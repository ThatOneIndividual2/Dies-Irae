<?php

namespace App\Domain\Apocalypse;

final class ApocalypseMeters
{
    public const KEYS = [
        'global_corruption',
        'plague_severity',
        'demonic_manifestation',
        'institutional_collapse',
        'famine_pressure',
        'despair',
        'church_cohesion',
        'political_fragmentation',
    ];

    public const HEALTHY = [
        'church_cohesion',
    ];

    /** @var array<string, int> */
    private array $values;

    /** @param array<string, int|float> $values */
    public function __construct(array $values)
    {
        $this->values = [];
        foreach (self::KEYS as $key) {
            $this->values[$key] = self::clamp((int) round($values[$key] ?? 0));
        }
    }

    /** @return array<string, int> */
    public static function ordinaryDefaults(): array
    {
        return [
            'global_corruption' => 2,
            'plague_severity' => 0,
            'demonic_manifestation' => 0,
            'institutional_collapse' => 1,
            'famine_pressure' => 3,
            'despair' => 4,
            'church_cohesion' => 82,
            'political_fragmentation' => 6,
        ];
    }

    public static function fromState($state): self
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = (int) $state->{$key};
        }

        return new self($values);
    }

    public function get(string $key): int
    {
        return $this->values[$key];
    }

    /** @return array<string, int> */
    public function all(): array
    {
        return $this->values;
    }

    public static function isHealthy(string $key): bool
    {
        return in_array($key, self::HEALTHY, true);
    }

    /**
     * @param  array<string, int>  $floors
     * @param  array<string, int>  $ceilings
     */
    public function withDelta(string $key, int $delta, array $floors, array $ceilings): self
    {
        $next = $this->values;
        $next[$key] = self::clampBounded($next[$key] + $delta, $key, $floors, $ceilings);

        return new self($next);
    }

    /**
     * @param  array<string, int>  $deltas
     * @param  array<string, int>  $floors
     * @param  array<string, int>  $ceilings
     */
    public function withDeltas(array $deltas, array $floors, array $ceilings): self
    {
        $next = $this->values;
        foreach ($deltas as $key => $delta) {
            if (!in_array($key, self::KEYS, true)) {
                continue;
            }
            $next[$key] = self::clampBounded($next[$key] + (int) $delta, $key, $floors, $ceilings);
        }

        return new self($next);
    }

    /**
     * @param  array<string, int>  $floors
     * @param  array<string, int>  $ceilings
     */
    public function clampedTo(array $floors, array $ceilings): self
    {
        $next = [];
        foreach (self::KEYS as $key) {
            $next[$key] = self::clampBounded($this->values[$key], $key, $floors, $ceilings);
        }

        return new self($next);
    }

    /**
     * @param  array<string, int>  $floors
     * @param  array<string, int>  $ceilings
     */
    public static function clampBounded(int $value, string $key, array $floors, array $ceilings): int
    {
        $min = $floors[$key] ?? 0;
        $max = $ceilings[$key] ?? 100;

        return max($min, min($max, self::clamp($value)));
    }

    public static function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}
