<?php

namespace App\Domain\Campaign\Opening;

final class CampaignRng
{
    public function __construct(private string $seed)
    {
    }

    public function roll(string ...$parts): int
    {
        $material = $this->seed.'|'.implode('|', $parts);
        $hash = hash('sha256', $material);
        $slice = substr($hash, 0, 8);

        return hexdec($slice) % 1000;
    }

    public function chance(int $weight, string ...$parts): bool
    {
        $weight = max(0, min(900, $weight));
        if ($weight <= 0) {
            return false;
        }

        return $this->roll(...$parts) < $weight;
    }

    public function pickWeighted(array $items, callable $weightOf, string ...$parts): ?array
    {
        $total = 0;
        $pairs = [];
        foreach ($items as $item) {
            $w = (int) $weightOf($item);
            if ($w <= 0) {
                continue;
            }
            $total += $w;
            $pairs[] = [$item, $w];
        }
        if ($total < 1) {
            return null;
        }
        $tick = $this->roll(...$parts) % $total;
        foreach ($pairs as [$item, $w]) {
            if ($tick < $w) {
                return $item;
            }
            $tick -= $w;
        }

        return $pairs[array_key_last($pairs)][0] ?? null;
    }
}
