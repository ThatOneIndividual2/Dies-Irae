<?php

namespace App\Domain\Population;

use App\Domain\Enums\RuinState;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\IntClamp;

/**
 * Mechanical consequences of a ruin state. Tax, levy, food, parish, and war
 * read these multipliers. A ruined town does not keep collecting tax.
 */
final class RuinModifiers
{
    public string $state;
    public int $tax;
    public int $levy;
    public int $food;
    public int $moraleFloor;
    public int $despairFloor;
    public int $rebellion;
    public int $migrationPush;
    public int $corruptionDrift;
    public int $armySupply;
    public int $armyAttrition;
    public bool $parish;
    public bool $recruit;
    public bool $tithe;
    public bool $successionCrisis;
    public bool $acceptsRefugees;

    public function __construct(string $state)
    {
        if (!RuinState::isKnown($state)) {
            throw new \InvalidArgumentException("Unknown ruin state {$state}");
        }

        $this->state = $state;
        $this->acceptsRefugees = RuinState::acceptsRefugees($state);
        $this->parish = RuinState::parishFunctions($state);
        $this->tithe = $this->parish;
        $this->recruit = RuinState::raisesLevy($state);

        switch ($state) {
            case RuinState::FUNCTIONING:
                $this->tax = 10000;
                $this->levy = 10000;
                $this->food = 10000;
                $this->moraleFloor = 40;
                $this->despairFloor = 0;
                $this->rebellion = 0;
                $this->migrationPush = 0;
                $this->corruptionDrift = 0;
                $this->armySupply = 10000;
                $this->armyAttrition = 0;
                $this->successionCrisis = false;
                break;
            case RuinState::STRAINED:
                $this->tax = 7000;
                $this->levy = 6500;
                $this->food = 7500;
                $this->moraleFloor = 25;
                $this->despairFloor = 15;
                $this->rebellion = 1800;
                $this->migrationPush = 2000;
                $this->corruptionDrift = 200;
                $this->armySupply = 7000;
                $this->armyAttrition = 400;
                $this->successionCrisis = false;
                break;
            case RuinState::DEPOPULATED:
                $this->tax = 2500;
                $this->levy = 2000;
                $this->food = 3500;
                $this->moraleFloor = 10;
                $this->despairFloor = 35;
                $this->rebellion = 3500;
                $this->migrationPush = 4500;
                $this->corruptionDrift = 600;
                $this->armySupply = 2500;
                $this->armyAttrition = 1200;
                $this->successionCrisis = true;
                break;
            case RuinState::ABANDONED:
                $this->tax = 0;
                $this->levy = 0;
                $this->food = 0;
                $this->moraleFloor = 0;
                $this->despairFloor = 55;
                $this->rebellion = 0;
                $this->migrationPush = 8000;
                $this->corruptionDrift = 1200;
                $this->armySupply = 0;
                $this->armyAttrition = 2500;
                $this->successionCrisis = true;
                $this->recruit = false;
                $this->parish = false;
                $this->tithe = false;
                break;
            case RuinState::RUINED:
                $this->tax = 0;
                $this->levy = 0;
                $this->food = 0;
                $this->moraleFloor = 0;
                $this->despairFloor = 70;
                $this->rebellion = 0;
                $this->migrationPush = 0;
                $this->corruptionDrift = 1800;
                $this->armySupply = 0;
                $this->armyAttrition = 4000;
                $this->successionCrisis = true;
                $this->recruit = false;
                $this->parish = false;
                $this->tithe = false;
                break;
            case RuinState::CORRUPTED:
                $this->tax = 1500;
                $this->levy = 800;
                $this->food = 2000;
                $this->moraleFloor = 0;
                $this->despairFloor = 60;
                $this->rebellion = 5000;
                $this->migrationPush = 3500;
                $this->corruptionDrift = 2500;
                $this->armySupply = 1500;
                $this->armyAttrition = 3000;
                $this->successionCrisis = true;
                $this->parish = false;
                $this->tithe = false;
                $this->recruit = false;
                break;
            case RuinState::OVERRUN:
                $this->tax = 0;
                $this->levy = 0;
                $this->food = 0;
                $this->moraleFloor = 0;
                $this->despairFloor = 90;
                $this->rebellion = 0;
                $this->migrationPush = 9500;
                $this->corruptionDrift = 4000;
                $this->armySupply = 0;
                $this->armyAttrition = 7000;
                $this->successionCrisis = true;
                $this->recruit = false;
                $this->parish = false;
                $this->tithe = false;
                $this->acceptsRefugees = false;
                break;
        }
    }

    public function applyTax(int $base): int
    {
        return BasisPoints::of($base, $this->tax);
    }

    public function applyLevy(int $base): int
    {
        return BasisPoints::of($base, $this->levy);
    }

    public function applyFood(int $base): int
    {
        return BasisPoints::of($base, $this->food);
    }

    public function floorDespair(int $despair): int
    {
        return IntClamp::between(max($despair, $this->despairFloor), 0, 100);
    }

    public function floorMorale(int $morale): int
    {
        if ($this->moraleFloor <= 0 && in_array($this->state, [
            RuinState::ABANDONED,
            RuinState::RUINED,
            RuinState::CORRUPTED,
            RuinState::OVERRUN,
        ], true)) {
            return IntClamp::between(min($morale, 15), 0, 100);
        }

        return IntClamp::between($morale, 0, 100);
    }
}
