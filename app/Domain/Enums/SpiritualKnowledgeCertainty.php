<?php

namespace App\Domain\Enums;

final class SpiritualKnowledgeCertainty
{
    public const RUMOR = 'rumor';
    public const INFERRED = 'inferred';
    public const WITNESSED = 'witnessed';
    public const CONFESSED = 'confessed';
    public const OFFICIAL = 'official';

    public static function all(): array
    {
        return [self::RUMOR, self::INFERRED, self::WITNESSED, self::CONFESSED, self::OFFICIAL];
    }
}
