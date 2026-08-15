<?php

namespace App\Domain\Spiritual\Effects;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionEffectPolicy;
use App\Models\CorruptionState;

final class SettlementCorruptionPolicy implements CorruptionEffectPolicy
{
    public function subjectType(): string
    {
        return CorruptionSubjectType::SETTLEMENT;
    }

    public function gameplayModifiers(CorruptionState $state): array
    {
        $i = (int) $state->intensity;

        return [
            'heresy_foothold' => intdiv($i, 6),
            'pilgrimage_traffic' => -intdiv($i, 8),
            'parish_attendance' => -intdiv($i, 7),
            'cult_recruitment' => intdiv($i, 10),
        ];
    }

    public function spreadHints(CorruptionState $state): array
    {
        return [
            ['subject_type' => CorruptionSubjectType::TERRITORY, 'relation' => 'parent_territory'],
            ['subject_type' => CorruptionSubjectType::ARMY, 'relation' => 'garrison'],
        ];
    }

    public function cleansingRequirements(CorruptionState $state): array
    {
        return ['procession' => true, 'relic_presence' => $state->intensity >= 50];
    }
}
