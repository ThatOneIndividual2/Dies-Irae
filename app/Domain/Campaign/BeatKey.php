<?php

namespace App\Domain\Campaign;

final class BeatKey
{
    public const PLAGUE_APPEARS = 'plague_appears';
    public const REFUGEES_ARRIVE = 'refugees_arrive';
    public const NOBLE_REFUSES_AID = 'noble_refuses_aid';
    public const MONASTERY_REQUESTS_RESOURCES = 'monastery_requests_resources';
    public const RUMORS_OF_HERESY = 'rumors_of_heresy';
    public const CULT_DISCOVERY = 'cult_discovery';
    public const DEMONIC_MANIFESTATION = 'demonic_manifestation';
    public const CLERGY_RESPONSE = 'clergy_response';
    public const MILITARY_RESPONSE = 'military_response';
    public const AFTERMATH = 'aftermath';

    public static function sequence(): array
    {
        return [
            self::PLAGUE_APPEARS,
            self::REFUGEES_ARRIVE,
            self::NOBLE_REFUSES_AID,
            self::MONASTERY_REQUESTS_RESOURCES,
            self::RUMORS_OF_HERESY,
            self::CULT_DISCOVERY,
            self::DEMONIC_MANIFESTATION,
            self::CLERGY_RESPONSE,
            self::MILITARY_RESPONSE,
            self::AFTERMATH,
        ];
    }

    public static function dayOffset(string $key): int
    {
        return match ($key) {
            self::PLAGUE_APPEARS => 1,
            self::REFUGEES_ARRIVE => 3,
            self::NOBLE_REFUSES_AID => 4,
            self::MONASTERY_REQUESTS_RESOURCES => 5,
            self::RUMORS_OF_HERESY => 7,
            self::CULT_DISCOVERY => 8,
            self::DEMONIC_MANIFESTATION => 10,
            self::CLERGY_RESPONSE => 11,
            self::MILITARY_RESPONSE => 12,
            self::AFTERMATH => 14,
            default => 0,
        };
    }
}
