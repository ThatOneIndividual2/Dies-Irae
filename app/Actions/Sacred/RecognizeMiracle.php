<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\MiracleService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\MiracleRecognized;
use App\Models\Character;
use App\Models\Miracle;
use App\Models\World;
use Carbon\CarbonInterface;

final class RecognizeMiracle
{
    public function __construct(private MiracleService $miracles)
    {
    }

    public function execute(World $world, Miracle $miracle, Character $actor, CarbonInterface $date): Miracle
    {
        return Transactional::run(function () use ($world, $miracle, $actor, $date) {
            $updated = $this->miracles->recognize($world, $miracle, $actor, $date);
            AfterCommit::dispatch(fn () => event(new MiracleRecognized($miracle->id, (int) $actor->id)));

            return $updated;
        });
    }
}
