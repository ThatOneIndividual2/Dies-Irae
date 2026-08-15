<?php

namespace App\Domain\Sacred\Policies;

use App\Domain\Enums\RelicTrueNature;
use App\Models\Relic;

final class RelicVisibilityPolicy
{
    public function publicView(Relic $relic): array
    {
        return [
            'id' => $relic->id,
            'name' => $relic->name,
            'category' => $relic->category,
            'claimed_authenticity' => $relic->claimed_authenticity,
            'recognized_authenticity' => $relic->authenticity,
            'claimed_provenance' => $relic->claimed_provenance,
            'condition' => $relic->condition,
            'pilgrimage_value' => $relic->pilgrimage_value,
            'current_holding_id' => $relic->current_holding_id,
            'current_territory_id' => $relic->current_territory_id,
            'current_character_id' => $relic->current_character_id,
            'owner_type' => $relic->owner_type,
            'owner_id' => $relic->owner_id,
        ];
    }

    public function adminView(Relic $relic): array
    {
        $view = $this->publicView($relic);
        $view['true_nature'] = $relic->true_nature ?: RelicTrueNature::DOUBTFUL;

        return $view;
    }
}
