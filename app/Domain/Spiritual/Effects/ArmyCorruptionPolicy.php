<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class ArmyCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::ARMY;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'morale' => -intdiv($i, 4),
            'desertion_risk' => intdiv($i, 6),
            'friendly_discipline' => -intdiv($i, 8),
            'demonic_exposure' => intdiv($i, 5),
            'consecrated_unit_penalty' => intdiv($i, 7),
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::TERRITORY, 'relation' => 'occupied'],
            ['subject_type' => CorruptionSubjectType::CHARACTER, 'relation' => 'commander'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['chaplain_mass' => true, 'purge_or_penance' => $state->intensity >= 35];
    }
}
