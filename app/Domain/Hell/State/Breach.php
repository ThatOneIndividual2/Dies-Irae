<?php

namespace App\Domain\Hell\State;

final class Breach
{
    public string $id;
    public string $territoryId;
    public bool $open = true;
    public ?string $namedDemonId = null;
    public string $openedOn;
    public ?string $closedOn = null;

    public function __construct(string $id, string $territoryId, string $openedOn)
    {
        $this->id = $id;
        $this->territoryId = $territoryId;
        $this->openedOn = $openedOn;
    }

    public static function fromArray(array $row): self
    {
        $b = new self((string) $row['id'], (string) $row['territoryId'], (string) $row['openedOn']);
        foreach (['open', 'namedDemonId', 'closedOn'] as $field) {
            if (array_key_exists($field, $row)) {
                $b->{$field} = $row[$field];
            }
        }

        return $b;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
