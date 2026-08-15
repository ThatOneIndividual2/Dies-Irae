<?php

namespace App\Actions\Church;

use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Support\WorldBoundary;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\World;
use InvalidArgumentException;

final class CreateSpiritualOffice
{
    public function execute(World $world, string $key, string $name, string $rank, array $attributes = []): SpiritualOffice
    {
        if (!SpiritualOfficeRank::isSpiritual($rank)) {
            throw new InvalidArgumentException("Rank {$rank} is not a spiritual office.");
        }

        if (!empty($attributes['see_id'])) {
            $see = See::query()->findOrFail($attributes['see_id']);
            WorldBoundary::assertSameWorld((int) $world->id, (int) $see->world_id, 'office see');
        }

        $office = SpiritualOffice::query()->create([
            'world_id' => $world->id,
            'key' => $key,
            'name' => $name,
            'rank' => $rank,
            'see_id' => $attributes['see_id'] ?? null,
            'church_province_id' => $attributes['church_province_id'] ?? null,
            'monastery_id' => $attributes['monastery_id'] ?? null,
            'religious_order_id' => $attributes['religious_order_id'] ?? null,
            'holy_order_id' => $attributes['holy_order_id'] ?? null,
            'appointment_mode' => $attributes['appointment_mode'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
            'is_papal_apex' => $attributes['is_papal_apex'] ?? false,
        ]);

        if (!empty($attributes['see_id']) && !empty($attributes['as_ordinary'])) {
            See::query()->whereKey($attributes['see_id'])->update(['ordinary_office_id' => $office->id]);
        }

        return $office;
    }
}
