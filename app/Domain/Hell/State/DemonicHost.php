<?php

namespace App\Domain\Hell\State;

use App\Domain\Hell\Enums\HostKind;

final class DemonicHost
{
    public string $id;
    public string $territoryId;
    public string $kind = HostKind::DEMONIC_HOST;
    public string $factionKey;
    public ?string $commanderCatalogKey = null;
    public ?string $namedDemonId = null;
    public int $strength = 10;
    public ?string $boundBreachId = null;
    public bool $collapsed = false;

    /** @var array<string, int> catalog_key => count */
    public array $composition = [];

    public function __construct(string $id, string $territoryId, string $factionKey)
    {
        $this->id = $id;
        $this->territoryId = $territoryId;
        $this->factionKey = $factionKey;
    }

    public static function fromArray(array $row): self
    {
        $h = new self((string) $row['id'], (string) $row['territoryId'], (string) $row['factionKey']);
        foreach ([
            'kind', 'commanderCatalogKey', 'namedDemonId', 'strength',
            'boundBreachId', 'collapsed', 'composition',
        ] as $field) {
            if (array_key_exists($field, $row)) {
                $h->{$field} = $row[$field];
            }
        }

        return $h;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
