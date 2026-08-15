<?php

namespace App\Domain\Enums;

final class HolyOrderHouseType
{
    public const HEADQUARTERS = 'headquarters';
    public const COMMANDERY = 'commandery';
    public const PRECEPTORY = 'preceptory';
    public const HOSPITAL = 'hospital';

    public static function all(): array
    {
        return [self::HEADQUARTERS, self::COMMANDERY, self::PRECEPTORY, self::HOSPITAL];
    }
}
