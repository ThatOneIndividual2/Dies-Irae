<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\SacramentService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\Spiritual\SacramentConferred;
use App\Models\Character;
use App\Models\SacramentRecord;
use App\Models\World;
use Carbon\CarbonInterface;

final class ConferSacrament
{
    public function __construct(private SacramentService $sacraments)
    {
    }

    public function execute(
        World $world,
        string $sacramentType,
        Character $subject,
        CarbonInterface $date,
        ?Character $minister = null,
        ?Character $spouse = null,
        ?string $placeType = null,
        ?int $placeId = null,
        ?string $ordersGrade = null,
        ?array $metadata = null
    ): SacramentRecord {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $subject->world_id, 'sacrament subject');
        if ($minister) {
            WorldBoundary::assertSameWorld((int) $world->id, (int) $minister->world_id, 'sacrament minister');
        }
        if ($spouse) {
            WorldBoundary::assertSameWorld((int) $world->id, (int) $spouse->world_id, 'sacrament spouse');
        }

        return Transactional::run(function () use (
            $world, $sacramentType, $subject, $date, $minister, $spouse, $placeType, $placeId, $ordersGrade, $metadata
        ) {
            $record = $this->sacraments->confer(
                $world,
                $sacramentType,
                $subject,
                $date,
                $minister,
                $spouse,
                $placeType,
                $placeId,
                $ordersGrade,
                $metadata
            );

            AfterCommit::dispatch(fn () => event(new SacramentConferred(
                $record->id,
                $record->subject_character_id,
                $record->sacrament_type,
                $record->validity
            )));

            return $record;
        });
    }
}
