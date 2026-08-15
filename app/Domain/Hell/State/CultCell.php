<?php

namespace App\Domain\Hell\State;

final class CultCell
{
    public string $id;
    public string $territoryId;
    public string $factionKey;
    public int $activity = 10;
    public bool $revealed = false;
    public ?string $patronNamedKey = null;
    public bool $destroyed = false;

    public function __construct(string $id, string $territoryId, string $factionKey)
    {
        $this->id = $id;
        $this->territoryId = $territoryId;
        $this->factionKey = $factionKey;
    }

    public static function fromArray(array $row): self
    {
        $c = new self((string) $row['id'], (string) $row['territoryId'], (string) $row['factionKey']);
        foreach (['activity', 'revealed', 'patronNamedKey', 'destroyed'] as $field) {
            if (array_key_exists($field, $row)) {
                $c->{$field} = $row[$field];
            }
        }

        return $c;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
