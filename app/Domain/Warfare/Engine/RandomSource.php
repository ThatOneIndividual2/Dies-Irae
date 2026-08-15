<?php

namespace App\Domain\Warfare\Engine;

interface RandomSource
{
    public function int(int $min, int $max): int;

    public function chance(int $percent): bool;
}
