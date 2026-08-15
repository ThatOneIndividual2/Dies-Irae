<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\SaintService;
use App\Domain\Support\Transactional;
use App\Models\Character;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;

final class RecognizeSaintCause
{
    public function __construct(private SaintService $saints)
    {
    }

    public function execute(World $world, Saint $saint, Character $actor, CarbonInterface $date): Saint
    {
        return Transactional::run(fn () => $this->saints->recognize($world, $saint, $actor, $date));
    }
}
