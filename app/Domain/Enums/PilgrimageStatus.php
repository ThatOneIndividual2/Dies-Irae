<?php

namespace App\Domain\Enums;

final class PilgrimageStatus
{
    public const EN_ROUTE = 'en_route';
    public const COMPLETED = 'completed';
    public const ABANDONED = 'abandoned';
    public const INTERDICTED = 'interdicted';

    public static function all(): array
    {
        return [self::EN_ROUTE, self::COMPLETED, self::ABANDONED, self::INTERDICTED];
    }
}
