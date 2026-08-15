<?php

namespace App\Domain\Enums;

final class AppointmentMode
{
    public const PAPAL = 'papal';
    public const LOCAL = 'local';
    public const CHAPTER_ELECTION = 'chapter_election';
    public const ABBATIAL_ELECTION = 'abbatial_election';
    public const CONCLAVE = 'conclave';
    public const INTERNAL_ELECTION = 'internal_election';
    public const LAY_INVESTITURE = 'lay_investiture';
    public const CONCURRENT = 'concurrent';
    public const DISPUTED = 'disputed';
    public const VACANCY = 'vacancy';

    public static function all(): array
    {
        return [
            self::PAPAL,
            self::LOCAL,
            self::CHAPTER_ELECTION,
            self::ABBATIAL_ELECTION,
            self::CONCLAVE,
            self::INTERNAL_ELECTION,
            self::LAY_INVESTITURE,
            self::CONCURRENT,
            self::DISPUTED,
            self::VACANCY,
        ];
    }
}
