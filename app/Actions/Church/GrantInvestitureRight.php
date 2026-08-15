<?php

namespace App\Actions\Church;

use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\InvestitureRight;
use App\Models\See;
use App\Models\Title;
use Carbon\CarbonInterface;

final class GrantInvestitureRight
{
    public function execute(
        See $see,
        string $mode,
        CarbonInterface $date,
        ?Character $nominator = null,
        ?Title $nominatorTitle = null
    ): InvestitureRight {
        if ($nominator) {
            WorldBoundary::assertSameWorldEntities('investiture nominator', $see, $nominator);
        }
        if ($nominatorTitle) {
            WorldBoundary::assertSameWorldEntities('investiture title', $see, $nominatorTitle);
        }

        return Transactional::run(function () use ($see, $mode, $date, $nominator, $nominatorTitle) {
            $current = InvestitureRight::query()
                ->where('see_id', $see->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($current) {
                $current->ended_date = $date->toDateString();
                $current->is_current = null;
                $current->save();
            }

            return InvestitureRight::query()->create([
                'world_id' => $see->world_id,
                'see_id' => $see->id,
                'nominator_character_id' => $nominator?->id,
                'nominator_title_id' => $nominatorTitle?->id,
                'mode' => $mode,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        });
    }
}
