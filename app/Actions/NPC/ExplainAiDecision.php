<?php

namespace App\Actions\NPC;

use App\Domain\Ai\Trace\Decision;
use App\Domain\Ai\Trace\DecisionTrace;
use App\Domain\NPC\NPCContract;

final class ExplainAiDecision
{
    public function __construct(private NPCContract $npc)
    {
    }

    public function execute(Decision $decision): DecisionTrace
    {
        return $this->npc->explain($decision);
    }
}
