<?php

namespace App\Domain\Warfare\Engine;

final class SequenceRandom implements RandomSource
{
    /** @var int[] */
    private array $ints;

    private int $cursor = 0;

    public function __construct(array $ints = [])
    {
        $this->ints = $ints;
    }

    public function int(int $min, int $max): int
    {
        if ($this->ints === []) {
            return $min;
        }
        $value = $this->ints[$this->cursor % count($this->ints)];
        $this->cursor++;

        return max($min, min($max, $value));
    }

    public function chance(int $percent): bool
    {
        if ($percent <= 0) {
            return false;
        }

        return $this->int(1, 100) <= $percent;
    }
}
