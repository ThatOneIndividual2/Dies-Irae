<?php

namespace App\Domain\Sacred\Ports;

use App\Domain\Enums\SpreadVector;
use App\Models\Settlement;
use App\Models\SettlementLink;

final class EloquentPlagueTravelPort implements PlagueTravelPort
{
    /** @var list<array{world_id:int, from:string, to:string, intensity:int}> */
    private array $links = [];

    public function openPilgrimageLink(int $worldId, string $fromKey, string $toKey, int $intensity): void
    {
        $this->links[] = [
            'world_id' => $worldId,
            'from' => $fromKey,
            'to' => $toKey,
            'intensity' => $intensity,
        ];

        $from = Settlement::query()->where('world_id', $worldId)->where('settlement_key', $fromKey)->first();
        $to = Settlement::query()->where('world_id', $worldId)->where('settlement_key', $toKey)->first();
        if ($from === null || $to === null) {
            return;
        }

        SettlementLink::query()->updateOrCreate(
            [
                'world_id' => $worldId,
                'from_settlement_id' => $from->id,
                'to_settlement_id' => $to->id,
                'vector' => SpreadVector::PILGRIMAGE,
            ],
            [
                'intensity' => $intensity,
                'is_active' => true,
            ]
        );
    }

    public function openedLinks(): array
    {
        return $this->links;
    }
}
