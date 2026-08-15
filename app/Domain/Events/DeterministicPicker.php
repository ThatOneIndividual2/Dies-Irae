<?php

namespace App\Domain\Events;

final class DeterministicPicker
{
    /**
     * @param  list<EventCandidate>  $candidates
     * @return list<EventCandidate>
     */
    public function pick(array $candidates, string $seed, string $date, int $limit): array
    {
        if ($candidates === [] || $limit < 1) {
            return [];
        }

        usort($candidates, function (EventCandidate $a, EventCandidate $b) {
            return [$a->key(), $a->scopeType, $a->scopeId] <=> [$b->key(), $b->scopeType, $b->scopeId];
        });

        $picked = [];
        $pool = $candidates;
        for ($i = 0; $i < $limit && $pool !== []; $i++) {
            $total = 0;
            foreach ($pool as $candidate) {
                $total += $candidate->weight;
            }
            if ($total < 1) {
                break;
            }
            $roll = $this->roll($seed, $date, $i, $total);
            $cursor = 0;
            $chosenIndex = 0;
            foreach ($pool as $index => $candidate) {
                $cursor += $candidate->weight;
                if ($roll < $cursor) {
                    $chosenIndex = $index;
                    break;
                }
            }
            $picked[] = $pool[$chosenIndex];
            array_splice($pool, $chosenIndex, 1);
        }

        return $picked;
    }

    private function roll(string $seed, string $date, int $slot, int $total): int
    {
        $hash = crc32($seed.'|'.$date.'|pulse|'.$slot);

        return $hash % $total;
    }
}
