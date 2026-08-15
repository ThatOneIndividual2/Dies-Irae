<?php

namespace App\Domain\Population\Ports;

interface ArmyAttritionPort
{
    public function applyLevyLoss(int $worldId, string $settlementId, int $lostLevy, string $cause): void;
}
