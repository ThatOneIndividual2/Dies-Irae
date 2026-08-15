<?php

namespace App\Domain\Economy;

final class FieldForager
{
    public function __construct(
        public string $id,
        public string $settlementId,
        public int $men,
        public int $deserted = 0
    ) {
    }

    public function dailyRations(): int
    {
        return $this->men * YieldCatalog::ARMY_RATION;
    }
}
