<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class ClaimantReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::CLAIMANT;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if ($action->key === 'call_bannermen') {
            return 'a claimant without the crown cannot summon the realm';
        }
        if ($action->key === 'press_claim' && !$situation->isDynastic) {
            return 'no living house stands behind the claim';
        }

        return null;
    }
}
