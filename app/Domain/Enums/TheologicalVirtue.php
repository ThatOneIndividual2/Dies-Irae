<?php

namespace App\Domain\Enums;

final class TheologicalVirtue
{
    public const FAITH = 'faith';
    public const HOPE = 'hope';
    public const CHARITY = 'charity';

    public static function all(): array
    {
        return [self::FAITH, self::HOPE, self::CHARITY];
    }
}
