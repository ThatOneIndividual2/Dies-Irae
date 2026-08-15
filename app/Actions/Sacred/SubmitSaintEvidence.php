<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\SaintService;
use App\Domain\Support\Transactional;
use App\Models\Saint;
use App\Models\SaintEvidence;
use App\Models\World;
use Carbon\CarbonInterface;

final class SubmitSaintEvidence
{
    public function __construct(private SaintService $saints)
    {
    }

    public function execute(
        World $world,
        Saint $saint,
        string $evidenceType,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $notes = null
    ): SaintEvidence {
        return Transactional::run(fn () => $this->saints->submitEvidence(
            $world,
            $saint,
            $evidenceType,
            $date,
            $sourceType,
            $sourceId,
            $notes
        ));
    }
}
