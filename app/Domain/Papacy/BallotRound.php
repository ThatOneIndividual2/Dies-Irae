<?php

namespace App\Domain\Papacy;

final class BallotRound
{
    public int $round;
    public string $phase;
    /** @var array<string,string> electorId => candidateId */
    public array $votes = [];
    /** @var array<string,int> */
    public array $tally = [];
    public int $present = 0;
    public int $needed = 0;
    public ?string $leaderId = null;
    public bool $majority = false;
    public bool $deadlocked = false;
    public ?string $compromiseCandidateId = null;

    public function votesFor(string $candidateId): int
    {
        return $this->tally[$candidateId] ?? 0;
    }
}
