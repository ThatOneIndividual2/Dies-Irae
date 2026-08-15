<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\CorpseHandling;
use InvalidArgumentException;

final class SetCorpseHandling
{
    public function execute(CatastropheEngine $engine, string $settlementId, string $handling): void
    {
        if (!in_array($handling, CorpseHandling::all(), true)) {
            throw new InvalidArgumentException("Unknown corpse handling {$handling}");
        }
        $engine->settlement($settlementId)->corpseHandling = $handling;
    }
}
