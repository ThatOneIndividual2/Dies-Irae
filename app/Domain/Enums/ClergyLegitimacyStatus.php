<?php

namespace App\Domain\Enums;

final class ClergyLegitimacyStatus
{
    public const RECOGNIZED = 'recognized';
    public const DISPUTED = 'disputed';
    public const SCHISMATIC = 'schismatic';
    public const IRREGULAR = 'irregular';

    public static function all(): array
    {
        return [
            self::RECOGNIZED,
            self::DISPUTED,
            self::SCHISMATIC,
            self::IRREGULAR,
        ];
    }
}
