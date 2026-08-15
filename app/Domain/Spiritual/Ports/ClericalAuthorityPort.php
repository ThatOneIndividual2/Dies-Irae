<?php

namespace App\Domain\Spiritual\Ports;

use App\Domain\Enums\SacramentType;
use App\Models\Character;

interface ClericalAuthorityPort
{
    public function isCleric(Character $character): bool;

    public function holyOrdersGrade(Character $character): string;

    public function mayMinisterSacrament(Character $minister, string $sacramentType): bool;

    public function mayHearConfession(Character $minister, Character $penitent): bool;

    public function hasJurisdiction(Character $cleric, Character $subject): bool;

    public function isLicensedExorcist(Character $cleric): bool;
}
