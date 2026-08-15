<?php

namespace App\Actions\Titles;

use App\Domain\Enums\TitleRank;
use App\Domain\Support\WorldBoundary;
use App\Models\Title;
use App\Models\World;
use InvalidArgumentException;

final class CreateTitle
{
    public function execute(World $world, string $name, string $rank, array $attributes = []): Title
    {
        TitleRank::assertSecular($rank);

        if (!empty($attributes['parent_title_id'])) {
            $parent = Title::query()->findOrFail($attributes['parent_title_id']);
            WorldBoundary::assertSameWorld((int) $world->id, (int) $parent->world_id, 'parent title');
        }

        $key = $attributes['key'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

        return Title::query()->create([
            'world_id' => $world->id,
            'key' => $key,
            'name' => $name,
            'adjective' => $attributes['adjective'] ?? null,
            'rank' => $rank,
            'parent_title_id' => $attributes['parent_title_id'] ?? null,
            'de_jure_liege_title_id' => $attributes['de_jure_liege_title_id'] ?? null,
            'capital_territory_id' => $attributes['capital_territory_id'] ?? null,
            'primary_territory_id' => $attributes['primary_territory_id'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
            'is_titular' => $attributes['is_titular'] ?? false,
        ]);
    }
}
