<?php

namespace App\Domain\Hell\Enums;

final class HostKind
{
    public const DEMONIC_HOST = 'demonic_host';
    public const CORRUPTED_ARMY = 'corrupted_army';

    public static function all(): array
    {
        return [
            self::DEMONIC_HOST,
            self::CORRUPTED_ARMY,
        ];
    }
}
