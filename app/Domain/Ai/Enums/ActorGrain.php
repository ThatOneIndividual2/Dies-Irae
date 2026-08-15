<?php

namespace App\Domain\Ai\Enums;

final class ActorGrain
{
    public const CHARACTER = 'character';
    public const ORGANIZATION = 'organization';

    public static function all(): array
    {
        return [self::CHARACTER, self::ORGANIZATION];
    }
}
