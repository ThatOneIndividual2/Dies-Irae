<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class MerchantReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::MERCHANT;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['raise_levy', 'military_cleansing', 'march_on_breach', 'exorcism'], true)) {
            return 'a merchant does not command hosts or rites';
        }

        return null;
    }
}
