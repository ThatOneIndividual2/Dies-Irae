<?php

namespace App\Actions\Sacred;

use App\Domain\Enums\RelicAcquisition;
use App\Domain\Sacred\RelicService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\RelicTransferred;
use App\Models\Relic;
use App\Models\RelicCustody;
use App\Models\World;
use Carbon\CarbonInterface;

final class TransferRelic
{
    public function __construct(private RelicService $relics)
    {
    }

    public function execute(
        World $world,
        Relic $relic,
        string $custodianType,
        ?int $custodianId,
        CarbonInterface $date,
        string $acquisition = RelicAcquisition::TRANSLATION,
        ?int $holdingId = null,
        ?int $territoryId = null,
        ?int $characterId = null,
        ?string $ownerType = null,
        ?int $ownerId = null
    ): RelicCustody {
        return Transactional::run(function () use (
            $world, $relic, $custodianType, $custodianId, $date, $acquisition, $holdingId, $territoryId, $characterId, $ownerType, $ownerId
        ) {
            $custody = $this->relics->placeInCustody(
                $world,
                $relic,
                $custodianType,
                $custodianId,
                $date,
                $acquisition,
                $holdingId,
                $territoryId,
                $characterId,
                $ownerType,
                $ownerId
            );
            AfterCommit::dispatch(fn () => event(new RelicTransferred($relic->id, $custodianType, $custodianId)));

            return $custody;
        });
    }
}
