<?php

namespace App\Domain\Enums;

final class SaintRecognitionStatus
{
    public const CAUSE_OPENED = 'cause_opened';
    public const UNDER_INQUIRY = 'under_inquiry';
    public const CULTUS = 'cultus';
    public const CANONIZED = 'canonized';
    public const REJECTED = 'rejected';

    public static function all(): array
    {
        return [
            self::CAUSE_OPENED,
            self::UNDER_INQUIRY,
            self::CULTUS,
            self::CANONIZED,
            self::REJECTED,
        ];
    }

    public static function isFormal(string $status): bool
    {
        return $status === self::CANONIZED;
    }
}
