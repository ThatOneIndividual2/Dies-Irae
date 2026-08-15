<?php

namespace App\Domain\Church;

use App\Domain\Enums\ClergyGrade;
use App\Domain\Enums\ReligiousState;
use App\Domain\Succession\SuccessionEligibilityGate;
use App\Models\Character;
use App\Models\Title;

final class ChurchEligibilityGate implements SuccessionEligibilityGate
{
    public function __construct(private ClergyLegitimacy $legitimacy)
    {
    }

    public function isEligible(Character $character, Title $title): bool
    {
        return $this->ineligibilityReason($character, $title) === null;
    }

    public function ineligibilityReason(Character $character, Title $title): ?string
    {
        $policy = config('church.secular_succession', []);
        $status = $this->legitimacy->currentStatus($character);

        if (($policy['block_if_excommunicated'] ?? false) && $this->legitimacy->isExcommunicated($character)) {
            return 'excommunicated';
        }

        if ($status && ($policy['block_if_regular'] ?? false) && $status->religious_state === ReligiousState::REGULAR) {
            return 'regular_vows';
        }

        if ($status && ($policy['block_if_major_orders'] ?? false)) {
            $grade = $status->orders_grade ?? ClergyGrade::NONE;
            if (ClergyGrade::isMajor($grade)) {
                return 'major_orders';
            }
        }

        return null;
    }
}
