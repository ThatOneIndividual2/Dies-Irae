<?php

namespace App\Domain\Hell\Enums;

final class ActChannel
{
    public const DREAMS = 'dreams';
    public const TEMPTATION = 'temptation';
    public const POSSESSION = 'possession';
    public const CULT_AGENTS = 'cult_agents';
    public const CORRUPTED_NOBLES = 'corrupted_nobles';
    public const CORRUPTED_CLERGY = 'corrupted_clergy';
    public const MANIFESTATIONS = 'manifestations';
    public const INFERNAL_ARMIES = 'infernal_armies';
    public const LOCALIZED_BREACHES = 'localized_breaches';

    public static function all(): array
    {
        return [
            self::DREAMS,
            self::TEMPTATION,
            self::POSSESSION,
            self::CULT_AGENTS,
            self::CORRUPTED_NOBLES,
            self::CORRUPTED_CLERGY,
            self::MANIFESTATIONS,
            self::INFERNAL_ARMIES,
            self::LOCALIZED_BREACHES,
        ];
    }

    public static function isPhysical(string $channel): bool
    {
        return in_array($channel, [self::MANIFESTATIONS, self::INFERNAL_ARMIES, self::LOCALIZED_BREACHES], true);
    }
}
