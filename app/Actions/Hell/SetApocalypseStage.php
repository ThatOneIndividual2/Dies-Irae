<?php

namespace App\Actions\Hell;

use App\Domain\Apocalypse\CoarseStageMap;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Support\Transactional;
use App\Models\ApocalypseState;
use App\Models\World;
use InvalidArgumentException;

final class SetApocalypseStage
{
    public function execute(World $world, string $stage, array $extraSigns = []): ApocalypseState
    {
        if (!in_array($stage, ApocalypseStage::all(), true)) {
            throw new InvalidArgumentException("Unknown apocalypse stage: {$stage}");
        }

        return Transactional::run(function () use ($world, $stage, $extraSigns) {
            $current = ApocalypseState::query()
                ->where('world_id', $world->id)
                ->lockForUpdate()
                ->first();

            $date = $world->current_date
                ? $world->current_date->toDateString()
                : $world->game_date->toDateString();

            $phaseKey = CoarseStageMap::phaseFromStage($stage);

            if (!$current) {
                return ApocalypseState::query()->create([
                    'world_id' => $world->id,
                    'stage' => $stage,
                    'phase_key' => $phaseKey,
                    'phase_ordinal' => ApocalypseStage::weight($stage) + 1,
                    'phase_entered_on' => $date,
                    'stage_entered_date' => $date,
                    'signs' => $extraSigns,
                ]);
            }

            $existingStage = $current->stage ?: CoarseStageMap::stageFromPhase((string) $current->phase_key);
            $signs = array_values(array_unique(array_merge($current->signs ?? [], $extraSigns)));

            if ($existingStage === $stage) {
                $current->signs = $signs;
                $current->save();
                return $current->fresh();
            }

            if (ApocalypseStage::weight($stage) < ApocalypseStage::weight((string) $existingStage)) {
                return $current;
            }

            $current->stage = $stage;
            $current->phase_key = $phaseKey;
            $current->phase_ordinal = ApocalypseStage::weight($stage) + 1;
            $current->phase_entered_on = $date;
            $current->stage_entered_date = $date;
            $current->signs = $signs;
            $current->save();

            return $current->fresh();
        });
    }
}
