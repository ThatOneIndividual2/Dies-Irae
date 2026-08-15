<?php

namespace App\Domain\Enums;

final class PapalClaimStatus
{
    public const PENDING = 'pending';
    public const RECOGNIZED = 'recognized';
    public const ANTIPOPE = 'antipope';
    public const SCHISMATIC = 'schismatic';
    public const REJECTED = 'rejected';

    public static function all(): array
    {
        return [
            self::PENDING,
            self::RECOGNIZED,
            self::ANTIPOPE,
            self::SCHISMATIC,
            self::REJECTED,
        ];
    }
}
