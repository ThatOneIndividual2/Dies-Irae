<?php

namespace App\Actions\Warfare;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Engine\WarDirector;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\UnitStack;

final class RaiseLevies
{
    public function __construct(private WarDirector $director)
    {
    }

    /**
     * @return UnitStack[]
     */
    public function execute(Belligerent $belligerent, ?ForceProfile $force = null): array
    {
        $force = $force ?? $this->director->forceProfile($belligerent);

        return $this->director->levies->raise(
            $belligerent->worldId,
            $belligerent->id,
            $belligerent->kind,
            $force
        );
    }
}
