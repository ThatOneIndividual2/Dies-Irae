<?php

namespace App\Domain\Enums;

final class SpiritualActType
{
    public const MURDER = 'murder';
    public const BETRAYAL = 'betrayal';
    public const MERCY = 'mercy';
    public const ALMSGIVING = 'almsgiving';
    public const ADULTERY = 'adultery';
    public const SACRILEGE = 'sacrilege';
    public const OATH_BREAKING = 'oath_breaking';
    public const CHARITY = 'charity';
    public const FASTING = 'fasting';
    public const PILGRIMAGE = 'pilgrimage';
    public const CONFESSION = 'confession';
    public const DESECRATION = 'desecration';
    public const COWARDICE = 'cowardice';
    public const MARTYRDOM = 'martyrdom';

    public static function all(): array
    {
        return [
            self::MURDER,
            self::BETRAYAL,
            self::MERCY,
            self::ALMSGIVING,
            self::ADULTERY,
            self::SACRILEGE,
            self::OATH_BREAKING,
            self::CHARITY,
            self::FASTING,
            self::PILGRIMAGE,
            self::CONFESSION,
            self::DESECRATION,
            self::COWARDICE,
            self::MARTYRDOM,
        ];
    }
}
