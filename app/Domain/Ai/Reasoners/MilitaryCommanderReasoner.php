<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\Enums\CrisisKind;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class MilitaryCommanderReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::MILITARY_COMMANDER;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['exorcism', 'preach', 'issue_bull'], true)) {
            return 'a captain is not a minister';
        }
        if ($action->key === 'hold_the_line' && $situation->inCrisis(CrisisKind::PLAGUE) && $situation->pressure('plague_safety') >= 80) {
            return 'the host is too sick to hold';
        }

        return null;
    }
}
