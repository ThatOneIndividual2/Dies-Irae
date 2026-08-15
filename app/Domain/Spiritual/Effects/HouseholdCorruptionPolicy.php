<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class HouseholdCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::HOUSEHOLD;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'secret_vice_pressure' => intdiv($i, 5),
            'guest_scandal_risk' => intdiv($i, 8),
            'dynastic_trust' => -intdiv($i, 10),
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::CHARACTER, 'relation' => 'members'],
            ['subject_type' => CorruptionSubjectType::SETTLEMENT, 'relation' => 'seat'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['household_confession' => true, 'alms' => $state->intensity >= 30];
    }
}
