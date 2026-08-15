<?php

namespace App\Domain\Enums;

final class TemptationResolution
{
    public const OPEN = 'open';
    public const RESISTED = 'resisted';
    public const YIELDED = 'yielded';
    public const EXPIRED = 'expired';

    public static function all(): array
    {
        return [self::OPEN, self::RESISTED, self::YIELDED, self::EXPIRED];
    }
}
