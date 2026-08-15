<?php

namespace App\Validation;

use App\Domain\Catastrophe\CatastropheEngine;
use App\Domain\Enums\RuinState;

final class PopulationBoundsValidator
{
    /**
     * @return list<array{code:string,message:string,settlement_id?:string}>
     */
    public function validate(CatastropheEngine $engine): array
    {
        $issues = [];
        foreach ($engine->settlements as $settlement) {
            if ($settlement->worldId !== $engine->worldId) {
                $issues[] = [
                    'code' => 'cross_world',
                    'message' => 'Settlement world_id does not match engine',
                    'settlement_id' => $settlement->id,
                ];
            }
            if ($settlement->souls() < 0) {
                $issues[] = [
                    'code' => 'negative_population',
                    'message' => 'Souls below zero',
                    'settlement_id' => $settlement->id,
                ];
            }
            foreach ($settlement->cohorts->toArray() as $class => $n) {
                if ($n < 0) {
                    $issues[] = [
                        'code' => 'negative_cohort',
                        'message' => "Class {$class} below zero",
                        'settlement_id' => $settlement->id,
                    ];
                }
            }
            $sum = $settlement->cohorts->souls();
            if ($sum !== $settlement->souls()) {
                $issues[] = [
                    'code' => 'cohort_mismatch',
                    'message' => 'Class sum does not match souls',
                    'settlement_id' => $settlement->id,
                ];
            }
            if (!RuinState::isKnown($settlement->ruinState)) {
                $issues[] = [
                    'code' => 'unknown_ruin',
                    'message' => 'Ruin state is not in the catalog',
                    'settlement_id' => $settlement->id,
                ];
            }
            if ($settlement->ruinState === RuinState::FUNCTIONING && $settlement->souls() === 0) {
                $issues[] = [
                    'code' => 'empty_functioning',
                    'message' => 'Empty settlement still marked functioning',
                    'settlement_id' => $settlement->id,
                ];
            }
        }

        return $issues;
    }
}
