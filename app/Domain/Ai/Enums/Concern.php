<?php

namespace App\Domain\Ai\Enums;

final class Concern
{
    public const SURVIVAL = 'survival';
    public const DYNASTY = 'dynasty';
    public const LEGITIMACY = 'legitimacy';
    public const FAITH = 'faith';
    public const AMBITION = 'ambition';
    public const WEALTH = 'wealth';
    public const TERRITORIAL_SECURITY = 'territorial_security';
    public const LOYALTY = 'loyalty';
    public const FEAR = 'fear';
    public const CORRUPTION = 'corruption';
    public const REVENGE = 'revenge';
    public const CHURCH_STANDING = 'church_standing';
    public const PLAGUE_SAFETY = 'plague_safety';
    public const FAMINE = 'famine';
    public const INFERNAL_THREAT = 'infernal_threat';

    public static function all(): array
    {
        return [
            self::SURVIVAL,
            self::DYNASTY,
            self::LEGITIMACY,
            self::FAITH,
            self::AMBITION,
            self::WEALTH,
            self::TERRITORIAL_SECURITY,
            self::LOYALTY,
            self::FEAR,
            self::CORRUPTION,
            self::REVENGE,
            self::CHURCH_STANDING,
            self::PLAGUE_SAFETY,
            self::FAMINE,
            self::INFERNAL_THREAT,
        ];
    }
}
