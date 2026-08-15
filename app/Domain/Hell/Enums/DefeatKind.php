<?php

namespace App\Domain\Hell\Enums;

final class DefeatKind
{
    public const BANISHMENT = 'banishment';
    public const SEVER_LOCAL_INFLUENCE = 'sever_local_influence';
    public const DESTROY_CULT_NETWORK = 'destroy_cult_network';
    public const CLOSE_BREACH = 'close_breach';
    public const DEFEAT_MANIFESTATION = 'defeat_manifestation';
    public const IMPRISON_SERVANT = 'imprison_servant';
    public const PREVENT_OBJECTIVE = 'prevent_objective';
    public const BATTLEFIELD_DEFEAT = 'battlefield_defeat';

    public static function all(): array
    {
        return [
            self::BANISHMENT,
            self::SEVER_LOCAL_INFLUENCE,
            self::DESTROY_CULT_NETWORK,
            self::CLOSE_BREACH,
            self::DEFEAT_MANIFESTATION,
            self::IMPRISON_SERVANT,
            self::PREVENT_OBJECTIVE,
            self::BATTLEFIELD_DEFEAT,
        ];
    }

    public static function isPermanentDestruction(string $kind): bool
    {
        return false;
    }
}
