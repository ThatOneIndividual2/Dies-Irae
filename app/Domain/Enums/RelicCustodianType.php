<?php

namespace App\Domain\Enums;

final class RelicCustodianType
{
    public const MONASTERY = 'monastery';
    public const CATHEDRAL = 'cathedral';
    public const BISHOP = 'bishop';
    public const RULER = 'ruler';
    public const HOLY_ORDER = 'holy_order';
    public const SETTLEMENT_INSTITUTION = 'settlement_institution';
    public const PRIVATE_CHARACTER = 'private_character';

    public static function all(): array
    {
        return [
            self::MONASTERY,
            self::CATHEDRAL,
            self::BISHOP,
            self::RULER,
            self::HOLY_ORDER,
            self::SETTLEMENT_INSTITUTION,
            self::PRIVATE_CHARACTER,
        ];
    }
}
