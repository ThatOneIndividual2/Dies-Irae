<?php

namespace App\Domain\Campaign\Historical;

use App\Domain\Campaign\CampaignPack;
use App\Domain\Enums\ArmyKind;
use App\Models\Army;
use App\Models\CampaignState;
use App\Models\Character;
use App\Models\Cult;
use App\Models\DemonicThreat;
use App\Models\HolyOrder;
use App\Models\HolyOrderHouse;
use App\Models\LocalWar;
use App\Models\Papacy;
use App\Models\Realm;
use App\Models\See;
use App\Models\SpiritualOfficeHoldership;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\TitleClaim;
use App\Models\TitleOwnership;
use App\Models\World;
use App\Validation\ValidationIssue;
use App\Validation\ValidationReport;

final class HistoricalValidator
{
    public function __construct(private ?CampaignPack $pack = null)
    {
    }

    public function validate(World $world): ValidationReport
    {
        $report = new ValidationReport((int) $world->id);
        $facts = ($this->pack ?? CampaignPack::europa1347())->get('historical');

        foreach ($facts['facts'] as $fact) {
            $ok = $this->check($world, $fact);
            if (!$ok) {
                $report->add(new ValidationIssue(
                    'historical.'.$fact['id'],
                    ($fact['note'] ?? $fact['id']).' failed.',
                    'error',
                    'historical',
                    null
                ));
            }
        }

        if (!CampaignState::query()->where('world_id', $world->id)->exists()) {
            $report->add(new ValidationIssue(
                'historical.campaign_state',
                'Campaign state missing for Europa 1347.',
                'error'
            ));
        }

        return $report;
    }

    private function check(World $world, array $fact): bool
    {
        $assert = $fact['assert'] ?? '';

        return match ($assert) {
            'start_date' => $world->start_date->toDateString() === $fact['value'],
            'papacy_territory' => $this->papacySeat($world) === $fact['value'],
            'character_office' => $this->characterHoldsOffice($world, $fact['character'], $fact['office_rank']),
            'character_title' => $this->characterHoldsTitle($world, $fact['character'], $fact['title']),
            'territory_owner' => optional(Territory::query()->where('world_id', $world->id)->where('key', $fact['territory'])->first())->owner_character_id
                === optional($this->character($world, $fact['character']))->id,
            'holy_order_seat' => $this->orderSeated($world, $fact['order'], $fact['territory']),
            'prince_bishop' => $this->characterHoldsTitle($world, $fact['character'], $fact['title'])
                && $this->characterHoldsOffice($world, $fact['character'], $fact['office_rank']),
            'bishop_without_title' => $this->characterHoldsOffice($world, $fact['character'], 'archbishop')
                && TitleOwnership::query()->where('holder_character_id', optional($this->character($world, $fact['character']))->id)->where('is_current', true)->doesntExist(),
            'plague_only_entry' => $this->plagueOnly($world, $fact['territory']),
            'cults_unrevealed' => !Cult::query()->where('world_id', $world->id)->where('revealed', true)->exists(),
            'threats_dormant' => !DemonicThreat::query()->where('world_id', $world->id)->where('status', '!=', 'dormant')->exists(),
            'no_demonic_armies' => !Army::query()->where('world_id', $world->id)->where('kind', ArmyKind::DEMONIC)->exists(),
            'local_war' => LocalWar::query()->where('world_id', $world->id)->where('key', $fact['war'])->exists(),
            'title_claim' => $this->hasClaim($world, $fact['claimant'], $fact['title']),
            'holy_order_not_realm' => Realm::query()->where('world_id', $world->id)->where('key', 'like', '%hospital%')->doesntExist()
                && HolyOrder::query()->where('world_id', $world->id)->where('key', $fact['order'])->exists(),
            default => false,
        };
    }

    private function papacySeat(World $world): ?string
    {
        $papacy = Papacy::query()->where('world_id', $world->id)->first();
        if (!$papacy) {
            return null;
        }
        $see = See::query()->find($papacy->papal_see_id);

        return $see ? optional(Territory::query()->find($see->territory_id))->key : null;
    }

    private function character(World $world, string $key): ?Character
    {
        return Character::query()->where('world_id', $world->id)->where('key', $key)->first();
    }

    private function characterHoldsTitle(World $world, string $characterKey, string $titleKey): bool
    {
        $character = $this->character($world, $characterKey);
        $title = Title::query()->where('world_id', $world->id)->where('key', $titleKey)->first();
        if (!$character || !$title) {
            return false;
        }

        return TitleOwnership::query()
            ->where('title_id', $title->id)
            ->where('holder_character_id', $character->id)
            ->where('is_current', true)
            ->exists();
    }

    private function characterHoldsOffice(World $world, string $characterKey, string $rank): bool
    {
        $character = $this->character($world, $characterKey);
        if (!$character) {
            return false;
        }

        return SpiritualOfficeHoldership::query()
            ->where('holder_character_id', $character->id)
            ->where('is_current', true)
            ->whereHas('office', fn ($q) => $q->where('rank', $rank))
            ->exists();
    }

    private function orderSeated(World $world, string $orderKey, string $territoryKey): bool
    {
        $order = HolyOrder::query()->where('world_id', $world->id)->where('key', $orderKey)->first();
        $territory = Territory::query()->where('world_id', $world->id)->where('key', $territoryKey)->first();
        if (!$order || !$territory) {
            return false;
        }

        return HolyOrderHouse::query()
            ->where('holy_order_id', $order->id)
            ->where('territory_id', $territory->id)
            ->where('is_headquarters', true)
            ->exists();
    }

    private function plagueOnly(World $world, string $entry): bool
    {
        $states = TerritoryPlagueState::query()->where('world_id', $world->id)->where('is_active', true)->with('territory')->get();
        if ($states->count() !== 1) {
            return false;
        }

        return $states->first()->territory?->key === $entry;
    }

    private function hasClaim(World $world, string $claimant, string $titleKey): bool
    {
        $character = $this->character($world, $claimant);
        $title = Title::query()->where('world_id', $world->id)->where('key', $titleKey)->first();
        if (!$character || !$title) {
            return false;
        }

        return TitleClaim::query()
            ->where('title_id', $title->id)
            ->where('claimant_character_id', $character->id)
            ->where('is_active', true)
            ->exists();
    }
}
