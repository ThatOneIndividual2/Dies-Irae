<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class HolyOrderReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::HOLY_ORDER;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['open_granary', 'seize_grain', 'conceal_cell'], true)) {
            return 'the order chapter is for war and vow, not granary politics';
        }

        return null;
    }
}
