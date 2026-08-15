<?php

namespace App\Domain\Ai\Enums;

final class CrisisKind
{
    public const PLAGUE = 'plague';
    public const FAMINE = 'famine';
    public const WAR = 'war';
    public const SUCCESSION = 'succession_crisis';
    public const HERESY = 'heresy';
    public const CULT_DISCOVERY = 'cult_discovery';
    public const DEMONIC_INCURSION = 'demonic_incursion';
    public const PAPAL_CONFLICT = 'papal_conflict';
    public const LOCAL_COLLAPSE = 'local_collapse';

    public static function all(): array
    {
        return [
            self::PLAGUE,
            self::FAMINE,
            self::WAR,
            self::SUCCESSION,
            self::HERESY,
            self::CULT_DISCOVERY,
            self::DEMONIC_INCURSION,
            self::PAPAL_CONFLICT,
            self::LOCAL_COLLAPSE,
        ];
    }
}
