<?php

namespace App\Domain\Events;

final class EventCandidate
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(
        public array $definition,
        public string $scopeType,
        public int $scopeId,
        public int $weight,
        public ?int $actorCharacterId,
        public string $ineligibleReason = ''
    ) {
    }

    public function key(): string
    {
        return $this->definition['key'];
    }
}
