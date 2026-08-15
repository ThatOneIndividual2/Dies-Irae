<?php

namespace App\Domain\Enums;

final class MiracleCategory
{
    public const HEALING = 'healing';
    public const PROTECTION = 'protection';
    public const INCORRUPT_BODY = 'incorrupt_body';
    public const APPARITION = 'apparition';
    public const BATTLEFIELD = 'battlefield';
    public const DELIVERANCE = 'unexplained_deliverance';
    public const PLAGUE_CESSATION = 'plague_cessation';
    public const RELIC_ASSOCIATED = 'relic_associated';

    public static function all(): array
    {
        return [
            self::HEALING,
            self::PROTECTION,
            self::INCORRUPT_BODY,
            self::APPARITION,
            self::BATTLEFIELD,
            self::DELIVERANCE,
            self::PLAGUE_CESSATION,
            self::RELIC_ASSOCIATED,
        ];
    }
}
