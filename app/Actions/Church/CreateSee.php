<?php

namespace App\Actions\Church;

use App\Domain\Enums\SeeKind;
use App\Domain\Support\WorldBoundary;
use App\Models\ChurchProvince;
use App\Models\See;
use App\Models\World;
use InvalidArgumentException;

final class CreateSee
{
    public function execute(World $world, string $key, string $name, string $seeType, array $attributes = []): See
    {
        if (!in_array($seeType, SeeKind::all(), true)) {
            throw new InvalidArgumentException("Unknown see type: {$seeType}");
        }

        if (!empty($attributes['church_province_id'])) {
            $province = ChurchProvince::query()->findOrFail($attributes['church_province_id']);
            WorldBoundary::assertSameWorld((int) $world->id, (int) $province->world_id, 'see province');
        }

        if (!empty($attributes['parent_see_id'])) {
            $parent = See::query()->findOrFail($attributes['parent_see_id']);
            WorldBoundary::assertSameWorld((int) $world->id, (int) $parent->world_id, 'parent see');
        }

        return See::query()->create([
            'world_id' => $world->id,
            'church_province_id' => $attributes['church_province_id'] ?? null,
            'parent_see_id' => $attributes['parent_see_id'] ?? null,
            'territory_id' => $attributes['territory_id'] ?? null,
            'seat_holding_id' => $attributes['seat_holding_id'] ?? null,
            'key' => $key,
            'name' => $name,
            'see_type' => $seeType,
            'is_active' => $attributes['is_active'] ?? true,
            'is_exempt' => $attributes['is_exempt'] ?? ($seeType === SeeKind::EXEMPT_ABBEY),
        ]);
    }
}
