<?php

namespace App\Domain\Enums;

final class HolyOrderMissionType
{
    public const DEFEND_PILGRIMAGE = 'defend_pilgrimage';
    public const FIGHT_HERETICS = 'fight_heretics';
    public const FIGHT_CULTS = 'fight_cults';
    public const FIGHT_DEMONS = 'fight_demons';
    public const SERVE_POPE = 'serve_pope';
    public const SERVE_KING = 'serve_king';

    public static function all(): array
    {
        return [
            self::DEFEND_PILGRIMAGE,
            self::FIGHT_HERETICS,
            self::FIGHT_CULTS,
            self::FIGHT_DEMONS,
            self::SERVE_POPE,
            self::SERVE_KING,
        ];
    }
}
