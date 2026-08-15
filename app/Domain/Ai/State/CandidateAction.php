<?php

namespace App\Domain\Ai\State;

final class CandidateAction
{
    public string $key;
    public string $label;
    /** @var array<string, float> */
    public array $concerns = [];
    public float $risk = 0.2;
    /** @var list<string> */
    public array $requires = [];
    public string $source = 'catalog';

    public function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public static function fromCatalog(string $key, array $row): self
    {
        $a = new self($key, (string) ($row['label'] ?? $key));
        $a->concerns = $row['concerns'] ?? [];
        $a->risk = (float) ($row['risk'] ?? 0.2);
        $a->requires = array_values($row['requires'] ?? []);

        return $a;
    }
}
