<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Enums\UnitCategory;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\UnitStack;

final class ArmyFactory
{
    private int $nextId = 1;

    public function __construct(private WarfareBalance $balance)
    {
    }

    /**
     * @param UnitStack[] $levyStacks
     * @param UnitStack[] $menAtArms
     */
    public function form(
        int $worldId,
        int $warId,
        int $belligerentId,
        ForceProfile $force,
        int $territoryId,
        array $levyStacks,
        array $menAtArms,
        ?Commander $commander,
        int $manifestationRemaining = 0,
        ?int $portalTerritoryId = null,
    ): Army {
        $stacks = array_merge($levyStacks, $menAtArms);
        if ($force->consecratedByDefault) {
            $stacks = array_map(function (UnitStack $stack) {
                if ($stack->category === UnitCategory::SIEGE) {
                    return $stack;
                }

                return new UnitStack(
                    $stack->category === UnitCategory::LEVY ? UnitCategory::CONSECRATED : $stack->category,
                    $stack->men,
                    $stack->quality,
                    $stack->label,
                    true,
                );
            }, $stacks);
        }

        if ($force->manifestationLimited && $manifestationRemaining > 0) {
            $cap = $manifestationRemaining;
            $total = 0;
            foreach ($stacks as $stack) {
                $total += $stack->men;
            }
            if ($total > $cap) {
                $stacks = $this->trimToCap($stacks, $cap);
            }
        }

        $id = $this->nextId++;

        return new Army(
            $worldId,
            $id,
            $warId,
            $belligerentId,
            $force->nature,
            $territoryId,
            $stacks,
            $commander,
            $force->moraleInstability > 0 ? 70 : 80,
            80,
            0,
            0,
            0,
            $manifestationRemaining,
            $portalTerritoryId,
            false,
            0,
        );
    }

    /**
     * Standing troops, independent of levies. Donor analog: persistent infantry/cavalry/archers/siege columns,
     * but owned by the belligerent, not the User account.
     *
     * @return UnitStack[]
     */
    public function menAtArms(int $infantry, int $cavalry, int $archers, int $siege, bool $consecrated = false): array
    {
        $stacks = [];
        if ($infantry > 0) {
            $stacks[] = new UnitStack(UnitCategory::MEN_AT_ARMS, $infantry, $this->balance->unitPower['infantry'], 'infantry', $consecrated);
        }
        if ($archers > 0) {
            $stacks[] = new UnitStack(UnitCategory::MEN_AT_ARMS, $archers, $this->balance->unitPower['archers'], 'archers', $consecrated);
        }
        if ($cavalry > 0) {
            $stacks[] = new UnitStack(UnitCategory::MEN_AT_ARMS, $cavalry, $this->balance->unitPower['cavalry'], 'cavalry', $consecrated);
        }
        if ($siege > 0) {
            $stacks[] = new UnitStack(UnitCategory::SIEGE, $siege, $this->balance->unitPower['siege'], 'siege engines');
        }

        return $stacks;
    }

    /**
     * @param UnitStack[] $stacks
     * @return UnitStack[]
     */
    private function trimToCap(array $stacks, int $cap): array
    {
        $kept = [];
        $remaining = $cap;
        foreach ($stacks as $stack) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($stack->men, $remaining);
            $kept[] = new UnitStack($stack->category, $take, $stack->quality, $stack->label, $stack->consecrated);
            $remaining -= $take;
        }

        return $kept;
    }

    public function setNextId(int $id): void
    {
        $this->nextId = $id;
    }
}
