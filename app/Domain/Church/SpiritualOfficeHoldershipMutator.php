<?php

namespace App\Domain\Church;

use App\Domain\Enums\ClergyLegitimacyStatus;
use App\Domain\Enums\OfficeAcquisitionType;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\SpiritualOfficeAppointed;
use App\Events\SpiritualOfficeRemoved;
use App\Models\Character;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use RuntimeException;

final class SpiritualOfficeHoldershipMutator
{
    public function appoint(
        SpiritualOffice $office,
        Character $holder,
        CarbonInterface $date,
        string $acquisitionType = OfficeAcquisitionType::APPOINTMENT,
        ?Character $appointedBy = null,
        string $legitimacy = ClergyLegitimacyStatus::RECOGNIZED,
        ?int $appointmentId = null
    ): SpiritualOfficeHoldership {
        if (!in_array($acquisitionType, OfficeAcquisitionType::all(), true)) {
            throw new InvalidArgumentException("Unknown office acquisition type: {$acquisitionType}");
        }

        if (!in_array($legitimacy, ClergyLegitimacyStatus::all(), true)) {
            throw new InvalidArgumentException("Unknown clergy legitimacy: {$legitimacy}");
        }

        return Transactional::run(function () use ($office, $holder, $date, $acquisitionType, $appointedBy, $legitimacy, $appointmentId) {
            $locked = SpiritualOffice::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();

            WorldBoundary::assertSameWorldEntities('spiritual office appoint', $locked, $holder);
            if ($appointedBy) {
                WorldBoundary::assertSameWorldEntities('spiritual office appointor', $locked, $appointedBy);
            }

            $current = SpiritualOfficeHoldership::query()
                ->where('spiritual_office_id', $locked->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            $previousId = null;
            if ($current) {
                if ((int) $current->holder_character_id === (int) $holder->id) {
                    throw new RuntimeException('Character already holds this spiritual office.');
                }
                $current->lost_date = $date->toDateString();
                $current->is_current = null;
                $current->save();
                $previousId = $current->id;
            }

            $holdership = SpiritualOfficeHoldership::query()->create([
                'world_id' => $locked->world_id,
                'spiritual_office_id' => $locked->id,
                'holder_character_id' => $holder->id,
                'acquired_date' => $date->toDateString(),
                'lost_date' => null,
                'acquisition_type' => $acquisitionType,
                'legitimacy' => $legitimacy,
                'appointed_by_character_id' => $appointedBy?->id,
                'previous_holdership_id' => $previousId,
                'appointment_id' => $appointmentId,
                'is_current' => true,
            ]);

            $activeCount = SpiritualOfficeHoldership::query()
                ->where('spiritual_office_id', $locked->id)
                ->where('is_current', true)
                ->count();

            if ($activeCount !== 1) {
                throw new RuntimeException('Spiritual office must have exactly one active holder after appointment.');
            }

            AfterCommit::dispatch(function () use ($locked, $holder, $holdership, $appointedBy, $date) {
                event(new SpiritualOfficeAppointed(
                    $locked->id,
                    $holder->id,
                    $holdership->id,
                    $appointedBy?->id,
                    $date->toDateString()
                ));
            });

            return $holdership;
        });
    }

    public function remove(SpiritualOffice $office, CarbonInterface $date, ?Character $removedBy = null): ?SpiritualOfficeHoldership
    {
        return Transactional::run(function () use ($office, $date, $removedBy) {
            $locked = SpiritualOffice::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            $current = SpiritualOfficeHoldership::query()
                ->where('spiritual_office_id', $locked->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if (!$current) {
                return null;
            }

            if ($removedBy) {
                WorldBoundary::assertSameWorldEntities('spiritual office remove', $locked, $removedBy);
            }

            $previousHolderId = $current->holder_character_id;
            $current->lost_date = $date->toDateString();
            $current->is_current = null;
            $current->save();

            AfterCommit::dispatch(function () use ($locked, $previousHolderId, $removedBy, $date) {
                event(new SpiritualOfficeRemoved(
                    $locked->id,
                    $previousHolderId,
                    $removedBy?->id,
                    $date->toDateString()
                ));
            });

            return $current->fresh();
        });
    }
}
