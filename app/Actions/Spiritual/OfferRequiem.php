<?php

namespace App\Actions\Spiritual;

use App\Domain\Enums\SacramentType;
use App\Models\Character;
use App\Models\SacramentRecord;
use App\Models\Territory;
use App\Models\World;
use Carbon\Carbon;

final class OfferRequiem
{
    public function __construct(
        private EnsureHistoricalBaptism $baptism,
        private ConferSacrament $confer
    ) {
    }

    public function execute(World $world, Character $subject, Character $minister, Territory $place): SacramentRecord
    {
        $this->baptism->execute($world, $subject);

        return $this->confer->execute(
            $world,
            SacramentType::EUCHARIST,
            $subject,
            Carbon::parse($world->current_date),
            $minister,
            null,
            'territory',
            (int) $place->id,
            null,
            ['rite' => 'mass_for_the_dead']
        );
    }
}
