<?php

namespace App\Domain\Hell;

final class CountermeasureAttempt
{
    public string $kind;
    public string $territoryId;
    public ?string $actorCharacterId = null;
    public ?string $targetCharacterId = null;
    public ?string $targetCultId = null;
    public ?string $targetHostId = null;
    public ?string $targetArmyId = null;
    public ?string $targetBreachId = null;
    public ?string $targetNamedDemonId = null;

    /** @var array<string, float|int|bool> raw factor values, typically 0-100 or boolean */
    public array $factors = [];

    public function __construct(string $kind, string $territoryId)
    {
        $this->kind = $kind;
        $this->territoryId = $territoryId;
    }

    public function withFactors(array $factors): self
    {
        $this->factors = $factors;

        return $this;
    }
}
