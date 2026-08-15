<?php

namespace App\Domain\Apocalypse;

final class CoarseStageMap
{
    /** @var array<string, string> */
    private const PHASE_TO_STAGE = [
        'ordinary' => 'ordinary_order',
        'portents' => 'ordinary_order',
        'great_pestilence' => 'great_mortality',
        'spiritual_fracture' => 'thinning_veil',
        'manifestations' => 'thinning_veil',
        'open_incursions' => 'open_rifts',
        'collapse_of_kingdoms' => 'collapse_of_offices',
        'infernal_dominion' => 'end_of_the_age',
        'final_resistance' => 'end_of_the_age',
    ];

    /** @var array<string, string> */
    private const STAGE_TO_PHASE = [
        'ordinary_order' => 'ordinary',
        'great_mortality' => 'great_pestilence',
        'thinning_veil' => 'spiritual_fracture',
        'open_rifts' => 'open_incursions',
        'collapse_of_offices' => 'collapse_of_kingdoms',
        'end_of_the_age' => 'final_resistance',
    ];

    public static function stageFromPhase(string $phaseKey): string
    {
        return self::PHASE_TO_STAGE[$phaseKey] ?? 'ordinary_order';
    }

    public static function phaseFromStage(string $stage): string
    {
        return self::STAGE_TO_PHASE[$stage] ?? 'ordinary';
    }
}
