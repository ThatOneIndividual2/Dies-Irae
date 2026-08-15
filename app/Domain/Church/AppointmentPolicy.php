<?php

namespace App\Domain\Church;

use App\Domain\Enums\AppointmentMode;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Models\Character;
use App\Models\InvestitureRight;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;

final class AppointmentPolicy
{
    public function modeFor(SpiritualOffice $office): string
    {
        if ($office->appointment_mode) {
            return $office->appointment_mode;
        }

        if ($office->see_id) {
            $right = InvestitureRight::query()
                ->where('see_id', $office->see_id)
                ->where('is_current', true)
                ->first();

            if ($right) {
                return $right->mode;
            }
        }

        $defaults = config('church.appointment_modes', []);

        return $defaults[$office->rank] ?? AppointmentMode::PAPAL;
    }

    public function isVacant(SpiritualOffice $office): bool
    {
        return !SpiritualOfficeHoldership::query()
            ->where('spiritual_office_id', $office->id)
            ->where('is_current', true)
            ->exists();
    }

    public function actorMayAppoint(Character $actor, SpiritualOffice $office, string $mode): bool
    {
        $actorOffice = $this->highestCurrentOffice($actor);

        if ($mode === AppointmentMode::PAPAL || $mode === AppointmentMode::CONCLAVE) {
            return $actorOffice && $actorOffice->rank === SpiritualOfficeRank::POPE;
        }

        if ($mode === AppointmentMode::LOCAL || $mode === AppointmentMode::LAY_INVESTITURE) {
            if ($this->actorHoldsSeeInvestiture($actor, $office)) {
                return true;
            }

            return $actorOffice && in_array($actorOffice->rank, [
                SpiritualOfficeRank::POPE,
                SpiritualOfficeRank::ARCHBISHOP,
                SpiritualOfficeRank::BISHOP,
            ], true);
        }

        if (in_array($mode, [AppointmentMode::ABBATIAL_ELECTION, AppointmentMode::INTERNAL_ELECTION, AppointmentMode::CHAPTER_ELECTION], true)) {
            return true;
        }

        if ($mode === AppointmentMode::CONCURRENT) {
            return $actorOffice !== null || $this->actorHoldsSeeInvestiture($actor, $office);
        }

        return false;
    }

    public function highestCurrentOffice(Character $actor): ?SpiritualOffice
    {
        $holderships = SpiritualOfficeHoldership::query()
            ->where('holder_character_id', $actor->id)
            ->where('is_current', true)
            ->with('office')
            ->get();

        $best = null;
        $bestWeight = -1;

        foreach ($holderships as $row) {
            $office = $row->office;
            if (!$office || !$office->is_active) {
                continue;
            }
            $weight = SpiritualOfficeRank::weight($office->rank);
            if ($weight > $bestWeight) {
                $bestWeight = $weight;
                $best = $office;
            }
        }

        return $best;
    }

    private function actorHoldsSeeInvestiture(Character $actor, SpiritualOffice $office): bool
    {
        if (!$office->see_id) {
            return false;
        }

        return InvestitureRight::query()
            ->where('see_id', $office->see_id)
            ->where('is_current', true)
            ->where('nominator_character_id', $actor->id)
            ->exists();
    }
}
