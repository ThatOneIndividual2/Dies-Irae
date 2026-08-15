<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Ports\AftermathSink;
use App\Domain\Warfare\Ports\NullAftermathSink;
use App\Domain\Warfare\Support\InMemoryWarfareWorld;

final class WarfareKernel
{
    public static function boot(
        InMemoryWarfareWorld $world,
        ?AftermathSink $sink = null,
        ?RandomSource $random = null,
        ?WarfareBalance $balance = null,
    ): WarDirector {
        return new WarDirector(
            $world,
            $world,
            $world,
            $world,
            $world,
            $world,
            $sink ?? new NullAftermathSink(),
            $random ?? new NullRandom(),
            $balance,
        );
    }
}
