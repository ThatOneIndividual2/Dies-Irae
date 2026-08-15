<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Catalog\ActionCatalog;
use App\Domain\Ai\Catalog\ProfileCatalog;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

interface Reasoner
{
    public function actorType(): string;

    /**
     * @return list<CandidateAction>
     */
    public function propose(AiActor $actor, Situation $situation, ActionCatalog $actions, ProfileCatalog $profiles): array;

    public function reject(CandidateAction $action, AiActor $actor, Situation $situation): ?string;
}
