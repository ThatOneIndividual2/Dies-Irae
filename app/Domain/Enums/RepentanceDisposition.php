<?php

namespace App\Domain\Enums;

final class RepentanceDisposition
{
    public const NONE = 'none';
    public const STIRRING = 'stirring';
    public const CONTRITE = 'contrite';
    public const PERFORMING_PENANCE = 'performing_penance';
    public const RELAPSED = 'relapsed';

    public static function all(): array
    {
        return [self::NONE, self::STIRRING, self::CONTRITE, self::PERFORMING_PENANCE, self::RELAPSED];
    }
}
