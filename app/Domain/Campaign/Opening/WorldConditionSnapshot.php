<?php

namespace App\Domain\Campaign\Opening;

final class WorldConditionSnapshot
{
    public int $monthsElapsed = 0;
    public int $calendarMonth = 10;
    public string $archetype = 'count';
    public ?string $homeTerritory = null;
    public array $plagueTerritories = [];
    public array $neighborPlague = [];
    public array $tradeHubs = [];
    public array $tradeToPlague = [];
    public bool $plagueExists = false;
    public bool $hasTrade = false;
    public bool $hasWar = false;
    public bool $hasVassal = false;
    public int $localPlagueIntensity = 0;
    public int $despair = 0;
    public int $corruption = 0;
    public int $cultActivity = 0;
    public bool $cultRevealed = false;
    public bool $massDeathFired = false;
    public bool $manifestationFired = false;
    public int $pendingEvents = 0;
    public array $seeTerritories = [];
    public array $realmTerritories = [];
    public array $warTheaters = [];

    public function hasLocalPlague(): bool
    {
        return $this->localPlagueIntensity > 0 || in_array($this->homeTerritory, $this->plagueTerritories, true);
    }

    public function condition(string $name): bool
    {
        return match ($name) {
            'plague_exists_or_trade_to_plague' => $this->plagueExists || $this->tradeToPlague !== [],
            'has_trade' => $this->hasTrade,
            'local_plague' => $this->hasLocalPlague() || $this->plagueTerritories !== [],
            'harvest_season_or_plague' => in_array($this->calendarMonth, [8, 9, 10, 11], true) || $this->plagueExists,
            'neighbor_plague' => $this->neighborPlague !== [] || $this->plagueExists,
            'plague_or_despair' => $this->plagueExists || $this->despair >= 4,
            'war_or_vassal' => $this->hasWar || $this->hasVassal,
            'plague_or_corruption' => $this->plagueExists || $this->corruption >= 2,
            'trade_or_plague' => $this->hasTrade || $this->plagueExists,
            'corruption_or_cult' => $this->corruption >= 3 || $this->cultActivity >= 2,
            'manifestation_ready' => $this->massDeathFired && $this->plagueExists && $this->monthsElapsed >= 8 && ($this->cultActivity >= 2 || $this->despair >= 8 || $this->corruption >= 6),
            'active_war' => $this->hasWar,
            'has_vassal_or_peer' => $this->hasVassal,
            default => true,
        };
    }
}
