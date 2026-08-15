<?php

namespace App\Domain\Ai\Scoring;

use App\Domain\Ai\Catalog\ProfileCatalog;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;
use App\Domain\Ai\Trace\ScoredAction;

final class UtilityScorer
{
    public function __construct(private ProfileCatalog $profiles)
    {
    }

    public function score(CandidateAction $action, AiActor $actor, Situation $situation): ScoredAction
    {
        $type = $actor->hat();
        $weights = $this->profiles->concernWeights($type);
        $modifiers = [];

        foreach ($situation->crises as $crisis) {
            foreach ($this->profiles->crisisShift($crisis) as $concern => $delta) {
                $weights[$concern] = ($weights[$concern] ?? 0) + $delta;
                $modifiers[] = "crisis {$crisis} {$concern}".($delta >= 0 ? '+' : '').$delta;
            }
        }

        $raw = 0.0;
        foreach ($action->concerns as $concern => $affinity) {
            $pressure = $situation->pressure($concern) / 100.0;
            $w = (float) ($weights[$concern] ?? 0.3);
            $raw += $w * (float) $affinity * $pressure;
        }

        $personality = $this->personalityAdjust($action, $actor, $modifiers);
        $relation = $this->relationshipAdjust($action, $actor, $situation, $modifiers);
        $memory = $this->memoryAdjust($action, $actor, $modifiers);

        $riskTolerance = max(0.05, min(0.95, $actor->riskTolerance - $actor->personality->riskBias()));
        $riskPenalty = $action->risk * (1.0 - $riskTolerance);
        if ($riskPenalty > 0.05) {
            $modifiers[] = sprintf('risk -%.2f (tol %.2f)', $riskPenalty, $riskTolerance);
        }

        $final = $raw + $personality + $relation + $memory - $riskPenalty;

        return new ScoredAction($action->key, $action->label, $raw, $final, array_values(array_unique($modifiers)));
    }

    private function personalityAdjust(CandidateAction $action, AiActor $actor, array &$modifiers): float
    {
        $p = $actor->personality;
        $delta = 0.0;
        $map = [
            'press_claim' => ['ambitious', 0.008],
            'secure_succession' => ['ambitious', 0.004],
            'arrange_heir_marriage' => ['ambitious', 0.004],
            'exorcism' => ['pious', 0.007],
            'consecrate_land' => ['pious', 0.006],
            'preach' => ['pious', 0.005],
            'authorize_exorcists' => ['zealous', 0.006],
            'march_on_breach' => ['zealous', 0.006],
            'fortify' => ['cautious', 0.006],
            'withdraw_army' => ['cautious', 0.007],
            'quarantine' => ['cautious', 0.005],
            'relocate_goods' => ['greedy', 0.008],
            'seize_grain' => ['greedy', 0.006],
            'open_granary' => ['compassionate', 0.007],
            'shelter_refugees' => ['compassionate', 0.007],
            'excommunicate_rival' => ['vengeful', 0.007],
            'pacify_vassals' => ['loyal', 0.005],
            'petition_liege' => ['loyal', 0.004],
            'accelerate_rite' => ['zealous', 0.005],
        ];
        if (isset($map[$action->key])) {
            [$axis, $coeff] = $map[$action->key];
            $add = $p->{$axis} * $coeff;
            $delta += $add;
            if (abs($add) >= 0.15) {
                $modifiers[] = sprintf('%s %+0.2f', $axis, $add);
            }
        }

        return $delta;
    }

    private function relationshipAdjust(CandidateAction $action, AiActor $actor, Situation $situation, array &$modifiers): float
    {
        if ($situation->rivalId === null) {
            return 0.0;
        }
        $standing = $actor->relationshipStanding($situation->rivalId);
        if ($action->key === 'excommunicate_rival' || $action->key === 'press_claim') {
            $add = max(0, -$standing) / 200.0;
            if ($add >= 0.1) {
                $modifiers[] = sprintf('rival standing %d %+0.2f', $standing, $add);
            }

            return $add;
        }
        if ($action->key === 'pacify_vassals' || $action->key === 'treat_with_papacy') {
            return $standing / 400.0;
        }

        return 0.0;
    }

    private function memoryAdjust(CandidateAction $action, AiActor $actor, array &$modifiers): float
    {
        $delta = 0.0;
        foreach ($actor->memories as $memory) {
            if ($memory->relatedAction === $action->key) {
                $add = $memory->salience / 400.0;
                if ($memory->kind === 'failure') {
                    $add *= -1;
                }
                $delta += $add;
                $modifiers[] = sprintf('memory %s %+0.2f', $memory->kind, $add);
            }
        }

        return $delta;
    }
}
