<?php

namespace App\Domain\Ai\Trace;

final class Decision
{
    public string $actorId;
    public string $actorType;
    public string $actionKey;
    public string $actionLabel;
    public float $score;
    public DecisionTrace $trace;

    public function __construct(
        string $actorId,
        string $actorType,
        string $actionKey,
        string $actionLabel,
        float $score,
        DecisionTrace $trace
    ) {
        $this->actorId = $actorId;
        $this->actorType = $actorType;
        $this->actionKey = $actionKey;
        $this->actionLabel = $actionLabel;
        $this->score = $score;
        $this->trace = $trace;
    }
}
