<?php

namespace App\Domain\Hell\Enums;

final class CountermeasureResult
{
    public const SUCCESS = 'success';
    public const PARTIAL = 'partial';
    public const FAILURE = 'failure';
    public const BACKLASH = 'backlash';

    public static function all(): array
    {
        return [
            self::SUCCESS,
            self::PARTIAL,
            self::FAILURE,
            self::BACKLASH,
        ];
    }
}
