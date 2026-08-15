<?php

namespace App\Domain\Warfare\Ports;

interface LevySource
{
    /**
     * @return HoldingLevySnapshot[]
     */
    public function holdingsForBelligerent(int $worldId, int $belligerentId, string $belligerentKind): array;
}
