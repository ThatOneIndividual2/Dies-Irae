<?php

namespace App\Domain\Enums;

final class AcquisitionType
{
    public const GRANT = 'grant';
    public const CREATION = 'creation';
    public const TRANSFER = 'transfer';
    public const REVOCATION = 'revocation';
    public const SUCCESSION = 'succession';
    public const USURPATION = 'usurpation';

    public static function all(): array
    {
        return [
            self::GRANT,
            self::CREATION,
            self::TRANSFER,
            self::REVOCATION,
            self::SUCCESSION,
            self::USURPATION,
        ];
    }
}
