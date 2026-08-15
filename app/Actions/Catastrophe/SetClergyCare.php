<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\ClergyCare;
use InvalidArgumentException;

final class SetClergyCare
{
    public function execute(CatastropheEngine $engine, string $settlementId, string $care): void
    {
        if (!in_array($care, ClergyCare::all(), true)) {
            throw new InvalidArgumentException("Unknown clergy care {$care}");
        }
        $engine->settlement($settlementId)->clergyCare = $care;
    }
}
