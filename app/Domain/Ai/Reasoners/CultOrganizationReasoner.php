<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\Enums\CrisisKind;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class CultOrganizationReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::CULT;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['issue_bull', 'exorcism', 'levy_from_vassals'], true)) {
            return 'a cult is not the Church and not a realm';
        }
        if ($action->key === 'accelerate_rite' && $situation->inCrisis(CrisisKind::CULT_DISCOVERY)) {
            return 'a revealed cell does not accelerate while the hunt is up';
        }

        return null;
    }
}
