<?php

namespace App\Actions\Church;

use App\Domain\Church\ChurchAuthority;
use App\Models\Character;
use App\Models\ExtraordinarySpiritualAuthorization;
use Carbon\CarbonInterface;

final class AuthorizeExtraordinarySpiritualAction
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function execute(
        Character $actor,
        Character $subject,
        string $actionKind,
        CarbonInterface $date,
        ?CarbonInterface $expires = null
    ): ExtraordinarySpiritualAuthorization {
        return $this->church->authorizeExtraordinaryAction($actor, $subject, $actionKind, $date, $expires);
    }
}
