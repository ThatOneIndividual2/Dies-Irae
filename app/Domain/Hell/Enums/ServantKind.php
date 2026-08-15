<?php

namespace App\Domain\Hell\Enums;

final class ServantKind
{
    public const CULT_AGENT = 'cult_agent';
    public const CORRUPTED_NOBLE = 'corrupted_noble';
    public const CORRUPTED_CLERGY = 'corrupted_clergy';
    public const POSSESSED = 'possessed';

    public static function all(): array
    {
        return [self::CULT_AGENT, self::CORRUPTED_NOBLE, self::CORRUPTED_CLERGY, self::POSSESSED];
    }
}
