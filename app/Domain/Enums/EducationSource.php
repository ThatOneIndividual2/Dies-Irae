<?php

namespace App\Domain\Enums;

final class EducationSource
{
    public const MONASTERY_SCHOOL = 'monastery_school';
    public const CATHEDRAL_SCHOOL = 'cathedral_school';
    public const NOBLE_HOUSEHOLD = 'noble_household';
    public const MILITARY_HOUSEHOLD = 'military_household';
    public const UNIVERSITY = 'university';
    public const APPRENTICESHIP = 'apprenticeship';

    public static function all(): array
    {
        return [
            self::MONASTERY_SCHOOL,
            self::CATHEDRAL_SCHOOL,
            self::NOBLE_HOUSEHOLD,
            self::MILITARY_HOUSEHOLD,
            self::UNIVERSITY,
            self::APPRENTICESHIP,
        ];
    }
}
