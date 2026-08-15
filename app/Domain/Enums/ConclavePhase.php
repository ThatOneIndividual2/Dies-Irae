<?php

namespace App\Domain\Enums;

final class ConclavePhase
{
    public const VACANT = 'vacant';
    public const ASSEMBLING = 'assembling';
    public const VOTING = 'voting';
    public const DEADLOCK = 'deadlock';
    public const ELECTED = 'elected';
    public const ACCEPTED = 'accepted';
    public const ENTHRONED = 'enthroned';
    public const CONTESTED = 'contested';

    public static function all(): array
    {
        return [
            self::VACANT,
            self::ASSEMBLING,
            self::VOTING,
            self::DEADLOCK,
            self::ELECTED,
            self::ACCEPTED,
            self::ENTHRONED,
            self::CONTESTED,
        ];
    }
}
