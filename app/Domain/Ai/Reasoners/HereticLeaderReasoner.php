<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class HereticLeaderReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::HERETIC_LEADER;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['investigate_heresy', 'expose_cult', 'exorcism', 'preach'], true)) {
            return 'a heretic does not staff the inquisition or preach as the Church';
        }

        return null;
    }
}
