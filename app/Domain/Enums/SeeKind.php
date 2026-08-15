<?php

namespace App\Domain\Enums;

final class SeeKind
{
    public const PAPAL_SEE = 'papal_see';
    public const PATRIARCHATE = 'patriarchate';
    public const ARCHDIOCESE = 'archdiocese';
    public const DIOCESE = 'diocese';
    public const PARISH = 'parish';
    public const EXEMPT_ABBEY = 'exempt_abbey';
    public const PECULIAR = 'peculiar';

    public static function all(): array
    {
        return [
            self::PAPAL_SEE,
            self::PATRIARCHATE,
            self::ARCHDIOCESE,
            self::DIOCESE,
            self::PARISH,
            self::EXEMPT_ABBEY,
            self::PECULIAR,
        ];
    }
}
