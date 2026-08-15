<?php

namespace App\Domain\Enums;

final class HolyOrderPapalRecognition
{
    public const NONE = 'none';
    public const RECOGNIZED = 'recognized';
    public const WITHDRAWN = 'withdrawn';
    public const SUPPRESSED = 'suppressed';

    public static function all(): array
    {
        return [
            self::NONE,
            self::RECOGNIZED,
            self::WITHDRAWN,
            self::SUPPRESSED,
        ];
    }

    public static function isLost(string $status): bool
    {
        return in_array($status, [self::WITHDRAWN, self::SUPPRESSED], true);
    }
}
