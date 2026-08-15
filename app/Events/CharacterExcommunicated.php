<?php

namespace App\Events;

final class CharacterExcommunicated
{
    public function __construct(
        public int $characterId,
        public int $actorId,
        public int $stateId,
        public string $date
    ) {
    }
}
