<?php

namespace App\Domain\Enums;

final class PapacyStatus
{
    public const OCCUPIED = 'occupied';
    public const VACANT = 'vacant';
    public const DISPUTED = 'disputed';
    public const SCHISM = 'schism';

    public static function all(): array
    {
        return [
            self::OCCUPIED,
            self::VACANT,
            self::DISPUTED,
            self::SCHISM,
        ];
    }
}
