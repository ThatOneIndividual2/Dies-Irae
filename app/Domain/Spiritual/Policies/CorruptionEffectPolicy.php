<?php

namespace App\Domain\Spiritual\Policies;

use App\Models\CorruptionState;

interface CorruptionEffectPolicy
{
    public function subjectType(): string;

    /**
     * Gameplay modifiers other domains may read. Never a single morality number.
     *
     * @return array<string,int|string|bool>
     */
    public function gameplayModifiers(CorruptionState $state): array;

    /**
     * Hints for explicit spread actions. This policy does not mutate neighbors.
     *
     * @return array<int, array{subject_type:string, relation:string}>
     */
    public function spreadHints(CorruptionState $state): array;

    /**
     * @return array<string, mixed>
     */
    public function cleansingRequirements(CorruptionState $state): array;
}
