<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class DemonCommanderReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::DEMON_COMMANDER;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['exorcism', 'petition_pope', 'preach', 'quarantine', 'open_granary'], true)) {
            return 'a demonic commander is not a mortal institution';
        }
        if (in_array('sacrament', $action->requires, true) || in_array('papal_office', $action->requires, true)) {
            return 'Hell does not borrow Church faculties';
        }
        if (in_array('dynasty', $action->requires, true)) {
            return 'demons are not a dynasty';
        }

        return null;
    }
}
