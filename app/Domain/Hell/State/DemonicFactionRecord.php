<?php

namespace App\Domain\Hell\State;

/**
 * Infernal polity. Not a Realm, Dynasty, or Title.
 */
final class DemonicFactionRecord
{
    public string $key;
    public string $name;
    /** @var list<string> */
    public array $themes = [];
    /** @var list<string> */
    public array $rivals = [];
    public ?string $patronNamedKey = null;
    /** @var list<string> claimed as incursion, never as title ownership */
    public array $claimedTerritoryIds = [];

    public function __construct(string $key, string $name)
    {
        $this->key = $key;
        $this->name = $name;
    }

    public static function fromArray(array $row): self
    {
        $f = new self((string) $row['key'], (string) ($row['name'] ?? $row['key']));
        foreach (['themes', 'rivals', 'patronNamedKey', 'claimedTerritoryIds'] as $field) {
            if (array_key_exists($field, $row)) {
                $f->{$field} = $row[$field];
            }
        }

        return $f;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
