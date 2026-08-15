<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class TerritoryCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::TERRITORY;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'veil_thinness' => intdiv($i, 5),
            'harvest_blessing' => -intdiv($i, 8),
            'incursion_foothold' => intdiv($i, 6),
            'legal_title_unaffected' => true,
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::SETTLEMENT, 'relation' => 'holdings'],
            ['subject_type' => CorruptionSubjectType::TERRITORY, 'relation' => 'neighbors'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['episcopal_circuit' => true, 'exorcism_of_place' => $state->intensity >= 45];
    }
}
