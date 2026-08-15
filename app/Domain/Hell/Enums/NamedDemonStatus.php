<?php

namespace App\Domain\Hell\Enums;

final class NamedDemonStatus
{
    public const LATENT = 'latent';
    public const MANIFESTED = 'manifested';
    public const BOUND = 'bound';
    public const BANISHED = 'banished';
    public const DESTROYED = 'destroyed';

    public static function all(): array
    {
        return [
            self::LATENT,
            self::MANIFESTED,
            self::BOUND,
            self::BANISHED,
            self::DESTROYED,
        ];
    }

    public static function isPresentOnMap(string $status): bool
    {
        return in_array($status, [self::MANIFESTED, self::BOUND], true);
    }
}
