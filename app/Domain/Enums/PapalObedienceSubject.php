<?php

namespace App\Domain\Enums;

final class PapalObedienceSubject
{
    public const CHARACTER = 'character';
    public const SEE = 'see';
    public const REALM = 'realm';
    public const ELECTOR = 'elector';

    public static function all(): array
    {
        return [
            self::CHARACTER,
            self::SEE,
            self::REALM,
            self::ELECTOR,
        ];
    }
}
