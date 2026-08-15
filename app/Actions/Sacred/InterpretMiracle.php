<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\MiracleService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\MiracleDisputed;
use App\Models\Character;
use App\Models\Miracle;
use App\Models\MiracleInterpretation;
use App\Models\World;
use Carbon\CarbonInterface;

final class InterpretMiracle
{
    public function __construct(private MiracleService $miracles)
    {
    }

    public function execute(
        World $world,
        Miracle $miracle,
        Character $interpreter,
        string $reading,
        CarbonInterface $date,
        ?string $notes = null
    ): MiracleInterpretation {
        return Transactional::run(function () use ($world, $miracle, $interpreter, $reading, $date, $notes) {
            $row = $this->miracles->interpret($world, $miracle, $interpreter, $reading, $date, false, $notes);
            $fresh = $miracle->fresh();
            if ($fresh->status === 'disputed') {
                AfterCommit::dispatch(fn () => event(new MiracleDisputed($miracle->id)));
            }

            return $row;
        });
    }
}
