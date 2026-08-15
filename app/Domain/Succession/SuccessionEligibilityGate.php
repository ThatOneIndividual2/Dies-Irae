<?php

namespace App\Domain\Succession;

use App\Models\Character;
use App\Models\Title;

interface SuccessionEligibilityGate
{
    public function isEligible(Character $character, Title $title): bool;

    public function ineligibilityReason(Character $character, Title $title): ?string;
}
