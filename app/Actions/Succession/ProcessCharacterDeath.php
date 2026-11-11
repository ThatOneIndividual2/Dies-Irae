<?php

namespace App\Actions\Succession;

use App\Actions\Titles\TitleOwnershipMutator;
use App\Domain\Enums\AcquisitionType;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\GameEventStatus;
use App\Domain\Succession\HeirOrder;
use App\Domain\Succession\SuccessionEligibilityGate;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Army;
use App\Models\Character;
use App\Models\CharacterControlHistory;
use App\Models\CharacterDeathProcessing;
use App\Models\ChurchRelation;
use App\Models\DynastyHouse;
use App\Models\FeudalContract;
use App\Models\GameEvent;
use App\Models\Realm;
use App\Models\SuccessionSummary;
use App\Models\Title;
use App\Models\TitleOwnership;
use App\Models\User;
use App\Models\VassalRelationship;
use App\Models\World;
use Carbon\CarbonInterface;

final class ProcessCharacterDeath
{
    public function __construct(
        private HeirOrder $order,
        private SuccessionEligibilityGate $gate,
        private TitleOwnershipMutator $titles
    ) {
    }

    public function execute(Character $character, string $cause, CarbonInterface $date): CharacterDeathProcessing
    {
        return Transactional::run(function () use ($character, $cause, $date) {
            $dead = Character::query()->whereKey($character->id)->lockForUpdate()->firstOrFail();
            $existing = CharacterDeathProcessing::query()->where('character_id', $dead->id)->first();
            if ($existing) {
                return $existing;
            }

            $world = World::query()->whereKey($dead->world_id)->lockForUpdate()->firstOrFail();
            WorldBoundary::assertSameWorld((int) $world->id, (int) $dead->world_id, 'character death');

            $dead->is_alive = false;
            $dead->death_date = $date->toDateString();
            $dead->save();

            $held = TitleOwnership::query()
                ->where('holder_character_id', $dead->id)
                ->where('is_current', true)
                ->get();

            $heirByTitle = [];
            $firstHeir = null;
            $skipped = [];

            foreach ($held as $ownership) {
                $title = Title::query()->whereKey($ownership->title_id)->firstOrFail();
                $lawKey = $title->successionLaw?->key ?: HeirOrder::LAW_KEY;
                $heir = null;
                foreach ($this->order->candidates($dead) as $candidate) {
                    $reason = $this->gate->ineligibilityReason($candidate, $title);
                    if ($reason !== null) {
                        $skipped[] = $candidate->displayName().': '.$reason;
                        continue;
                    }
                    $heir = $candidate;
                    break;
                }

                if ($heir) {
                    $this->titles->transfer($title, $heir, $date, null, AcquisitionType::SUCCESSION);
                    $heirByTitle[(int) $title->id] = $heir;
                    $firstHeir = $firstHeir ?: $heir;
                    $this->summarize($world, $dead, $title, $heir, $lawKey, 'inherited', null, $date);
                } else {
                    $this->titles->revoke($title, $date, null);
                    $this->summarize($world, $dead, $title, null, $lawKey, 'vacant', 'no_eligible_heir', $date);
                }
            }

            $primaryHeir = $this->primaryHeir($world, $heirByTitle, $firstHeir);
            $this->retargetVassals($dead, $primaryHeir, $date);
            $this->retargetRealm($world, $dead, $primaryHeir);
            $this->retargetHouse($dead, $primaryHeir);
            $this->moveTreasury($dead, $primaryHeir);
            $this->copyChurchStanding($dead, $primaryHeir);
            $this->transferLevies($dead, $primaryHeir);
            $this->transferControl($dead, $primaryHeir);
            $this->retargetEvents($dead, $primaryHeir);

            return CharacterDeathProcessing::query()->create([
                'world_id' => $world->id,
                'character_id' => $dead->id,
                'cause' => $cause,
                'processed_on' => $date->toDateString(),
                'heir_character_id' => $primaryHeir?->id,
                'notes' => [
                    'skipped' => array_values(array_unique($skipped)),
                    'titles' => count($held),
                ],
            ]);
        });
    }

    /**
     * @param  array<int, Character>  $heirByTitle
     */
    private function primaryHeir(World $world, array $heirByTitle, ?Character $fallback): ?Character
    {
        $realm = Realm::query()->where('world_id', $world->id)->whereNotNull('primary_title_id')->first();
        if ($realm && isset($heirByTitle[(int) $realm->primary_title_id])) {
            return $heirByTitle[(int) $realm->primary_title_id];
        }

        return $fallback;
    }

    private function summarize(
        World $world,
        Character $dead,
        Title $title,
        ?Character $heir,
        string $lawKey,
        string $outcome,
        ?string $reason,
        CarbonInterface $date
    ): void {
        $fingerprint = hash('sha256', implode('|', [
            $world->id,
            $dead->id,
            $title->id,
            $date->toDateString(),
            $lawKey,
            $outcome,
        ]));

        if (SuccessionSummary::query()->where('fingerprint', $fingerprint)->exists()) {
            return;
        }

        SuccessionSummary::query()->create([
            'world_id' => $world->id,
            'deceased_character_id' => $dead->id,
            'title_id' => $title->id,
            'heir_character_id' => $heir?->id,
            'law_key' => $lawKey,
            'outcome' => $outcome,
            'reason' => $reason,
            'processed_on' => $date->toDateString(),
            'fingerprint' => $fingerprint,
        ]);
    }

    private function retargetVassals(Character $dead, ?Character $heir, CarbonInterface $date): void
    {
        $links = VassalRelationship::query()
            ->where('liege_character_id', $dead->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->get();

        foreach ($links as $link) {
            $contract = $link->contract_id
                ? FeudalContract::query()->whereKey($link->contract_id)->first()
                : null;
            VassalRelationship::query()->whereKey($link->id)->update([
                'is_current' => null,
                'ended_date' => $date->toDateString(),
            ]);
            if ($contract) {
                $contract->ended_date = $date->toDateString();
                $contract->save();
            }

            if (!$heir || (int) $link->vassal_character_id === (int) $heir->id) {
                continue;
            }

            $replacement = FeudalContract::query()->create([
                'world_id' => $link->world_id,
                'realm_id' => $contract->realm_id ?? $link->realm_id,
                'liege_character_id' => $heir->id,
                'vassal_character_id' => $link->vassal_character_id,
                'tax_rate' => $contract->tax_rate ?? 10,
                'levy_rate' => $contract->levy_rate ?? 20,
                'effective_date' => $date->toDateString(),
            ]);

            VassalRelationship::query()->create([
                'world_id' => $link->world_id,
                'realm_id' => $link->realm_id,
                'liege_character_id' => $heir->id,
                'vassal_character_id' => $link->vassal_character_id,
                'primary_title_id' => $link->primary_title_id,
                'contract_id' => $replacement->id,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        }
    }

    private function retargetRealm(World $world, Character $dead, ?Character $heir): void
    {
        if (!$heir) {
            return;
        }

        Realm::query()
            ->where('world_id', $world->id)
            ->where('top_liege_character_id', $dead->id)
            ->update(['top_liege_character_id' => $heir->id]);
    }

    private function retargetHouse(Character $dead, ?Character $heir): void
    {
        if (!$heir) {
            return;
        }

        DynastyHouse::query()
            ->where('head_character_id', $dead->id)
            ->update(['head_character_id' => $heir->id]);
    }

    private function moveTreasury(Character $dead, ?Character $heir): void
    {
        if (!$heir) {
            return;
        }

        $heir->treasury = (int) $heir->treasury + (int) $dead->treasury;
        $heir->save();
        $dead->treasury = 0;
        $dead->save();
    }

    private function copyChurchStanding(Character $dead, ?Character $heir): void
    {
        if (!$heir) {
            return;
        }

        $existing = ChurchRelation::query()->where('character_id', $heir->id)->first();
        if ($existing) {
            return;
        }

        $rel = ChurchRelation::query()->where('character_id', $dead->id)->first();
        if (!$rel) {
            return;
        }

        ChurchRelation::query()->create([
            'world_id' => $rel->world_id,
            'character_id' => $heir->id,
            'see_id' => $rel->see_id,
            'standing' => $rel->standing,
        ]);
    }

    private function transferLevies(Character $dead, ?Character $heir): void
    {
        if (!$heir) {
            return;
        }

        $armies = Army::query()
            ->where('owner_character_id', $dead->id)
            ->where('kind', ArmyKind::LEVY)
            ->get();

        foreach ($armies as $army) {
            $army->owner_character_id = $heir->id;
            if ((int) $army->commander_character_id === (int) $dead->id) {
                $army->commander_character_id = $heir->id;
            }
            $army->save();
        }
    }

    private function transferControl(Character $dead, ?Character $heir): void
    {
        if (!$heir || !$heir->is_alive) {
            return;
        }

        $users = User::query()->where('controlled_character_id', $dead->id)->get();
        foreach ($users as $user) {
            $blocked = CharacterControlHistory::query()
                ->where('character_id', $heir->id)
                ->where('is_current', true)
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($blocked) {
                continue;
            }

            CharacterControlHistory::query()
                ->where('user_id', $user->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->get();
            CharacterControlHistory::query()
                ->where('user_id', $user->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => null,
                    'ended_at' => now(),
                ]);

            CharacterControlHistory::query()->create([
                'world_id' => $heir->world_id,
                'user_id' => $user->id,
                'character_id' => $heir->id,
                'started_at' => now(),
                'reason' => 'succession',
                'is_current' => true,
            ]);

            $user->controlled_character_id = $heir->id;
            $user->name = $heir->displayName();
            $user->save();
        }
    }

    private function retargetEvents(Character $dead, ?Character $heir): void
    {
        if (!$heir) {
            return;
        }

        GameEvent::query()
            ->where('audience_character_id', $dead->id)
            ->whereIn('status', [GameEventStatus::SCHEDULED, GameEventStatus::AWAITING_DECISION])
            ->update(['audience_character_id' => $heir->id]);
    }
}
