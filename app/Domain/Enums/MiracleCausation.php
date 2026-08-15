<?php

namespace App\Domain\Enums;

final class MiracleCausation
{
    public const UNKNOWN = 'unknown';

    public static function all(): array
    {
        return [self::UNKNOWN];
    }
}
