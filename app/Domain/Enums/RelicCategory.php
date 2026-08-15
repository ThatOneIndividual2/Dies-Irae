<?php

namespace App\Domain\Enums;

final class RelicCategory
{
    public const BODILY = 'bodily';
    public const ASSOCIATED_OBJECT = 'associated_object';
    public const SACRED_OBJECT = 'sacred_object';
    public const LOCAL_VENERATION = 'local_veneration';

    public static function all(): array
    {
        return [
            self::BODILY,
            self::ASSOCIATED_OBJECT,
            self::SACRED_OBJECT,
            self::LOCAL_VENERATION,
        ];
    }
}
