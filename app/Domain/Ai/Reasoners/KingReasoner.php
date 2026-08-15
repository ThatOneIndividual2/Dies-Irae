<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class KingReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::KING;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['exorcism', 'consecrate_land'], true)) {
            return 'a king does not celebrate the rite; he may only petition or send men';
        }
        if ($action->key === 'call_bannermen' && !$situation->isRealmHead) {
            return 'only a realm head may call bannermen';
        }

        return null;
    }
}
