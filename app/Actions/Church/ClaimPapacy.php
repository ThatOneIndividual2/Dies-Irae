<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Domain\Enums\PapalClaimStatus;
use App\Models\Character;
use App\Models\Papacy;
use App\Models\PapalClaim;
use App\Models\SpiritualOffice;
use Carbon\CarbonInterface;

final class ClaimPapacy
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(
        Character $claimant,
        Papacy $papacy,
        CarbonInterface $date,
        string $status = PapalClaimStatus::ANTIPOPE,
        ?SpiritualOffice $claimantOffice = null
    ): PapalClaim {
        return $this->church->claimPapacy($claimant, $papacy, $date, $status, $claimantOffice);
    }
}
