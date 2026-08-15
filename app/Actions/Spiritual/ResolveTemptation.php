<?php

namespace App\Actions\Spiritual;

use App\Domain\Spiritual\SpiritualStateService;
use App\Domain\Spiritual\TemptationService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Events\Spiritual\TemptationResolved;
use App\Models\Temptation;
use Carbon\CarbonInterface;

final class ResolveTemptation
{
    public function __construct(
        private TemptationService $temptations,
        private SpiritualStateService $states
    ) {
    }

    public function execute(Temptation $temptation, string $resolution, CarbonInterface $date): Temptation
    {
        return Transactional::run(function () use ($temptation, $resolution, $date) {
            $updated = $this->temptations->resolve($temptation, $resolution, $date, $this->states);
            AfterCommit::dispatch(fn () => event(new TemptationResolved($updated->id, $updated->character_id, $updated->resolution)));

            return $updated;
        });
    }
}
