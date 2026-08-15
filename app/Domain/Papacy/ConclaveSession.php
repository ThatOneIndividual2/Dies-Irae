<?php

namespace App\Domain\Papacy;

use App\Domain\Enums\ConclavePhase;

final class ConclaveSession
{
    public string $id;
    public string $phase = ConclavePhase::VACANT;
    public string $papalSeeId = 'rome';
    public string $seatId = 'rome';
    public string $seatName = 'Rome';
    public bool $seatRelocated = false;
    public int $round = 0;
    public int $deadlockRounds = 0;
    public int $assemblyDelayRemaining = 0;
    public ?string $electedId = null;
    public ?string $acceptedId = null;
    public ?string $enthronedId = null;
    public ?string $compromiseCandidateId = null;
    public ?string $contestedBy = null;
    /** @var list<string> */
    public array $extraordinaryRules = [];
    public ?string $vacancyCause = null;
    public ?string $openedDate = null;
    public ?string $enthronedDate = null;
    /** @var BallotRound[] */
    public array $ballots = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function recordRule(string $rule): void
    {
        if (!in_array($rule, $this->extraordinaryRules, true)) {
            $this->extraordinaryRules[] = $rule;
        }
    }

    public function inElection(): bool
    {
        return in_array($this->phase, [
            ConclavePhase::ASSEMBLING,
            ConclavePhase::VOTING,
            ConclavePhase::DEADLOCK,
        ], true);
    }
}
