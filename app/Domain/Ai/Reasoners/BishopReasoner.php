<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class BishopReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::BISHOP;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['raise_levy', 'military_cleansing', 'call_bannermen'], true)) {
            return 'the bishop commands rite and word, not a feudal host';
        }
        if (in_array($action->key, ['press_claim', 'arrange_heir_marriage'], true)) {
            return 'a bishop does not advance a house';
        }
        if (!$situation->hasSacrament && in_array($action->key, ['exorcism', 'consecrate_land'], true)) {
            return 'no minister in good standing is present';
        }

        return null;
    }
}
