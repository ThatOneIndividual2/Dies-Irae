<?php

namespace App\Domain\Warfare\Enums;

final class WarStatus
{
    public const ACTIVE = 'active';
    public const ENDED = 'ended';

    public static function all(): array
    {
        return [self::ACTIVE, self::ENDED];
    }
}
