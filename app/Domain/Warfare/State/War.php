<?php

namespace App\Domain\Warfare\State;

use App\Domain\Warfare\Enums\WarStatus;
use App\Domain\Warfare\Enums\WarfareKind;

final class War
{
    /** @var int[] */
    public array $occupiedTerritoryIds = [];

    /** @var int[] */
    public array $infiltratedTerritoryIds = [];

    /** @var int[] */
    public array $overlayTerritoryIds = [];

    public int $attackerScore = 0;
    public int $defenderScore = 0;
    public int $attackerCasualties = 0;
    public int $defenderCasualties = 0;
    public ?int $winnerBelligerentId = null;
    public ?string $settlementType = null;
    public string $status = WarStatus::ACTIVE;

    public function __construct(
        public int $worldId,
        public int $id,
        public Belligerent $aggressor,
        public Belligerent $defender,
        public string $kind,
        public WarGoal $goal,
        public string $startedOn,
    ) {
        if (!in_array($kind, WarfareKind::all(), true)) {
            throw new \InvalidArgumentException("Unknown warfare kind: {$kind}");
        }
        if ($aggressor->worldId !== $worldId || $defender->worldId !== $worldId) {
            throw new \InvalidArgumentException('War belligerents must share the war world.');
        }
        if ($aggressor->id === $defender->id && $aggressor->kind === $defender->kind) {
            throw new \InvalidArgumentException('A belligerent cannot declare war on itself.');
        }
    }

    public function active(): bool
    {
        return $this->status === WarStatus::ACTIVE;
    }

    public function involves(int $belligerentId): bool
    {
        return $this->aggressor->id === $belligerentId || $this->defender->id === $belligerentId;
    }

    public function opponentOf(int $belligerentId): Belligerent
    {
        if ($this->aggressor->id === $belligerentId) {
            return $this->defender;
        }
        if ($this->defender->id === $belligerentId) {
            return $this->aggressor;
        }

        throw new \InvalidArgumentException('Belligerent is not a party to this war.');
    }
}
