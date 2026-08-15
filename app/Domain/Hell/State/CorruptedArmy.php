<?php

namespace App\Domain\Hell\State;

/**
 * A former human army under infernal influence.
 * Not a DemonicHost and not a feudal levy of Hell.
 */
final class CorruptedArmy
{
    public string $id;
    public string $territoryId;
    public int $corruption = 0;
    public int $strength = 10;
    public ?string $originalRealmId = null;
    public ?string $originalCommanderCharacterId = null;
    public bool $cleansed = false;

    public function __construct(string $id, string $territoryId)
    {
        $this->id = $id;
        $this->territoryId = $territoryId;
    }

    public static function fromArray(array $row): self
    {
        $a = new self((string) $row['id'], (string) $row['territoryId']);
        foreach ([
            'corruption', 'strength', 'originalRealmId',
            'originalCommanderCharacterId', 'cleansed',
        ] as $field) {
            if (array_key_exists($field, $row)) {
                $a->{$field} = $row[$field];
            }
        }

        return $a;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
