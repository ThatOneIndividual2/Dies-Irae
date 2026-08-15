<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class PapacyReasoner extends ProfileReasoner
{
    public function actorType(): string
    {
        return ActorType::PAPACY;
    }

    protected function extraReject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        if (in_array($action->key, ['raise_levy', 'press_claim', 'close_borders', 'seize_grain'], true)) {
            return 'the papacy as institution is not a kingdom';
        }
        if (!$situation->hasPapalOffice && in_array($action->key, ['issue_bull', 'authorize_exorcists'], true)) {
            return 'sede vacante: extraordinary acts wait on a living pope or a declared emergency council';
        }

        return null;
    }
}
