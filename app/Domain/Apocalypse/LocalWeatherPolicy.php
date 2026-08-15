<?php

namespace App\Domain\Apocalypse;

final class LocalWeatherPolicy
{
    /**
     * @param  array<string, mixed>  $phase
     * @param  array<string, int>  $globalFloors
     * @return array<string, int>
     */
    public function applyLocalDeltas(
        array $current,
        array $deltas,
        array $phase,
        ApocalypseMeters $global,
        array $globalFloors
    ): array {
        $allowance = (int) ($phase['sanctuary_allowance'] ?? 0);

        $corruption = $this->boundLocal(
            (int) ($current['local_corruption'] ?? $global->get('global_corruption')),
            (int) ($deltas['local_corruption'] ?? 0),
            $global->get('global_corruption'),
            (int) ($globalFloors['global_corruption'] ?? 0),
            $allowance
        );
        $despair = $this->boundLocal(
            (int) ($current['local_despair'] ?? $global->get('despair')),
            (int) ($deltas['local_despair'] ?? 0),
            $global->get('despair'),
            (int) ($globalFloors['despair'] ?? 0),
            $allowance
        );
        $manifestation = $this->boundLocal(
            (int) ($current['local_manifestation'] ?? $global->get('demonic_manifestation')),
            (int) ($deltas['local_manifestation'] ?? 0),
            $global->get('demonic_manifestation'),
            (int) ($globalFloors['demonic_manifestation'] ?? 0),
            $allowance
        );
        $sanctity = ApocalypseMeters::clamp(
            (int) ($current['local_sanctity'] ?? 50) + (int) ($deltas['local_sanctity'] ?? 0)
        );

        $isSanctuary = $sanctity >= 70 && $corruption + 15 < $global->get('global_corruption');

        return [
            'local_corruption' => $corruption,
            'local_despair' => $despair,
            'local_manifestation' => $manifestation,
            'local_sanctity' => $sanctity,
            'is_sanctuary' => $isSanctuary ? 1 : 0,
        ];
    }

    private function boundLocal(int $current, int $delta, int $global, int $globalFloor, int $allowance): int
    {
        $proposed = ApocalypseMeters::clamp($current + $delta);
        $localFloor = max(0, $globalFloor - $allowance);
        $localCeiling = 100;

        // A sanctuary may be healthier than the globe, but not healthier than the phase still allows.
        if ($proposed < $localFloor) {
            $proposed = $localFloor;
        }
        if ($proposed > $localCeiling) {
            $proposed = $localCeiling;
        }

        // Local weather may lag behind global wounds, but cannot pretend the age is ordinary
        // once the global floor has passed the sanctuary allowance.
        unset($global);

        return $proposed;
    }
}
