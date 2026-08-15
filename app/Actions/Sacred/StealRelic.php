<?php

namespace App\Actions\Sacred;

use App\Actions\Spiritual\RecordSpiritualAct;
use App\Domain\Enums\SpiritualActType;
use App\Domain\Sacred\RelicService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\RelicStolen;
use App\Models\Character;
use App\Models\Relic;
use App\Models\RelicCustody;
use App\Models\World;
use Carbon\CarbonInterface;

final class StealRelic
{
    public function __construct(
        private RelicService $relics,
        private RecordSpiritualAct $acts
    ) {
    }

    public function execute(
        World $world,
        Relic $relic,
        Character $thief,
        string $custodianType,
        CarbonInterface $date,
        ?int $custodianId = null,
        ?int $holdingId = null,
        ?int $territoryId = null
    ): RelicCustody {
        return Transactional::run(function () use ($world, $relic, $thief, $custodianType, $date, $custodianId, $holdingId, $territoryId) {
            $custody = $this->relics->steal($world, $relic, $thief, $custodianType, $custodianId, $date, $holdingId, $territoryId);
            $this->acts->execute($world, $thief, SpiritualActType::SACRILEGE, $date, true);
            AfterCommit::dispatch(fn () => event(new RelicStolen($relic->id, (int) $thief->id)));

            return $custody;
        });
    }
}
