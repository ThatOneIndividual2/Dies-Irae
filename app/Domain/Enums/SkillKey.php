<?php

namespace App\Domain\Enums;

final class SkillKey
{
    public const MARTIAL = 'martial';
    public const STEWARDSHIP = 'stewardship';
    public const DIPLOMACY = 'diplomacy';
    public const INTRIGUE = 'intrigue';
    public const LEARNING = 'learning';
    public const THEOLOGY = 'theology';
    public const MEDICINE = 'medicine';
    public const LEADERSHIP = 'leadership';
    public const PIETY_REPUTATION = 'piety_reputation';

    public static function all(): array
    {
        return [
            self::MARTIAL,
            self::STEWARDSHIP,
            self::DIPLOMACY,
            self::INTRIGUE,
            self::LEARNING,
            self::THEOLOGY,
            self::MEDICINE,
            self::LEADERSHIP,
            self::PIETY_REPUTATION,
        ];
    }

    /**
     * Interior virtues/vices never belong on this list.
     */
    public static function forbiddenAsSkills(): array
    {
        return [
            'faith', 'hope', 'charity',
            'pride', 'greed', 'lust', 'envy', 'gluttony', 'wrath', 'sloth',
            'despair', 'corruption', 'piety',
        ];
    }
}
