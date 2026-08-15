<?php

namespace App\Domain\Enums;

final class GrainMoveMode
{
    public const PURCHASE = 'purchase';
    public const REQUISITION = 'requisition';
    public const DONATION = 'donation';
    public const RELIEF = 'relief';
    public const SEIZURE = 'seizure';
    public const ALMS = 'alms';

    public static function all(): array
    {
        return [
            self::PURCHASE,
            self::REQUISITION,
            self::DONATION,
            self::RELIEF,
            self::SEIZURE,
            self::ALMS,
        ];
    }

    public static function isChurch(string $mode): bool
    {
        return in_array($mode, [self::ALMS, self::RELIEF, self::DONATION], true);
    }

    public static function isCoercive(string $mode): bool
    {
        return in_array($mode, [self::REQUISITION, self::SEIZURE], true);
    }
}
