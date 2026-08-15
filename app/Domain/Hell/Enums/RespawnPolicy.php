<?php

namespace App\Domain\Hell\Enums;

/**
 * Destruction is sticky. Pulse never revives a named demon.
 * Return requires an explicit action that this policy may still refuse.
 */
final class RespawnPolicy
{
    public const NEVER = 'never';
    public const LORE_ONLY = 'lore_only';
    public const APOCALYPSE_STAGE = 'apocalypse_stage';
    public const RITUAL = 'ritual';

    public static function all(): array
    {
        return [
            self::NEVER,
            self::LORE_ONLY,
            self::APOCALYPSE_STAGE,
            self::RITUAL,
        ];
    }
}
