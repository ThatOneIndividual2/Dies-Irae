<?php

namespace App\Domain\Ai\State;

final class Personality
{
    public int $ambitious = 40;
    public int $pious = 40;
    public int $cautious = 40;
    public int $vengeful = 20;
    public int $greedy = 30;
    public int $loyal = 50;
    public int $zealous = 30;
    public int $compassionate = 40;

    public static function balanced(): self
    {
        return new self();
    }

    public static function fromArray(array $row): self
    {
        $p = new self();
        foreach (['ambitious', 'pious', 'cautious', 'vengeful', 'greedy', 'loyal', 'zealous', 'compassionate'] as $k) {
            if (isset($row[$k])) {
                $p->{$k} = max(0, min(100, (int) $row[$k]));
            }
        }

        return $p;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function riskBias(): float
    {
        return ($this->cautious - $this->ambitious) / 200.0;
    }
}
