<?php

namespace App\Domain\Hell\Enums;

final class ObjectiveStatus
{
    public const OPEN = 'open';
    public const ADVANCED = 'advanced';
    public const PREVENTED = 'prevented';
    public const COMPLETED = 'completed';

    public static function all(): array
    {
        return [self::OPEN, self::ADVANCED, self::PREVENTED, self::COMPLETED];
    }
}
