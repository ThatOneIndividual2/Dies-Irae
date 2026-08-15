<?php

namespace App\Domain\Enums;

final class SacramentType
{
    public const BAPTISM = 'baptism';
    public const CONFESSION = 'confession';
    public const EUCHARIST = 'eucharist';
    public const CONFIRMATION = 'confirmation';
    public const MATRIMONY = 'matrimony';
    public const HOLY_ORDERS = 'holy_orders';
    public const ANOINTING = 'anointing';

    public static function all(): array
    {
        return [
            self::BAPTISM,
            self::CONFESSION,
            self::EUCHARIST,
            self::CONFIRMATION,
            self::MATRIMONY,
            self::HOLY_ORDERS,
            self::ANOINTING,
        ];
    }

    public static function onceOnlyValid(): array
    {
        return [self::BAPTISM, self::CONFIRMATION];
    }
}
