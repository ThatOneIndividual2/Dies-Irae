<?php

namespace App\Domain\Enums;

final class HolyOrderLegitimacy
{
    public const UNRECOGNIZED = 'unrecognized';
    public const RECOGNIZED = 'recognized';
    public const DISPUTED = 'disputed';
    public const SCHISMATIC = 'schismatic';
    public const SUPPRESSED = 'suppressed';
    public const DISSOLVED = 'dissolved';

    public static function all(): array
    {
        return [
            self::UNRECOGNIZED,
            self::RECOGNIZED,
            self::DISPUTED,
            self::SCHISMATIC,
            self::SUPPRESSED,
            self::DISSOLVED,
        ];
    }

    public static function isFractured(string $status): bool
    {
        return in_array($status, [self::DISPUTED, self::SCHISMATIC], true);
    }
}
