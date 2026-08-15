<?php

namespace App\Domain\Population;

use App\Domain\Enums\SocialClass;
use InvalidArgumentException;

/**
 * Aggregate living people by estate. Never an individual peasant.
 */
final class PopulationCohorts
{
    public int $nobles;
    public int $clergy;
    public int $burghers;
    public int $peasants;
    public int $unfree;

    public function __construct(
        int $nobles = 0,
        int $clergy = 0,
        int $burghers = 0,
        int $peasants = 0,
        int $unfree = 0
    ) {
        $this->nobles = self::guard($nobles, SocialClass::NOBLES);
        $this->clergy = self::guard($clergy, SocialClass::CLERGY);
        $this->burghers = self::guard($burghers, SocialClass::BURGHERS);
        $this->peasants = self::guard($peasants, SocialClass::PEASANTS);
        $this->unfree = self::guard($unfree, SocialClass::UNFREE);
    }

    public static function fromArray(array $counts): self
    {
        return new self(
            (int) ($counts[SocialClass::NOBLES] ?? $counts['nobles'] ?? 0),
            (int) ($counts[SocialClass::CLERGY] ?? $counts['clergy'] ?? 0),
            (int) ($counts[SocialClass::BURGHERS] ?? $counts['burghers'] ?? 0),
            (int) ($counts[SocialClass::PEASANTS] ?? $counts['peasants'] ?? 0),
            (int) ($counts[SocialClass::UNFREE] ?? $counts['unfree'] ?? 0),
        );
    }

    public function souls(): int
    {
        return $this->nobles + $this->clergy + $this->burghers + $this->peasants + $this->unfree;
    }

    public function militaryAge(): int
    {
        return (int) floor(
            $this->nobles * 0.40
            + $this->clergy * 0.15
            + $this->burghers * 0.35
            + $this->peasants * 0.28
            + $this->unfree * 0.30
        );
    }

    public function workforce(): int
    {
        return (int) floor(
            $this->nobles * 0.10
            + $this->clergy * 0.20
            + $this->burghers * 0.55
            + $this->peasants * 0.65
            + $this->unfree * 0.70
        );
    }

    /**
     * Daily ration demand. Nobles eat more; the unfree eat less.
     */
    public function foodDemand(): int
    {
        return (int) ceil(
            $this->nobles * 1.8
            + $this->clergy * 1.1
            + $this->burghers * 1.2
            + $this->peasants * 1.0
            + $this->unfree * 0.85
        );
    }

    public function get(string $class): int
    {
        return match ($class) {
            SocialClass::NOBLES => $this->nobles,
            SocialClass::CLERGY => $this->clergy,
            SocialClass::BURGHERS => $this->burghers,
            SocialClass::PEASANTS => $this->peasants,
            SocialClass::UNFREE => $this->unfree,
            default => throw new InvalidArgumentException("Unknown class {$class}"),
        };
    }

    public function with(string $class, int $count): self
    {
        $copy = $this->copy();
        match ($class) {
            SocialClass::NOBLES => $copy->nobles = self::guard($count, $class),
            SocialClass::CLERGY => $copy->clergy = self::guard($count, $class),
            SocialClass::BURGHERS => $copy->burghers = self::guard($count, $class),
            SocialClass::PEASANTS => $copy->peasants = self::guard($count, $class),
            SocialClass::UNFREE => $copy->unfree = self::guard($count, $class),
            default => throw new InvalidArgumentException("Unknown class {$class}"),
        };

        return $copy;
    }

    public function add(self $other): self
    {
        return new self(
            $this->nobles + $other->nobles,
            $this->clergy + $other->clergy,
            $this->burghers + $other->burghers,
            $this->peasants + $other->peasants,
            $this->unfree + $other->unfree,
        );
    }

    public function subtract(self $other): self
    {
        return new self(
            $this->nobles - $other->nobles,
            $this->clergy - $other->clergy,
            $this->burghers - $other->burghers,
            $this->peasants - $other->peasants,
            $this->unfree - $other->unfree,
        );
    }

    public function copy(): self
    {
        return new self($this->nobles, $this->clergy, $this->burghers, $this->peasants, $this->unfree);
    }

    public function toArray(): array
    {
        return [
            SocialClass::NOBLES => $this->nobles,
            SocialClass::CLERGY => $this->clergy,
            SocialClass::BURGHERS => $this->burghers,
            SocialClass::PEASANTS => $this->peasants,
            SocialClass::UNFREE => $this->unfree,
        ];
    }

    /**
     * Remove up to $total souls using relative class weights. Conserves exactly.
     *
     * @param  array<string,int>  $weights
     * @return array{taken: self, remaining: self}
     */
    public function extract(int $total, array $weights): array
    {
        $total = max(0, $total);
        $available = $this->souls();
        if ($total === 0 || $available === 0) {
            return ['taken' => new self(), 'remaining' => $this->copy()];
        }
        if ($total >= $available) {
            return ['taken' => $this->copy(), 'remaining' => new self()];
        }

        $scored = [];
        foreach (SocialClass::all() as $class) {
            $size = $this->get($class);
            $weight = $weights[$class] ?? 100;
            $scored[$class] = $size * max(0, $weight);
        }

        $allocated = self::largestRemainder($total, $scored, $this->toArray());
        $taken = self::fromArray($allocated);

        return ['taken' => $taken, 'remaining' => $this->subtract($taken)];
    }

    /**
     * @param  array<string,int>  $scores
     * @param  array<string,int>  $caps
     * @return array<string,int>
     */
    public static function largestRemainder(int $total, array $scores, array $caps): array
    {
        $sumScores = array_sum($scores);
        $result = [];
        $remainders = [];
        $assigned = 0;

        foreach (SocialClass::all() as $class) {
            $cap = max(0, $caps[$class] ?? 0);
            if ($sumScores <= 0 || ($scores[$class] ?? 0) <= 0 || $cap === 0) {
                $result[$class] = 0;
                $remainders[$class] = 0.0;
                continue;
            }
            $raw = ($total * $scores[$class]) / $sumScores;
            $floor = (int) floor($raw);
            if ($floor > $cap) {
                $floor = $cap;
            }
            $result[$class] = $floor;
            $remainders[$class] = $raw - $floor;
            $assigned += $floor;
        }

        $leftover = $total - $assigned;
        arsort($remainders, SORT_NUMERIC);
        foreach (array_keys($remainders) as $class) {
            if ($leftover <= 0) {
                break;
            }
            $cap = max(0, $caps[$class] ?? 0);
            $room = $cap - $result[$class];
            if ($room <= 0) {
                continue;
            }
            $give = min($room, $leftover);
            $result[$class] += $give;
            $leftover -= $give;
        }

        if ($leftover > 0) {
            foreach (SocialClass::all() as $class) {
                if ($leftover <= 0) {
                    break;
                }
                $cap = max(0, $caps[$class] ?? 0);
                $room = $cap - ($result[$class] ?? 0);
                if ($room <= 0) {
                    continue;
                }
                $give = min($room, $leftover);
                $result[$class] += $give;
                $leftover -= $give;
            }
        }

        return $result;
    }

    private static function guard(int $count, string $class): int
    {
        if ($count < 0) {
            throw new InvalidArgumentException("Population cohort {$class} cannot be negative");
        }

        return $count;
    }
}
