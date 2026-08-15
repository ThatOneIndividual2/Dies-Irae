<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;

final class TickPlague
{
    public function execute(CatastropheEngine $engine, int $days = 1): array
    {
        return $engine->tick($days);
    }
}
