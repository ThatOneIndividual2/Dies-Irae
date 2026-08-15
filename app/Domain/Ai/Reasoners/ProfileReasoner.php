<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Catalog\ActionCatalog;
use App\Domain\Ai\Catalog\ProfileCatalog;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

abstract class ProfileReasoner implements Reasoner
{
    public function propose(AiActor $actor, Situation $situation, ActionCatalog $actions, ProfileCatalog $profiles): array
    {
        $out = [];
        foreach ($profiles->actions($this->actorType()) as $key) {
            if (!$actions->exists($key)) {
                continue;
            }
            $out[] = $actions->candidate($key);
        }

        return array_merge($out, $this->extraPropose($actor, $situation, $actions));
    }

    public function reject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        return $this->extraReject($action, $actor, $situation);
    }

    /**
     * @return list<CandidateAction>
     */
    protected function extraPropose(AiActor $actor, Situation $situation, ActionCatalog $actions): array
    {
        return [];
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        return null;
    }
}
