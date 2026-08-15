<?php

namespace App\Domain\Hell\Enums;

final class KnowledgeSource
{
    public const CLERGY = 'clergy';
    public const MANUSCRIPT = 'manuscript';
    public const CONFESSION = 'confession';
    public const INTERROGATION = 'interrogation';
    public const RELIC = 'relic';
    public const VISION = 'vision';
    public const EXORCISM = 'exorcism';
    public const CULT_DOCUMENT = 'cult_document';

    public static function all(): array
    {
        return [
            self::CLERGY,
            self::MANUSCRIPT,
            self::CONFESSION,
            self::INTERROGATION,
            self::RELIC,
            self::VISION,
            self::EXORCISM,
            self::CULT_DOCUMENT,
        ];
    }
}
