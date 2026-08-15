<?php

namespace App\Actions\Famine;

use App\Domain\Economy\EconomyEngine;

final class TickFamine
{
    public function execute(EconomyEngine $engine, int $days = 1): void
    {
        $engine->consumeDays($days);
    }
}
