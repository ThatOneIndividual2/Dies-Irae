<?php

namespace App\Actions\Sacred;

use App\Actions\Spiritual\RecordSpiritualAct;
use App\Domain\Enums\SpiritualActType;
use App\Domain\Sacred\RelicService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\RelicDestroyed;
use App\Models\Character;
use App\Models\Relic;
use App\Models\World;
use Carbon\CarbonInterface;

final class DestroyRelic
{
    public function __construct(
        private RelicService $relics,
        private RecordSpiritualAct $acts
    ) {
    }

    public function execute(World $world, Relic $relic, Character $actor, CarbonInterface $date): Relic
    {
        return Transactional::run(function () use ($world, $relic, $actor, $date) {
            $updated = $this->relics->destroy($world, $relic, $actor, $date);
            $this->acts->execute($world, $actor, SpiritualActType::DESECRATION, $date, true);
            AfterCommit::dispatch(fn () => event(new RelicDestroyed($relic->id, (int) $actor->id)));

            return $updated;
        });
    }
}
