<?php

namespace App\Domain\Ai\State;

final class MemoryEntry
{
    public string $key;
    public string $kind;
    public int $salience = 50;
    public ?string $relatedAction = null;

    public function __construct(string $key, string $kind, int $salience = 50, ?string $relatedAction = null)
    {
        $this->key = $key;
        $this->kind = $kind;
        $this->salience = max(0, min(100, $salience));
        $this->relatedAction = $relatedAction;
    }
}
