<?php

namespace App\Domain\Enums;

final class SaintEvidenceType
{
    public const MARTYRDOM = 'martyrdom';
    public const REPUTATION = 'reputation_of_holiness';
    public const MIRACLE_CLAIM = 'miracle_claim';
    public const RELIC = 'relic';
    public const INCORRUPT_BODY = 'incorrupt_body';
    public const LOCAL_CULT = 'local_cult';
    public const VITA = 'written_vita';

    public static function all(): array
    {
        return [
            self::MARTYRDOM,
            self::REPUTATION,
            self::MIRACLE_CLAIM,
            self::RELIC,
            self::INCORRUPT_BODY,
            self::LOCAL_CULT,
            self::VITA,
        ];
    }
}
