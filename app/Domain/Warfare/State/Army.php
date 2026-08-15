<?php

namespace App\Domain\Warfare\State;

use App\Domain\Warfare\Engine\WarfareBalance;
use App\Domain\Warfare\Enums\ArmyNature;

final class Army
{
    /** @var UnitStack[] */
    public array $stacks;

    public function __construct(
        public int $worldId,
        public int $id,
        public int $warId,
        public int $belligerentId,
        public string $nature,
        public int $territoryId,
        array $stacks,
        public ?Commander $commander,
        public int $morale,
        public int $supply,
        public int $fear,
        public int $corruptionExposure,
        public int $plagueExposure,
        public int $manifestationRemaining,
        public ?int $portalTerritoryId,
        public bool $starving,
        public int $daysInField,
    ) {
        if (!in_array($nature, ArmyNature::all(), true)) {
            throw new \InvalidArgumentException("Unknown army nature: {$nature}");
        }
        $this->stacks = array_values($stacks);
        $this->morale = self::clamp($morale);
        $this->supply = self::clamp($supply);
        $this->fear = self::clamp($fear);
        $this->corruptionExposure = self::clamp($corruptionExposure);
        $this->plagueExposure = self::clamp($plagueExposure);
    }

    public function men(): int
    {
        $total = 0;
        foreach ($this->stacks as $stack) {
            $total += $stack->men;
        }

        return $total;
    }

    public function power(WarfareBalance $balance): float
    {
        $power = 0.0;
        foreach ($this->stacks as $stack) {
            $power += $stack->power($balance);
        }

        return $power;
    }

    public function siegePower(WarfareBalance $balance): float
    {
        $power = 0.0;
        foreach ($this->stacks as $stack) {
            if ($stack->category === \App\Domain\Warfare\Enums\UnitCategory::SIEGE) {
                $power += $stack->power($balance);
            }
        }

        return $power;
    }

    public function consecratedMen(): int
    {
        $total = 0;
        foreach ($this->stacks as $stack) {
            if ($stack->consecrated || $stack->category === \App\Domain\Warfare\Enums\UnitCategory::CONSECRATED) {
                $total += $stack->men;
            }
        }

        return $total;
    }

    public function replaceStacks(array $stacks): void
    {
        $this->stacks = array_values(array_filter($stacks, fn (UnitStack $s) => !$s->isEmpty()));
    }

    public function routed(): bool
    {
        return $this->men() <= 0 || $this->morale <= 0;
    }

    public static function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}
