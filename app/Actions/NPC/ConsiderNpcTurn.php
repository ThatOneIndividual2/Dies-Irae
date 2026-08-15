<?php

namespace App\Actions\NPC;

use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\Situation;
use App\Domain\Ai\Trace\Decision;
use App\Domain\NPC\NPCContract;

final class ConsiderNpcTurn
{
    public function __construct(private NPCContract $npc)
    {
    }

    public function execute(AiActor $actor, Situation $situation): Decision
    {
        return $this->npc->consider($actor, $situation);
    }
}
