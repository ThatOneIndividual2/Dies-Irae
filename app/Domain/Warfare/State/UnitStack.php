<?php

namespace App\Domain\Warfare\State;

use App\Domain\Warfare\Enums\UnitCategory;
use App\Domain\Warfare\Engine\WarfareBalance;

final class UnitStack
{
    public function __construct(
        public string $category,
        public int $men,
        public float $quality = 1.0,
        public string $label = '',
        public bool $consecrated = false,
    ) {
        if ($this->men < 0) {
            throw new \InvalidArgumentException('Unit stack cannot be negative.');
        }
        if (!in_array($category, UnitCategory::all(), true)) {
            throw new \InvalidArgumentException("Unknown unit category: {$category}");
        }
    }

    public function power(WarfareBalance $balance): float
    {
        return $this->men * $this->quality * $balance->categoryWeight($this->category);
    }

    public function takeLosses(int $lost): self
    {
        $lost = max(0, min($this->men, $lost));

        return new self(
            $this->category,
            $this->men - $lost,
            $this->quality,
            $this->label,
            $this->consecrated,
        );
    }

    public function isEmpty(): bool
    {
        return $this->men <= 0;
    }
}
