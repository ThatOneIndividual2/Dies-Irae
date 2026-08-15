<?php

namespace App\Domain\Enums;

final class SacramentKind
{
    public const BAPTISM = 'baptism';
    public const CONFIRMATION = 'confirmation';
    public const EUCHARIST = 'eucharist';
    public const PENANCE = 'penance';
    public const UNCTION = 'unction';
    public const ORDERS = 'orders';
    public const MATRIMONY = 'matrimony';

    public static function all(): array
    {
        return [
            self::BAPTISM,
            self::CONFIRMATION,
            self::EUCHARIST,
            self::PENANCE,
            self::UNCTION,
            self::ORDERS,
            self::MATRIMONY,
        ];
    }
}
