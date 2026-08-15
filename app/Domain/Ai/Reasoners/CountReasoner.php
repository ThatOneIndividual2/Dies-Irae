<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\Enums\CrisisKind;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class CountReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::COUNT;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if ($action->key === 'press_claim') {
            return 'a count keeps to the county; claims belong to a claimant hat';
        }
        if ($action->key === 'call_bannermen') {
            return 'a count has no bannermen of the realm';
        }
        if ($action->key === 'military_cleansing' && $situation->inCrisis(CrisisKind::PLAGUE) && !$situation->inCrisis(CrisisKind::DEMONIC_INCURSION)) {
            return 'a count will not march a levy through plague for glory';
        }

        return null;
    }
}
