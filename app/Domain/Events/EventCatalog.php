<?php

namespace App\Domain\Events;

use InvalidArgumentException;

final class EventCatalog
{
    /** @var array<string, mixed> */
    private array $meta;

    /** @var array<string, array<string, mixed>> */
    private array $definitions = [];

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, mixed>>  $definitions
     */
    public function __construct(array $meta, array $definitions)
    {
        $this->meta = $meta;
        foreach ($definitions as $definition) {
            $this->definitions[$definition['key']] = $definition;
        }
    }

    public static function fromDirectory(string $directory): self
    {
        $meta = self::readJson($directory.'/catalog.json');
        $definitions = [];
        $defDir = $directory.'/definitions';
        if (is_dir($defDir)) {
            $files = glob($defDir.'/*.json') ?: [];
            sort($files);
            foreach ($files as $file) {
                $decoded = self::readJson($file);
                if (isset($decoded['key'])) {
                    $definitions[] = $decoded;
                    continue;
                }
                foreach ($decoded['events'] ?? $decoded as $event) {
                    if (is_array($event) && isset($event['key'])) {
                        $definitions[] = $event;
                    }
                }
            }
        }

        return new self($meta, $definitions);
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Event catalog file missing: {$path}");
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid event catalog JSON: {$path}");
        }

        return $decoded;
    }

    /** @return list<string> */
    public function scopes(): array
    {
        return $this->meta['scopes'] ?? [];
    }

    /** @return list<string> */
    public function categories(): array
    {
        return $this->meta['categories'] ?? [];
    }

    /** @return list<string> */
    public function visibilities(): array
    {
        return $this->meta['visibilities'] ?? ['player', 'observer', 'hidden', 'npc_only'];
    }

    /** @return list<string> */
    public function ops(): array
    {
        return $this->meta['ops'] ?? [];
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $key): array
    {
        if (! isset($this->definitions[$key])) {
            throw new InvalidArgumentException("Unknown event definition: {$key}");
        }

        return $this->definitions[$key];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }
}
