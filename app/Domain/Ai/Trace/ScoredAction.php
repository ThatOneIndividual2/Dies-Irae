<?php

namespace App\Domain\Ai\Trace;

final class ScoredAction
{
    public string $key;
    public string $label;
    public float $raw;
    public float $final;
    /** @var list<string> */
    public array $modifiers = [];

    public function __construct(string $key, string $label, float $raw, float $final, array $modifiers = [])
    {
        $this->key = $key;
        $this->label = $label;
        $this->raw = $raw;
        $this->final = $final;
        $this->modifiers = $modifiers;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'raw' => round($this->raw, 4),
            'final' => round($this->final, 4),
            'modifiers' => $this->modifiers,
        ];
    }
}
