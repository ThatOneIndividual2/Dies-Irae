<?php

namespace App\Domain\Hell\State;

use App\Domain\Hell\Enums\PossessionStage;

/**
 * Agency of hell on a person. Not a trait flag and not a health penalty.
 */
final class PossessionLink
{
    public string $id;
    public string $characterId;
    public string $stage = PossessionStage::TEMPTATION;
    public ?string $sourceCatalogKey = null;
    public ?string $namedDemonId = null;
    public ?string $factionKey = null;
    public int $intensity = 10;

    public function __construct(string $id, string $characterId)
    {
        $this->id = $id;
        $this->characterId = $characterId;
    }

    public static function fromArray(array $row): self
    {
        $p = new self((string) $row['id'], (string) $row['characterId']);
        foreach (['stage', 'sourceCatalogKey', 'namedDemonId', 'factionKey', 'intensity'] as $field) {
            if (array_key_exists($field, $row)) {
                $p->{$field} = $row[$field];
            }
        }

        return $p;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
