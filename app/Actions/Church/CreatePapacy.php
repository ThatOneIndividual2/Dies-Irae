<?php

namespace App\Actions\Church;

use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\SeeKind;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Support\WorldBoundary;
use App\Models\Faith;
use App\Models\Papacy;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\World;
use RuntimeException;

final class CreatePapacy
{
    public function execute(World $world, Faith $faith, See $papalSee, SpiritualOffice $papalOffice): Papacy
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $faith->world_id, 'papacy faith');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $papalSee->world_id, 'papal see');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $papalOffice->world_id, 'papal office');

        if (Papacy::query()->where('world_id', $world->id)->exists()) {
            throw new RuntimeException('A world may have only one papacy.');
        }

        if ($papalSee->see_type !== SeeKind::PAPAL_SEE) {
            throw new RuntimeException('Papacy must be attached to a papal see.');
        }

        if ($papalOffice->rank !== SpiritualOfficeRank::POPE) {
            throw new RuntimeException('Papal office must have rank pope.');
        }

        $papalOffice->is_papal_apex = true;
        $papalOffice->see_id = $papalSee->id;
        $papalOffice->save();

        $papalSee->ordinary_office_id = $papalOffice->id;
        $papalSee->save();

        return Papacy::query()->create([
            'world_id' => $world->id,
            'faith_id' => $faith->id,
            'papal_see_id' => $papalSee->id,
            'papal_office_id' => $papalOffice->id,
            'status' => PapacyStatus::VACANT,
        ]);
    }
}
