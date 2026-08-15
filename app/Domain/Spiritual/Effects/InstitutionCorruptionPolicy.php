<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class InstitutionCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::INSTITUTION;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'legitimacy' => -intdiv($i, 5),
            'simony_pressure' => intdiv($i, 6),
            'obedience_yield' => -intdiv($i, 8),
            'schism_risk' => intdiv($i, 10),
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::CHARACTER, 'relation' => 'officeholders'],
            ['subject_type' => CorruptionSubjectType::MONASTERY, 'relation' => 'houses'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['visitation' => true, 'deposition_or_reform' => $state->intensity >= 50];
    }
}
