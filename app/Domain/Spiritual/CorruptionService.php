<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Policies\CorruptionPolicyRegistry;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterSpiritualState;
use App\Models\CorruptionEvent;
use App\Models\CorruptionState;
use App\Models\World;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class CorruptionService
{
    public function __construct(private CorruptionPolicyRegistry $policies)
    {
    }

    public function current(string $subjectType, int $subjectId): ?CorruptionState
    {
        $this->assertType($subjectType);

        return CorruptionState::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('is_current', true)
            ->first();
    }

    public function apply(
        World $world,
        string $subjectType,
        int $subjectId,
        string $kind,
        int $intensityDelta,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $visibleSigns = null
    ): CorruptionState {
        $this->assertType($subjectType);

        $state = $this->current($subjectType, $subjectId);
        if ($state === null) {
            $state = CorruptionState::query()->create([
                'world_id' => $world->id,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'kind' => $kind,
                'intensity' => 0,
                'source' => $kind,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'started_date' => $date->toDateString(),
                'visible_signs' => $visibleSigns,
                'is_current' => true,
            ]);
        } else {
            WorldBoundary::assertSameWorld((int) $world->id, (int) $state->world_id, 'corruption subject');
            $state = CorruptionState::query()->whereKey($state->id)->lockForUpdate()->firstOrFail();
        }

        $before = (int) $state->intensity;
        $state->intensity = max(0, min(100, $before + $intensityDelta));
        if ($kind) {
            $state->kind = $kind;
        }
        if ($visibleSigns) {
            $state->visible_signs = array_values(array_unique(array_merge($state->visible_signs ?? [], $visibleSigns)));
        }
        if ($state->intensity === 0) {
            $state->is_current = null;
        }
        $state->save();

        CorruptionEvent::query()->create([
            'world_id' => $world->id,
            'corruption_state_id' => $state->id,
            'event_type' => $intensityDelta >= 0 ? 'applied' : 'reduced',
            'intensity_delta' => $intensityDelta,
            'occurred_date' => $date->toDateString(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        if ($subjectType === CorruptionSubjectType::CHARACTER) {
            $this->syncCharacterCache($subjectId, (int) $state->intensity);
        }

        return $state->fresh();
    }

    public function cleanse(
        World $world,
        string $subjectType,
        int $subjectId,
        int $amount,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): ?CorruptionState {
        $state = $this->current($subjectType, $subjectId);
        if ($state === null) {
            return null;
        }

        return $this->apply($world, $subjectType, $subjectId, $state->kind, -abs($amount), $date, $sourceType, $sourceId);
    }

    public function modifiers(string $subjectType, int $subjectId): array
    {
        $state = $this->current($subjectType, $subjectId);
        if ($state === null) {
            return [];
        }

        return $this->policies->for($subjectType)->gameplayModifiers($state);
    }

    public function spreadHints(string $subjectType, int $subjectId): array
    {
        $state = $this->current($subjectType, $subjectId);
        if ($state === null) {
            return [];
        }

        return $this->policies->for($subjectType)->spreadHints($state);
    }

    private function assertType(string $subjectType): void
    {
        if (!in_array($subjectType, CorruptionSubjectType::all(), true)) {
            throw new InvalidArgumentException("Unknown corruption subject: {$subjectType}");
        }
    }

    private function syncCharacterCache(int $characterId, int $intensity): void
    {
        CharacterSpiritualState::query()
            ->where('character_id', $characterId)
            ->where('is_current', true)
            ->update(['personal_corruption' => $intensity]);
    }
}
