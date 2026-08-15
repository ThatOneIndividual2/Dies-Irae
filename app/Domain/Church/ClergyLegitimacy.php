<?php

namespace App\Domain\Church;

use App\Domain\Enums\ClergyLegitimacyStatus;
use App\Domain\Enums\ReligiousState;
use App\Models\Character;
use App\Models\ClergyStatus;
use App\Models\ExcommunicationState;
use App\Models\SpiritualOfficeHoldership;

final class ClergyLegitimacy
{
    public function currentStatus(Character $character): ?ClergyStatus
    {
        return ClergyStatus::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();
    }

    public function isInHolyOrders(Character $character): bool
    {
        $status = $this->currentStatus($character);

        return $status !== null && $status->religious_state !== ReligiousState::LAICIZED
            && $status->religious_state !== ReligiousState::NONE;
    }

    public function isRegular(Character $character): bool
    {
        $status = $this->currentStatus($character);

        return $status !== null && $status->religious_state === ReligiousState::REGULAR;
    }

    public function isExcommunicated(Character $character): bool
    {
        return ExcommunicationState::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->exists();
    }

    public function holdershipIsRecognized(?SpiritualOfficeHoldership $holdership): bool
    {
        if (!$holdership || !$holdership->is_current) {
            return false;
        }

        return $holdership->legitimacy === ClergyLegitimacyStatus::RECOGNIZED;
    }

    public function canExerciseOffice(Character $character, SpiritualOfficeHoldership $holdership): bool
    {
        if (!$this->holdershipIsRecognized($holdership)) {
            return false;
        }

        if ($this->isExcommunicated($character)) {
            return false;
        }

        $status = $this->currentStatus($character);
        if ($status && $status->regularity === 'irregular') {
            return false;
        }

        return true;
    }
}
