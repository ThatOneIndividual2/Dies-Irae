<?php

namespace App\Domain\Spiritual\Ports;

use App\Domain\Enums\HolyOrdersGrade;
use App\Domain\Enums\SacramentType;
use App\Models\Character;

/**
 * Test and bootstrap stand-in. Church domain should bind a real implementation
 * that reads spiritual offices and faculties, not this map.
 */
final class InMemoryClericalAuthority implements ClericalAuthorityPort
{
    /** @var array<int, array{grade:string, jurisdiction:bool, exorcist:bool, faculties:array}> */
    private array $clerics = [];

    public function grant(
        Character $character,
        string $grade = HolyOrdersGrade::PRIEST,
        bool $jurisdiction = true,
        bool $exorcist = false,
        ?array $faculties = null
    ): void {
        $this->clerics[(int) $character->id] = [
            'grade' => $grade,
            'jurisdiction' => $jurisdiction,
            'exorcist' => $exorcist,
            'faculties' => $faculties ?? SacramentType::all(),
        ];
    }

    public function isCleric(Character $character): bool
    {
        return isset($this->clerics[(int) $character->id]);
    }

    public function holyOrdersGrade(Character $character): string
    {
        return $this->clerics[(int) $character->id]['grade'] ?? HolyOrdersGrade::NONE;
    }

    public function mayMinisterSacrament(Character $minister, string $sacramentType): bool
    {
        $entry = $this->clerics[(int) $minister->id] ?? null;
        if ($entry === null) {
            return false;
        }

        if (!in_array($sacramentType, $entry['faculties'], true)) {
            return false;
        }

        $grade = $entry['grade'];

        return match ($sacramentType) {
            SacramentType::BAPTISM => HolyOrdersGrade::weight($grade) >= HolyOrdersGrade::weight(HolyOrdersGrade::DEACON),
            SacramentType::CONFESSION, SacramentType::EUCHARIST, SacramentType::ANOINTING, SacramentType::MATRIMONY => HolyOrdersGrade::mayMinisterEucharist($grade),
            SacramentType::CONFIRMATION, SacramentType::HOLY_ORDERS => HolyOrdersGrade::mayOrdain($grade),
            default => false,
        };
    }

    public function mayHearConfession(Character $minister, Character $penitent): bool
    {
        return $this->mayMinisterSacrament($minister, SacramentType::CONFESSION)
            && $this->hasJurisdiction($minister, $penitent);
    }

    public function hasJurisdiction(Character $cleric, Character $subject): bool
    {
        $entry = $this->clerics[(int) $cleric->id] ?? null;
        if ($entry === null) {
            return false;
        }

        return (bool) $entry['jurisdiction'];
    }

    public function isLicensedExorcist(Character $cleric): bool
    {
        return (bool) ($this->clerics[(int) $cleric->id]['exorcist'] ?? false);
    }
}
