<?php

namespace App\Actions\Heresy;

use App\Domain\Heresy\FractureEngine;
use App\Models\Character;
use App\Models\CultCell;
use App\Models\CultMember;
use App\Models\CultOrganization;
use Carbon\CarbonInterface;

final class RecruitCultMember
{
    public function __construct(private FractureEngine $engine)
    {
    }

    public function execute(
        CultOrganization $org,
        Character $character,
        CarbonInterface $date,
        ?CultCell $cell = null,
        string $role = 'initiate'
    ): CultMember {
        return $this->engine->recruitCultMember($org, $character, $date, $cell, $role);
    }
}
