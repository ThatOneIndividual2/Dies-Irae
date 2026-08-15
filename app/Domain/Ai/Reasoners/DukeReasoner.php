<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class DukeReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::DUKE;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if ($action->key === 'call_bannermen') {
            return 'a duke petitions a liege; he does not summon the realm';
        }

        return null;
    }
}
