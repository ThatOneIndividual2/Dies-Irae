<?php

namespace App\Domain\Warfare\State;

final class Siege
{
    public function __construct(
        public int $worldId,
        public int $id,
        public int $warId,
        public int $territoryId,
        public int $attackerArmyId,
        public int $fortification,
        public int $garrison,
        public int $progress,
        public string $method,
        public bool $fallen = false,
    ) {
    }

    public function fallen(): bool
    {
        return $this->fallen || $this->progress >= 100;
    }
}
