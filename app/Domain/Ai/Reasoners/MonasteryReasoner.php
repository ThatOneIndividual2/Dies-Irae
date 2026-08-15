<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class MonasteryReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::MONASTERY;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['levy_from_vassals', 'seize_grain', 'military_cleansing', 'close_borders'], true)) {
            return 'a monastery does not levy or seize as a court';
        }

        return null;
    }
}
