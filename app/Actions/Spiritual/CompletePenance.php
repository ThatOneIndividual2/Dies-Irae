<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\PenanceService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Spiritual\PenanceChanged;
use App\Models\Penance;
use Carbon\CarbonInterface;

final class CompletePenance
{
    public function __construct(private PenanceService $penances)
    {
    }

    public function execute(Penance $penance, CarbonInterface $date): Penance
    {
        return Transactional::run(function () use ($penance, $date) {
            $updated = $this->penances->complete($penance, $date);
            AfterCommit::dispatch(fn () => event(new PenanceChanged($updated->id, $updated->character_id, $updated->status)));

            return $updated;
        });
    }
}
