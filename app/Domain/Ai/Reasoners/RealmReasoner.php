<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class RealmReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::REALM;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['exorcism', 'preach', 'press_claim'], true)) {
            return 'a realm is not a person and not a see';
        }

        return null;
    }
}
