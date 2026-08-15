<?php

namespace App\Domain\Heresy;

use App\Domain\Enums\SpreadVector;
use App\Models\Army;
use App\Models\Character;
use App\Models\CorruptionState;
use App\Models\DespairState;
use App\Models\MovementAdherent;
use App\Models\MovementPresence;
use App\Models\ReligiousMovement;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\TitleOwnership;
use Illuminate\Support\Facades\Schema;

final class SpreadVectorGate
{
    public function canSpread(
        ReligiousMovement $movement,
        MovementPresence $origin,
        Territory $target,
        string $vector,
        Character $actor
    ): bool {
        if (!in_array($vector, SpreadVector::all(), true)) {
            return false;
        }

        if ((int) $origin->territory_id === (int) $target->id) {
            return false;
        }

        return match ($vector) {
            SpreadVector::CLERGY => $this->hasClergyAdherent($movement, $origin),
            SpreadVector::PREACHING => $origin->visibility === 'public' && $movement->visibility === 'public',
            SpreadVector::MIGRATION => $this->adjacent($origin->territory_id, $target->id) || $origin->popular_adherents > 50,
            SpreadVector::TRADE_ROUTES => $this->adjacent($origin->territory_id, $target->id),
            SpreadVector::NOBLE_PATRONAGE => $this->patronHoldsLand($movement, $target),
            SpreadVector::WAR => $this->armyPresent($movement->world_id, $origin->territory_id)
                || $this->armyPresent($movement->world_id, $target->id),
            SpreadVector::FAMINE => $this->famine($origin->territory) || $this->famine($target),
            SpreadVector::PLAGUE => $this->plague($origin->territory_id) || $this->plague($target->id),
            SpreadVector::DESPAIR => $this->despair($origin->territory_id) || $this->despair($target->id),
            SpreadVector::CORRUPTION => $this->corruption($movement->world_id, $origin->territory_id)
                || $this->corruption($movement->world_id, $target->id),
            SpreadVector::CHARISMATIC_LEADERS => $movement->leader_character_id !== null
                || $actor->id === $movement->founder_character_id,
            default => false,
        };
    }

    private function hasClergyAdherent(ReligiousMovement $movement, MovementPresence $origin): bool
    {
        if ((int) $origin->clergy_adherents > 0) {
            return true;
        }

        return MovementAdherent::query()
            ->where('movement_id', $movement->id)
            ->where('is_current', true)
            ->where('is_clergy', true)
            ->exists();
    }

    private function patronHoldsLand(ReligiousMovement $movement, Territory $target): bool
    {
        $patrons = MovementAdherent::query()
            ->where('movement_id', $movement->id)
            ->where('is_current', true)
            ->where('is_patron', true)
            ->pluck('character_id');

        if ($patrons->isEmpty()) {
            return false;
        }

        if ($target->owner_character_id && $patrons->contains((int) $target->owner_character_id)) {
            return true;
        }

        return TitleOwnership::query()
            ->whereIn('holder_character_id', $patrons)
            ->where('is_current', true)
            ->exists();
    }

    private function adjacent(int $fromId, int $toId): bool
    {
        if (!Schema::hasTable('territory_adjacencies')) {
            return false;
        }

        return TerritoryAdjacency::query()
            ->where(function ($q) use ($fromId, $toId) {
                $q->where('from_territory_id', $fromId)->where('to_territory_id', $toId);
            })
            ->orWhere(function ($q) use ($fromId, $toId) {
                $q->where('from_territory_id', $toId)->where('to_territory_id', $fromId);
            })
            ->exists();
    }

    private function armyPresent(int $worldId, int $territoryId): bool
    {
        if (!Schema::hasTable('armies')) {
            return false;
        }

        return Army::query()
            ->where('world_id', $worldId)
            ->where('territory_id', $territoryId)
            ->exists();
    }

    private function plague(int $territoryId): bool
    {
        if (!Schema::hasTable('territory_plague_states')) {
            return false;
        }

        return TerritoryPlagueState::query()
            ->where('territory_id', $territoryId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->exists();
    }

    private function despair(int $territoryId): bool
    {
        if (!Schema::hasTable('despair_states')) {
            return false;
        }

        $threshold = (int) config('fracture.spread.despair_threshold', 20);

        return DespairState::query()
            ->where('territory_id', $territoryId)
            ->where('intensity', '>=', $threshold)
            ->exists();
    }

    private function corruption(int $worldId, int $territoryId): bool
    {
        if (!Schema::hasTable('corruption_states')) {
            return false;
        }

        $threshold = (int) config('fracture.spread.corruption_threshold', 10);

        return CorruptionState::query()
            ->where('world_id', $worldId)
            ->where('subject_type', 'territory')
            ->where('subject_id', $territoryId)
            ->where('intensity', '>=', $threshold)
            ->exists();
    }

    private function famine(?Territory $territory): bool
    {
        if (!$territory) {
            return false;
        }

        if (isset($territory->food_stores)) {
            $max = (int) config('fracture.spread.famine_food_stores_max', 0);

            return (int) $territory->food_stores <= $max && $territory->food_stores !== null;
        }

        return ($territory->ruin_state ?? null) === 'starving';
    }
}
