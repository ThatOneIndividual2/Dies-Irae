<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\DemonicInfluenceService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Spiritual\DemonicInfluenceChanged;
use App\Models\Character;
use App\Models\DemonicInfluence;
use App\Models\World;
use Carbon\CarbonInterface;

final class SetDemonicInfluence
{
    public function __construct(private DemonicInfluenceService $demonic)
    {
    }

    public function execute(
        World $world,
        Character $character,
        string $stage,
        int $intensity,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): DemonicInfluence {
        return Transactional::run(function () use ($world, $character, $stage, $intensity, $date, $sourceType, $sourceId, $metadata) {
            $influence = $this->demonic->set($world, $character, $stage, $intensity, $date, $sourceType, $sourceId, $metadata);
            AfterCommit::dispatch(fn () => event(new DemonicInfluenceChanged($influence->id, $character->id, $influence->stage)));

            return $influence;
        });
    }
}
