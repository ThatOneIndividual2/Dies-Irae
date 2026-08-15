<?php

namespace App\Actions\Catastrophe;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Catastrophe\PlagueCatalog;
use App\Domain\Catastrophe\PlagueProfile;

final class SeedPlagueWave
{
    public function execute(CatastropheEngine $engine, string $originId, int $infectious, ?string $strainKey = null): void
    {
        if ($strainKey !== null) {
            $engine->profile = PlagueCatalog::get($strainKey);
            $engine->waveId = $engine->profile->key.'-'.$engine->worldId;
        }
        $engine->seedPlague($originId, $infectious);
    }
}
