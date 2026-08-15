<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\CanonicalCensure;
use App\Domain\Enums\EucharistStanding;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\World;

final class CanonicalStandingService
{
    public function __construct(private SpiritualStateService $states)
    {
    }

    public function setCensure(
        World $world,
        Character $character,
        string $censure,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): CharacterCanonicalState {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $character->world_id, 'censure character');
        if (!in_array($censure, CanonicalCensure::all(), true)) {
            throw new \InvalidArgumentException("Unknown censure: {$censure}");
        }

        [, $canonical] = $this->states->ensure($world, $character);
        $canonical = CharacterCanonicalState::query()->whereKey($canonical->id)->lockForUpdate()->firstOrFail();
        $canonical->censure = $censure;
        $canonical->censure_source_type = $sourceType;
        $canonical->censure_source_id = $sourceId;

        if (CanonicalCensure::barsCommunion($censure)) {
            $canonical->eucharist_standing = EucharistStanding::BARRED_BY_CENSURE;
        } elseif ($canonical->is_baptized && !$canonical->grave_unconfessed) {
            $canonical->eucharist_standing = EucharistStanding::IN_COMMUNION;
        } elseif ($canonical->is_baptized) {
            $canonical->eucharist_standing = EucharistStanding::BARRED_BY_SIN;
        } else {
            $canonical->eucharist_standing = EucharistStanding::UNBAPTIZED;
        }

        $canonical->save();

        return $canonical->fresh();
    }

    public function setGraveUnconfessed(World $world, Character $character, bool $grave): CharacterCanonicalState
    {
        [, $canonical] = $this->states->ensure($world, $character);
        $canonical = CharacterCanonicalState::query()->whereKey($canonical->id)->lockForUpdate()->firstOrFail();
        $canonical->grave_unconfessed = $grave;

        if ($grave && $canonical->censure === CanonicalCensure::NONE && $canonical->is_baptized) {
            $canonical->eucharist_standing = EucharistStanding::BARRED_BY_SIN;
        } elseif (!$grave && $canonical->censure === CanonicalCensure::NONE && $canonical->is_baptized) {
            $canonical->eucharist_standing = EucharistStanding::IN_COMMUNION;
        }

        $canonical->save();

        return $canonical->fresh();
    }
}
