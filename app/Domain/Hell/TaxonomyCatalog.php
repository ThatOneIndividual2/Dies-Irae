<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\DemonCategory;
use App\Domain\Hell\Enums\RespawnPolicy;
use App\Domain\Hell\Enums\NamedDemonStrategy;

/**
 * Data-driven demon taxonomy. Engine code uses keys; names and themes live in JSON.
 */
final class TaxonomyCatalog
{
    /** @var array<string, array> */
    private array $templates = [];

    /** @var array<string, array> */
    private array $named = [];

    /** @var array<string, array> */
    private array $factions = [];

    /** @var list<string> */
    private array $themes = [];

    /** @var list<string> */
    private array $categories = [];

    public static function load(string $path): self
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("Taxonomy file missing: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException("Taxonomy JSON invalid: {$path}");
        }

        $catalog = new self();
        $catalog->hydrate($decoded);

        return $catalog;
    }

    public function hydrate(array $data): void
    {
        $this->templates = [];
        $this->named = [];
        $this->factions = [];
        $this->themes = array_values($data['themes'] ?? []);
        $this->categories = [];

        foreach ($data['categories'] ?? [] as $row) {
            $this->categories[] = (string) $row['key'];
        }

        foreach (DemonCategory::all() as $required) {
            if (!in_array($required, $this->categories, true)) {
                throw new InvalidArgumentException("Taxonomy missing required category: {$required}");
            }
        }

        foreach ($data['templates'] ?? [] as $row) {
            $this->assertEntity($row, false);
            $this->templates[(string) $row['key']] = $row;
        }

        foreach ($data['named'] ?? [] as $row) {
            $this->assertEntity($row, true);
            $this->named[(string) $row['key']] = $row;
        }

        foreach ($data['factions'] ?? [] as $row) {
            if (empty($row['key'])) {
                throw new InvalidArgumentException('Faction missing key');
            }
            $this->factions[(string) $row['key']] = $row;
        }
    }

    public function template(string $key): array
    {
        if (!isset($this->templates[$key])) {
            throw new InvalidArgumentException("Unknown demon template: {$key}");
        }

        return $this->templates[$key];
    }

    public function named(string $key): array
    {
        if (!isset($this->named[$key])) {
            throw new InvalidArgumentException("Unknown named demon: {$key}");
        }

        return $this->named[$key];
    }

    public function faction(string $key): array
    {
        if (!isset($this->factions[$key])) {
            throw new InvalidArgumentException("Unknown demonic faction: {$key}");
        }

        return $this->factions[$key];
    }

    public function hasTemplate(string $key): bool
    {
        return isset($this->templates[$key]);
    }

    public function hasNamed(string $key): bool
    {
        return isset($this->named[$key]);
    }

    public function hasFaction(string $key): bool
    {
        return isset($this->factions[$key]);
    }

    public function themes(): array
    {
        return $this->themes;
    }

    public function categories(): array
    {
        return $this->categories;
    }

    /** @return array<string, array> */
    public function templates(): array
    {
        return $this->templates;
    }

    /** @return array<string, array> */
    public function namedAll(): array
    {
        return $this->named;
    }

    /** @return array<string, array> */
    public function factions(): array
    {
        return $this->factions;
    }

    public function entity(string $key): array
    {
        if (isset($this->named[$key])) {
            return $this->named[$key];
        }
        if (isset($this->templates[$key])) {
            return $this->templates[$key];
        }

        throw new InvalidArgumentException("Unknown demonic entity: {$key}");
    }

    public function rankWeight(string $key): int
    {
        $row = $this->entity($key);

        return (int) ($row['rank_weight'] ?? DemonCategory::rankWeight((string) $row['category']));
    }

    public function respawnPolicy(string $namedKey): string
    {
        $row = $this->named($namedKey);
        $policy = (string) ($row['respawn_policy'] ?? RespawnPolicy::LORE_ONLY);

        if (!in_array($policy, RespawnPolicy::all(), true)) {
            throw new InvalidArgumentException("Invalid respawn policy for {$namedKey}: {$policy}");
        }

        return $policy;
    }

    public function sharesTheme(string $entityKey, string $theme): bool
    {
        $row = $this->entity($entityKey);
        $themes = $row['themes'] ?? [];

        return in_array($theme, $themes, true);
    }

    private function assertEntity(array $row, bool $named): void
    {
        if (empty($row['key']) || empty($row['category'])) {
            throw new InvalidArgumentException('Demon entity requires key and category');
        }

        if (!in_array($row['category'], DemonCategory::all(), true)) {
            throw new InvalidArgumentException("Unknown category {$row['category']} on {$row['key']}");
        }

        if ($named && empty($row['unique'])) {
            throw new InvalidArgumentException("Named demon {$row['key']} must be unique");
        }

        if ($named) {
            $strategy = (string) ($row['strategy'] ?? '');
            if ($strategy !== '' && !in_array($strategy, NamedDemonStrategy::all(), true)) {
                throw new InvalidArgumentException("Unknown strategy {$strategy} on {$row['key']}");
            }
        }

        foreach ($row['themes'] ?? [] as $theme) {
            if ($this->themes !== [] && !in_array($theme, $this->themes, true)) {
                throw new InvalidArgumentException("Unknown theme {$theme} on {$row['key']}");
            }
        }
    }
}
