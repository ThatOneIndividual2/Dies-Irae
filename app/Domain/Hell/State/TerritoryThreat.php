<?php

namespace App\Domain\Hell\State;

use App\Domain\Hell\Enums\IncursionState;

final class TerritoryThreat
{
    public string $id;
    public string $incursionState = IncursionState::DORMANT;
    public int $corruption = 0;
    public int $localManifestation = 0;
    public int $cultActivity = 0;
    public int $despair = 0;
    public int $plague = 0;
    public int $morale = 70;
    public int $population = 1000;
    public int $clergyPresence = 10;
    public float $production = 1.0;
    public bool $travelOpen = true;
    public ?string $rulerCharacterId = null;
    public ?string $legalTitleId = null;
    public bool $settlementCorrupted = false;
    public ?string $breachId = null;
    public ?string $strongholdFactionKey = null;
    public int $rulerTemptation = 0;
    public int $clergyPressure = 0;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function fromArray(array $row): self
    {
        $t = new self((string) $row['id']);
        foreach ([
            'incursionState', 'corruption', 'localManifestation', 'cultActivity',
            'despair', 'plague', 'morale', 'population', 'clergyPresence',
            'production', 'travelOpen', 'rulerCharacterId', 'legalTitleId',
            'settlementCorrupted', 'breachId', 'strongholdFactionKey',
            'rulerTemptation', 'clergyPressure',
        ] as $field) {
            if (array_key_exists($field, $row)) {
                $t->{$field} = $row[$field];
            }
        }

        return $t;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function clamp(): void
    {
        $this->corruption = self::meter($this->corruption);
        $this->localManifestation = self::meter($this->localManifestation);
        $this->cultActivity = self::meter($this->cultActivity);
        $this->despair = self::meter($this->despair);
        $this->plague = self::meter($this->plague);
        $this->morale = self::meter($this->morale);
        $this->rulerTemptation = self::meter($this->rulerTemptation);
        $this->clergyPressure = self::meter($this->clergyPressure);
        $this->population = max(0, $this->population);
        $this->clergyPresence = max(0, $this->clergyPresence);
        $this->production = max(0.0, min(1.5, (float) $this->production));
    }

    public static function meter(int $value): int
    {
        return max(0, min(100, $value));
    }
}
