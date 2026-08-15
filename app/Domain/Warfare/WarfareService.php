<?php

namespace App\Domain\Warfare;

use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\Engine\WarfareKernel;
use App\Domain\Warfare\Ports\AftermathSink;
use App\Domain\Warfare\Support\InMemoryWarfareWorld;

/**
 * Domain contract implementation. The doctrine engine is WarDirector;
 * this class is the provider-bound handle other domains may ask for.
 */
final class WarfareService implements WarfareContract
{
    public function domainKey(): string
    {
        return 'warfare';
    }

    public function bootKernel(
        InMemoryWarfareWorld $world,
        ?AftermathSink $sink = null,
    ): WarDirector {
        return WarfareKernel::boot($world, $sink);
    }
}
