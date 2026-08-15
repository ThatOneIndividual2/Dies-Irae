<?php

namespace App\Domain\Hell;

use App\Domain\Hell\State\ThreatEvent;

final class CountermeasureOutcome
{
    public string $kind;
    public string $result;
    public float $score;
    public string $territoryId;
    public bool $characterDied = false;
    public bool $breachClosed = false;
    public ?string $fromState = null;
    public ?string $toState = null;
    /** @var list<ThreatEvent> */
    public array $events = [];
    /** @var array<string, mixed> */
    public array $applied = [];

    public function __construct(string $kind, string $result, float $score, string $territoryId)
    {
        $this->kind = $kind;
        $this->result = $result;
        $this->score = $score;
        $this->territoryId = $territoryId;
    }
}
