<?php

namespace App\Actions\Spiritual;

use App\Domain\Enums\SacramentType;
use App\Models\Character;
use App\Models\SacramentRecord;
use App\Models\World;
use Carbon\Carbon;

/**
 * Infant baptism is assumed for a Latin Christian lord. It is recorded once,
 * dated to birth, so later rites are not invalid for lack of a font.
 */
final class EnsureHistoricalBaptism
{
    public function __construct(private ConferSacrament $confer)
    {
    }

    public function execute(World $world, Character $subject): ?SacramentRecord
    {
        $existing = SacramentRecord::query()
            ->where('subject_character_id', $subject->id)
            ->where('sacrament_type', SacramentType::BAPTISM)
            ->whereIn('validity', ['valid', 'valid_illicit'])
            ->first();
        if ($existing) {
            return $existing;
        }

        $when = $subject->birth_date
            ? Carbon::parse($subject->birth_date)
            : Carbon::parse($world->current_date);

        return $this->confer->execute(
            $world,
            SacramentType::BAPTISM,
            $subject,
            $when,
            null,
            null,
            null,
            null,
            null,
            ['historical' => true, 'reason' => 'infant_baptism']
        );
    }
}
