<?php

namespace App\Domain\Economy;

final class TradeLane
{
    public function __construct(
        public string $fromId,
        public string $toId,
        public int $capacity,
        public bool $blocked = false,
        public bool $haunted = false
    ) {
    }

    public function usableCapacity(): int
    {
        if ($this->blocked) {
            return 0;
        }

        $cap = $this->capacity;
        if ($this->haunted) {
            $cap = intdiv($cap, 3);
        }

        return max(0, $cap);
    }

    public function connects(string $a, string $b): bool
    {
        return ($this->fromId === $a && $this->toId === $b)
            || ($this->fromId === $b && $this->toId === $a);
    }
}
