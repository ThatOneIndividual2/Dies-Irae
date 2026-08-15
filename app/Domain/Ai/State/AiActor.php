<?php

namespace App\Domain\Ai\State;

use App\Domain\Ai\Enums\ActorGrain;
use App\Domain\Ai\Enums\ActorType;

final class AiActor
{
    public string $id;
    public string $type;
    public string $grain;
    public Personality $personality;
    public float $riskTolerance;
    /** @var list<MemoryEntry> */
    public array $memories = [];
    /** @var array<string, Relationship> */
    public array $relationships = [];
    /** @var array<string, Cooldown> */
    public array $cooldowns = [];
    public ?string $wearingType = null;

    public function __construct(string $id, string $type)
    {
        $this->id = $id;
        $this->type = $type;
        $this->grain = ActorType::grain($type);
        $this->personality = Personality::balanced();
        $this->riskTolerance = 0.4;
        $this->wearingType = $type;
    }

    public function hat(): string
    {
        return $this->wearingType ?? $this->type;
    }

    public function isCharacter(): bool
    {
        return $this->grain === ActorGrain::CHARACTER;
    }

    public function isOrganization(): bool
    {
        return $this->grain === ActorGrain::ORGANIZATION;
    }

    public function relationshipStanding(string $targetId): int
    {
        return $this->relationships[$targetId]->standing ?? 0;
    }

    public function remember(MemoryEntry $entry): void
    {
        $this->memories[] = $entry;
    }

    public function cooldown(string $actionKey, string $availableOn): void
    {
        $this->cooldowns[$actionKey] = new Cooldown($actionKey, $availableOn);
    }
}
