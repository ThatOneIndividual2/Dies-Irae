<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class AbbotReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::ABBOT;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['raise_levy', 'military_cleansing', 'press_claim'], true)) {
            return 'an abbot keeps a house of prayer, not a host';
        }

        return null;
    }
}
