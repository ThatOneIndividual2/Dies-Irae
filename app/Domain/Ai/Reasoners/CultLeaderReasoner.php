<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\Enums\CrisisKind;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class CultLeaderReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::CULT_LEADER;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['expose_cult', 'investigate_heresy', 'petition_pope', 'preach'], true)) {
            return 'the cell does not invite the Church';
        }
        if ($action->key === 'accelerate_rite' && $situation->inCrisis(CrisisKind::CULT_DISCOVERY)) {
            return 'a revealed cell conceals; it does not rite in the open';
        }

        return null;
    }
}
