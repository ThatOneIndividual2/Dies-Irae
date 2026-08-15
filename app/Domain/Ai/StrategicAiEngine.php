<?php

namespace App\Domain\Ai;

use App\Domain\Ai\Catalog\ActionCatalog;
use App\Domain\Ai\Catalog\ProfileCatalog;
use App\Domain\Ai\Constraints\InstitutionalGate;
use App\Domain\Ai\Reasoners\ReasonerRegistry;
use App\Domain\Ai\Scoring\UtilityScorer;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\Situation;
use App\Domain\Ai\Trace\Decision;
use App\Domain\Ai\Trace\DecisionTrace;
use App\Domain\Ai\Trace\RejectedAction;

/**
 * Orchestrates profile reasoners. It is not itself a brain.
 */
final class StrategicAiEngine
{
    public function __construct(
        private ActionCatalog $actions,
        private ProfileCatalog $profiles,
        private ReasonerRegistry $reasoners,
        private UtilityScorer $scorer,
        private InstitutionalGate $gate
    ) {
    }

    public static function fromDataDirectory(string $directory): self
    {
        $actions = ActionCatalog::load($directory.'/actions.json');
        $profiles = ProfileCatalog::load($directory.'/profiles.json', $directory.'/crises.json');

        return new self(
            $actions,
            $profiles,
            ReasonerRegistry::standard(),
            new UtilityScorer($profiles),
            new InstitutionalGate($profiles)
        );
    }

    public function profiles(): ProfileCatalog
    {
        return $this->profiles;
    }

    public function consider(AiActor $actor, Situation $situation): Decision
    {
        $type = $actor->hat();
        $reasoner = $this->reasoners->get($type);

        $trace = new DecisionTrace();
        $trace->actorId = $actor->id;
        $trace->actorType = $type;
        $trace->reasoner = get_class($reasoner);

        if ($situation->crises !== []) {
            $trace->majorModifiers[] = 'crises: '.implode(',', $situation->crises);
        }
        $trace->majorModifiers[] = sprintf('riskTolerance=%.2f', $actor->riskTolerance);

        $seen = [];
        foreach ($reasoner->propose($actor, $situation, $this->actions, $this->profiles) as $candidate) {
            if (isset($seen[$candidate->key])) {
                continue;
            }
            $seen[$candidate->key] = true;

            if (isset($actor->cooldowns[$candidate->key]) && $actor->cooldowns[$candidate->key]->isActive($situation->date)) {
                $trace->rejected[] = new RejectedAction($candidate->key, 'cooldown until '.$actor->cooldowns[$candidate->key]->availableOn);
                continue;
            }

            $why = $reasoner->reject($candidate, $actor, $situation);
            if ($why !== null) {
                $trace->rejected[] = new RejectedAction($candidate->key, $why);
                continue;
            }

            $why = $this->gate->reject($candidate, $actor, $situation);
            if ($why !== null) {
                $trace->rejected[] = new RejectedAction($candidate->key, $why);
                continue;
            }

            $trace->considered[] = $this->scorer->score($candidate, $actor, $situation);
        }

        if ($trace->considered === []) {
            $wait = $this->actions->candidate('wait');
            $trace->considered[] = $this->scorer->score($wait, $actor, $situation);
            $trace->majorModifiers[] = 'fallback wait: every proposed act was rejected';
        }

        usort($trace->considered, function ($a, $b) {
            if ($a->final === $b->final) {
                return strcmp($a->key, $b->key);
            }

            return $b->final <=> $a->final;
        });

        $chosen = $trace->considered[0];
        $trace->chosen = $chosen;
        foreach ($chosen->modifiers as $mod) {
            if (!in_array($mod, $trace->majorModifiers, true)) {
                $trace->majorModifiers[] = $mod;
            }
        }

        return new Decision($actor->id, $type, $chosen->key, $chosen->label, $chosen->final, $trace);
    }
}
