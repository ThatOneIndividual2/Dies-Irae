<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class HolyOrderLeaderReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::HOLY_ORDER_LEADER;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['press_claim', 'arrange_heir_marriage', 'seize_grain'], true)) {
            return 'a holy-order master has no house to enrich';
        }
        if ($action->key === 'withdraw_army' && $situation->pressure('infernal_threat') >= 70) {
            return 'the order does not yield a breach without a chapter decision';
        }

        return null;
    }
}
