<?php

namespace App\Domain\Enums;

final class DemonicInfluenceStage
{
    public const TEMPTATION = 'temptation';
    public const OPPRESSION = 'oppression';
    public const OBSESSION = 'obsession';
    public const POSSESSION = 'possession';
    public const PACT = 'pact';

    public static function all(): array
    {
        return [
            self::TEMPTATION,
            self::OPPRESSION,
            self::OBSESSION,
            self::POSSESSION,
            self::PACT,
        ];
    }

    public static function weight(string $stage): int
    {
        return match ($stage) {
            self::PACT => 50,
            self::POSSESSION => 40,
            self::OBSESSION => 30,
            self::OPPRESSION => 20,
            self::TEMPTATION => 10,
            default => 0,
        };
    }

    public static function publiclyInferable(string $stage): bool
    {
        return in_array($stage, [self::POSSESSION, self::PACT], true);
    }
}
