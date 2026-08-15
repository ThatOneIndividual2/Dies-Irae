<?php

namespace App\Domain\Enums;

final class MiracleStatus
{
    public const CLAIMED = 'claimed';
    public const WITNESSED = 'witnessed';
    public const DISPUTED = 'disputed';
    public const RECOGNIZED = 'recognized';
    public const CONDEMNED = 'condemned';
    public const UNEXPLAINED = 'unexplained';

    public static function all(): array
    {
        return [
            self::CLAIMED,
            self::WITNESSED,
            self::DISPUTED,
            self::RECOGNIZED,
            self::CONDEMNED,
            self::UNEXPLAINED,
        ];
    }
}
