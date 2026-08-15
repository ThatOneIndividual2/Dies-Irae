<?php

namespace App\Domain\Enums;

final class SocialClass
{
    public const NOBLES = 'nobles';
    public const CLERGY = 'clergy';
    public const BURGHERS = 'burghers';
    public const PEASANTS = 'peasants';
    public const UNFREE = 'unfree';

    public static function all(): array
    {
        return [
            self::NOBLES,
            self::CLERGY,
            self::BURGHERS,
            self::PEASANTS,
            self::UNFREE,
        ];
    }
}
