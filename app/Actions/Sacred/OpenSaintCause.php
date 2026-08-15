<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\SaintService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\SaintCauseOpened;
use App\Models\Character;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;

final class OpenSaintCause
{
    public function __construct(private SaintService $saints)
    {
    }

    public function execute(World $world, Character $deceased, string $key, CarbonInterface $date): Saint
    {
        return Transactional::run(function () use ($world, $deceased, $key, $date) {
            $saint = $this->saints->openCause($world, $deceased, $key, $date);
            AfterCommit::dispatch(fn () => event(new SaintCauseOpened($saint->id, (int) $deceased->id)));

            return $saint;
        });
    }
}
