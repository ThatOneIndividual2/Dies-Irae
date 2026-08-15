<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\CanonicalCensure;
use App\Domain\Enums\CapitalVice;
use App\Domain\Enums\EucharistStanding;
use App\Domain\Enums\HolyOrdersGrade;
use App\Domain\Enums\RepentanceDisposition;
use App\Domain\Enums\TheologicalVirtue;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\CharacterSpiritualState;
use App\Models\SpiritualDispositionLedger;
use App\Models\World;
use Carbon\CarbonInterface;

final class SpiritualStateService
{
    public function ensure(World $world, Character $character): array
    {
        $spiritual = CharacterSpiritualState::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();

        if ($spiritual === null) {
            $start = config('spiritual.starting');
            $spiritual = CharacterSpiritualState::query()->create([
                'world_id' => $world->id,
                'character_id' => $character->id,
                'faith' => $start[TheologicalVirtue::FAITH],
                'hope' => $start[TheologicalVirtue::HOPE],
                'charity' => $start[TheologicalVirtue::CHARITY],
                'pride' => $start[CapitalVice::PRIDE],
                'greed' => $start[CapitalVice::GREED],
                'lust' => $start[CapitalVice::LUST],
                'envy' => $start[CapitalVice::ENVY],
                'gluttony' => $start[CapitalVice::GLUTTONY],
                'wrath' => $start[CapitalVice::WRATH],
                'sloth' => $start[CapitalVice::SLOTH],
                'despair' => $start['despair'],
                'repentance' => $start['repentance'],
                'personal_corruption' => 0,
                'is_current' => true,
            ]);
        }

        $canonical = CharacterCanonicalState::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();

        if ($canonical === null) {
            $canonical = CharacterCanonicalState::query()->create([
                'world_id' => $world->id,
                'character_id' => $character->id,
                'is_baptized' => false,
                'is_confirmed' => false,
                'eucharist_standing' => EucharistStanding::UNBAPTIZED,
                'holy_orders_grade' => HolyOrdersGrade::NONE,
                'censure' => CanonicalCensure::NONE,
                'grave_unconfessed' => false,
                'is_current' => true,
            ]);
        }

        return [$spiritual, $canonical];
    }

    /**
     * @param array<string,int> $deltas
     */
    public function applyDeltas(
        World $world,
        Character $character,
        array $deltas,
        string $reason,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): CharacterSpiritualState {
        /** @var CharacterSpiritualState $spiritual */
        [$spiritual] = $this->ensure($world, $character);
        $spiritual = CharacterSpiritualState::query()->whereKey($spiritual->id)->lockForUpdate()->firstOrFail();

        $axes = array_merge(TheologicalVirtue::all(), CapitalVice::all(), ['despair', 'personal_corruption']);

        foreach ($deltas as $facet => $delta) {
            if ($facet === 'repentance') {
                continue;
            }
            if (!in_array($facet, $axes, true)) {
                continue;
            }
            $before = (int) $spiritual->{$facet};
            $after = max(0, min(100, $before + (int) $delta));
            $applied = $after - $before;
            $spiritual->{$facet} = $after;

            if ($applied !== 0) {
                SpiritualDispositionLedger::query()->create([
                    'world_id' => $world->id,
                    'character_id' => $character->id,
                    'facet' => $facet,
                    'delta' => $applied,
                    'reason' => $reason,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'occurred_date' => $date->toDateString(),
                    'metadata' => $metadata,
                ]);
            }
        }

        if (isset($deltas['repentance']) && is_string($deltas['repentance'])) {
            $spiritual->repentance = $deltas['repentance'];
        }

        $spiritual->save();

        return $spiritual->fresh();
    }

    public function setRepentance(CharacterSpiritualState $spiritual, string $disposition): CharacterSpiritualState
    {
        if (!in_array($disposition, RepentanceDisposition::all(), true)) {
            return $spiritual;
        }
        $spiritual->repentance = $disposition;
        $spiritual->save();

        return $spiritual;
    }

    public function setPersonalCorruption(CharacterSpiritualState $spiritual, int $intensity): void
    {
        $spiritual->personal_corruption = max(0, min(100, $intensity));
        $spiritual->save();
    }

    public function demonicResistance(CharacterSpiritualState $spiritual, CharacterCanonicalState $canonical): int
    {
        $cfg = config('spiritual.demonic_resistance');
        $score = (int) round($spiritual->faith * $cfg['faith_weight']);
        if ($canonical->is_confirmed) {
            $score += (int) $cfg['confirmation_bonus'];
        }
        if ($canonical->last_anointing_date) {
            $score += (int) $cfg['anointing_bonus'];
        }
        $score -= intdiv((int) $spiritual->personal_corruption, 5);
        $score -= intdiv((int) $spiritual->despair, 8);

        return max(0, min(100, $score));
    }
}
