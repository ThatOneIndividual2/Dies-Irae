<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class PopeReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::POPE;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['press_claim', 'arrange_heir_marriage', 'secure_succession', 'raise_levy'], true)) {
            return 'the papal office is not a dynasty and not a kingdom';
        }
        if (!$situation->hasPapalOffice) {
            return 'no living papal office in this world';
        }

        return null;
    }
}
