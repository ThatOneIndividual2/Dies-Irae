<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class CharacterCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::CHARACTER;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'demonic_resistance' => -intdiv($i, 5),
            'confession_difficulty' => intdiv($i, 10),
            'scandal_risk' => intdiv($i, 8),
            'temptation_weight' => intdiv($i, 6),
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::HOUSEHOLD, 'relation' => 'household'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['confession' => true, 'penance' => $state->intensity >= 40];
    }
}
