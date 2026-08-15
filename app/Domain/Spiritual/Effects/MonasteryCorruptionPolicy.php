<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class MonasteryCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::MONASTERY;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'liturgy_quality' => -intdiv($i, 5),
            'relic_safety' => -intdiv($i, 6),
            'vocation_yield' => -intdiv($i, 8),
            'chronicle_integrity' => -intdiv($i, 10),
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::INSTITUTION, 'relation' => 'order'],
            ['subject_type' => CorruptionSubjectType::SETTLEMENT, 'relation' => 'adjacent'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['visitation' => true, 'abbatial_reform' => true, 'relic_exposition' => $state->intensity >= 40];
    }
}
