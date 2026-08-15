<?php

namespace App\Domain\Warfare\State;

final class BattleResult
{
    public function __construct(
        public int $warId,
        public int $territoryId,
        public int $winnerArmyId,
        public int $loserArmyId,
        public Casualties $attackerCasualties,
        public Casualties $defenderCasualties,
        public int $attackerMoraleAfter,
        public int $defenderMoraleAfter,
        public bool $possessionEvent,
        public bool $battlefieldDesecrated,
        public int $corpses,
        public string $winnerSide,
    ) {
    }
}
