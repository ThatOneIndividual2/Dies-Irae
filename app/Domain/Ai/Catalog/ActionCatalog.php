<?php

namespace App\Domain\Ai\Catalog;

use App\Domain\Ai\State\CandidateAction;
use InvalidArgumentException;

final class ActionCatalog
{
    /** @var array<string, array> */
    private array $actions = [];

    public static function load(string $path): self
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("AI action catalog missing: {$path}");
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException("Invalid AI action catalog: {$path}");
        }
        $c = new self();
        $c->actions = $decoded;

        return $c;
    }

    public function get(string $key): array
    {
        if (!isset($this->actions[$key])) {
            throw new InvalidArgumentException("Unknown AI action: {$key}");
        }

        return $this->actions[$key];
    }

    public function candidate(string $key): CandidateAction
    {
        return CandidateAction::fromCatalog($key, $this->get($key));
    }

    public function exists(string $key): bool
    {
        return isset($this->actions[$key]);
    }

    /** @return array<string, array> */
    public function all(): array
    {
        return $this->actions;
    }
}
