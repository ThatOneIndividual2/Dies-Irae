<?php

namespace App\Domain\Warfare\Enums;

final class SanctityState
{
    public const ORDINARY = 'ordinary';
    public const CONSECRATED = 'consecrated';
    public const PROFANED = 'profaned';
    public const DESECRATED = 'desecrated';
    public const HALLOWED_RUIN = 'hallowed_ruin';

    public static function all(): array
    {
        return [
            self::ORDINARY,
            self::CONSECRATED,
            self::PROFANED,
            self::DESECRATED,
            self::HALLOWED_RUIN,
        ];
    }
}
