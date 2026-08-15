<?php

namespace App\Domain\Enums;

final class AppointmentStatus
{
    public const PENDING = 'pending';
    public const RECOGNIZED = 'recognized';
    public const DISPUTED = 'disputed';
    public const REJECTED = 'rejected';
    public const SUPERSEDED = 'superseded';

    public static function all(): array
    {
        return [
            self::PENDING,
            self::RECOGNIZED,
            self::DISPUTED,
            self::REJECTED,
            self::SUPERSEDED,
        ];
    }
}
