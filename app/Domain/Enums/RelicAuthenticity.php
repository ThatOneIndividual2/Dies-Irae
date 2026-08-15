<?php

namespace App\Domain\Enums;

final class RelicAuthenticity
{
    public const UNRECOGNIZED = 'unrecognized';
    public const RECOGNIZED = 'recognized';
    public const DISPUTED = 'disputed';
    public const CONDEMNED = 'condemned';

    public static function all(): array
    {
        return [
            self::UNRECOGNIZED,
            self::RECOGNIZED,
            self::DISPUTED,
            self::CONDEMNED,
        ];
    }
}
