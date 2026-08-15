<?php

namespace App\Domain\Enums;

final class CanonicalCensure
{
    public const NONE = 'none';
    public const EXCOMMUNICATION = 'excommunication';
    public const INTERDICT_EFFECT = 'interdict_effect';

    public static function all(): array
    {
        return [self::NONE, self::EXCOMMUNICATION, self::INTERDICT_EFFECT];
    }

    public static function isPublic(string $censure): bool
    {
        return $censure !== self::NONE;
    }

    public static function barsCommunion(string $censure): bool
    {
        return in_array($censure, [self::EXCOMMUNICATION, self::INTERDICT_EFFECT], true);
    }
}
