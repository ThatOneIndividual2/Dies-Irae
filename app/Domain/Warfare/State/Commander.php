<?php

namespace App\Domain\Warfare\State;

final class Commander
{
    public function __construct(
        public int $characterId,
        public int $martial,
        public int $piety,
        public int $corruption,
        public bool $possessed,
        public bool $cleric,
        public bool $holyOrderKnight,
    ) {
    }

    public function battleBonus(): int
    {
        $bonus = $this->martial;
        if ($this->possessed) {
            $bonus += 4;
        }
        if ($this->holyOrderKnight) {
            $bonus += 2;
        }

        return $bonus;
    }

    public function fearResistance(): int
    {
        $resist = (int) floor($this->piety / 10);
        if ($this->cleric || $this->holyOrderKnight) {
            $resist += 15;
        }
        if ($this->possessed) {
            $resist -= 20;
        }

        return $resist;
    }
}
