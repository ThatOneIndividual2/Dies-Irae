<?php

namespace App\Actions\Sacred;

use App\Domain\Sacred\MiracleService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Sacred\MiracleClaimed;
use App\Models\Character;
use App\Models\Miracle;
use App\Models\Relic;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;

final class RecordMiracleClaim
{
    public function __construct(private MiracleService $miracles)
    {
    }

    public function execute(
        World $world,
        string $category,
        string $sourceType,
        CarbonInterface $date,
        ?int $sourceId = null,
        ?Saint $saint = null,
        ?Relic $relic = null,
        ?Character $beneficiary = null,
        ?Character $petitioner = null,
        ?string $placeType = null,
        ?int $placeId = null,
        array $witnessCharacterIds = [],
        ?array $metadata = null
    ): Miracle {
        return Transactional::run(function () use (
            $world, $category, $sourceType, $date, $sourceId, $saint, $relic, $beneficiary, $petitioner, $placeType, $placeId, $witnessCharacterIds, $metadata
        ) {
            $miracle = $this->miracles->claim(
                $world,
                $category,
                $sourceType,
                $date,
                $sourceId,
                $saint,
                $relic,
                $beneficiary,
                $petitioner,
                $placeType,
                $placeId,
                $witnessCharacterIds,
                $metadata
            );
            AfterCommit::dispatch(fn () => event(new MiracleClaimed($miracle->id, $miracle->category)));

            return $miracle;
        });
    }
}
