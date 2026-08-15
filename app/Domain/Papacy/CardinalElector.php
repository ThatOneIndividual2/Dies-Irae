<?php

namespace App\Domain\Papacy;

use App\Domain\Enums\CardinalFaction;
use App\Domain\Enums\TheologicalLean;
use App\Domain\Support\IntClamp;

final class CardinalElector
{
    public string $id;
    public string $name;
    public bool $alive = true;
    public bool $accessible = true;
    public bool $present = false;
    public bool $isExtraordinary = false;
    public string $theology = TheologicalLean::CONSERVATIVE;
    public string $faction = CardinalFaction::CURIAL;
    public int $ambition = 40;
    public int $fear = 20;
    public int $corruption = 20;
    public int $theologicalReputation = 50;
    public ?string $realmTie = null;
    public string $office = 'cardinal';
    /** @var array<string,int> */
    public array $relationships = [];
    /** @var array<string,int> */
    public array $preferences = [];
    public ?string $currentVote = null;
    public ?string $firstPreference = null;
    public int $patronageReceived = 0;
    public int $threatReceived = 0;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function eligible(): bool
    {
        return $this->alive && $this->accessible;
    }

    public function clampTraits(): void
    {
        $this->ambition = IntClamp::between($this->ambition, 0, 100);
        $this->fear = IntClamp::between($this->fear, 0, 100);
        $this->corruption = IntClamp::between($this->corruption, 0, 100);
        $this->theologicalReputation = IntClamp::between($this->theologicalReputation, 0, 100);
    }

    public function wouldAccept(): bool
    {
        if ($this->ambition >= 20) {
            return true;
        }

        return $this->fear < 80;
    }
}
