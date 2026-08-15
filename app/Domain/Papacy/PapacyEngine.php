<?php

namespace App\Domain\Papacy;

use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ConclavePhase;
use App\Domain\Enums\ExtraordinaryElectionRule;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\PapalObedienceSubject;
use App\Domain\Enums\SecularPressureKind;
use App\Domain\Enums\TheologicalLean;
use App\Domain\Support\WorldBoundary;
use InvalidArgumentException;

/**
 * Papal office, College, and conclave. Election is a process, not a roll.
 */
final class PapacyEngine
{
    public int $worldId;
    public string $papacyStatus = PapacyStatus::OCCUPIED;
    public ?string $popeId = null;
    public string $papalOfficeId = 'papal-office';
    public string $date;
    public int $tick = 0;
    public string $apocalypseStage = ApocalypseStage::ORDINARY_ORDER;

    public ConclaveCatalog $catalog;
    public PapalSeeCondition $see;
    public ConclaveSession $conclave;
    public ChurchLegitimacy $legitimacy;

    /** @var array<string, CardinalElector> */
    public array $college = [];

    /** @var array<string, CardinalElector> bishops who may become extraordinary electors */
    public array $bishopPool = [];

    /** @var SecularPressure[] */
    public array $pressures = [];

    /** @var PapalObedience[] */
    public array $obediences = [];

    public ?AntipopeSchism $schism = null;

    /** @var list<array<string,mixed>> */
    public array $log = [];

    private int $pressureSeq = 0;
    private int $schismSeq = 0;
    private int $sessionSeq = 0;

    public function __construct(int $worldId, string $date = '1348-06-24')
    {
        $this->worldId = $worldId;
        $this->date = $date;
        $this->catalog = new ConclaveCatalog();
        $this->see = new PapalSeeCondition();
        $this->conclave = new ConclaveSession('conclave-0');
        $this->legitimacy = new ChurchLegitimacy($this->catalog->legitimacyBase);
    }

    public function addCardinal(CardinalElector $elector): CardinalElector
    {
        $elector->clampTraits();
        $this->college[$elector->id] = $elector;

        return $elector;
    }

    public function addBishop(CardinalElector $bishop): CardinalElector
    {
        $bishop->isExtraordinary = true;
        $bishop->office = $bishop->office === 'cardinal' ? 'bishop' : $bishop->office;
        $bishop->clampTraits();
        $this->bishopPool[$bishop->id] = $bishop;

        return $bishop;
    }

    public function cardinal(string $id): CardinalElector
    {
        if (!isset($this->college[$id])) {
            throw new InvalidArgumentException("Unknown cardinal {$id}");
        }

        return $this->college[$id];
    }

    /**
     * @return CardinalElector[]
     */
    public function livingAccessibleElectors(): array
    {
        return array_values(array_filter(
            $this->college,
            static fn (CardinalElector $c) => $c->eligible()
        ));
    }

    /**
     * @return CardinalElector[]
     */
    public function presentElectors(): array
    {
        return array_values(array_filter(
            $this->college,
            static fn (CardinalElector $c) => $c->eligible() && $c->present
        ));
    }

    /**
     * @return list<string>
     */
    public function candidateIds(): array
    {
        $ids = [];
        foreach ($this->presentElectors() as $elector) {
            $ids[] = $elector->id;
        }
        sort($ids);

        return $ids;
    }

    public function openVacancy(string $date, string $cause = 'death'): ConclaveSession
    {
        $this->date = $date;
        $this->popeId = null;
        $this->papacyStatus = PapacyStatus::VACANT;
        $this->sessionSeq++;
        $this->conclave = new ConclaveSession('conclave-'.$this->sessionSeq);
        $this->conclave->phase = ConclavePhase::VACANT;
        $this->conclave->papalSeeId = $this->see->seeId;
        $this->conclave->vacancyCause = $cause;
        $this->conclave->openedDate = $date;
        $this->conclave->extraordinaryRules = $this->catalog->fallbackRules($this);

        foreach ($this->college as $elector) {
            $elector->present = false;
            $elector->currentVote = null;
            $elector->firstPreference = null;
        }

        $this->log[] = ['tick' => $this->tick, 'event' => 'vacancy', 'cause' => $cause, 'date' => $date];

        return $this->conclave;
    }

    public function assemble(): ConclaveSession
    {
        if ($this->papacyStatus !== PapacyStatus::VACANT && $this->papacyStatus !== PapacyStatus::DISPUTED) {
            throw new PapacyException('A conclave assembles only during a vacancy or disputed election.');
        }

        $this->applyExtraordinaryFallbacks();

        if (count($this->livingAccessibleElectors()) === 0) {
            $this->conclave->phase = ConclavePhase::VACANT;
            $this->conclave->recordRule(ExtraordinaryElectionRule::SEDE_VACANTE_PERSISTS);
            $this->log[] = ['tick' => $this->tick, 'event' => 'sede_vacante_persists'];

            return $this->conclave;
        }

        if ($this->see->delaysAssembly() && $this->conclave->assemblyDelayRemaining === 0 && $this->conclave->phase === ConclavePhase::VACANT) {
            $this->conclave->phase = ConclavePhase::ASSEMBLING;
            $this->conclave->assemblyDelayRemaining = $this->catalog->assemblyDelayTicks;
            $this->conclave->recordRule(ExtraordinaryElectionRule::DELAYED_ASSEMBLY);
            $this->placeSeat();
            $this->log[] = ['tick' => $this->tick, 'event' => 'assembly_delayed', 'seat' => $this->conclave->seatId];

            return $this->conclave;
        }

        if ($this->conclave->assemblyDelayRemaining > 0) {
            throw new PapacyException('Electors have not yet reached the assembly seat.');
        }

        $this->placeSeat();

        foreach ($this->livingAccessibleElectors() as $elector) {
            $elector->present = true;
        }

        $this->conclave->phase = ConclavePhase::VOTING;
        $this->log[] = [
            'tick' => $this->tick,
            'event' => 'assembled',
            'present' => count($this->presentElectors()),
            'seat' => $this->conclave->seatId,
        ];

        return $this->conclave;
    }

    public function voteRound(): BallotRound
    {
        if (!in_array($this->conclave->phase, [ConclavePhase::VOTING, ConclavePhase::DEADLOCK], true)) {
            throw new PapacyException('Ballots are cast only while the conclave is voting or deadlocked.');
        }

        $present = $this->presentElectors();
        if ($present === []) {
            throw new PapacyException('No electors are present to vote.');
        }

        $reduced = in_array(ExtraordinaryElectionRule::REDUCED_COLLEGE, $this->conclave->extraordinaryRules, true)
            || in_array(ExtraordinaryElectionRule::SIMPLE_MAJORITY, $this->conclave->extraordinaryRules, true);
        $needed = $this->catalog->extraordinaryMajorityNeeded(count($present), $reduced);
        $candidates = $this->candidateIds();

        if ($this->conclave->phase === ConclavePhase::DEADLOCK && $this->conclave->compromiseCandidateId === null) {
            $this->identifyCompromise();
        }

        $this->shiftCoalitions($candidates, $needed);

        $ballot = new BallotRound();
        $ballot->round = $this->conclave->round + 1;
        $ballot->phase = $this->conclave->phase;
        $ballot->present = count($present);
        $ballot->needed = $needed;
        $ballot->compromiseCandidateId = $this->conclave->compromiseCandidateId;

        foreach ($present as $elector) {
            $choice = $this->choiceFor($elector, $candidates);
            $elector->currentVote = $choice;
            if ($elector->firstPreference === null) {
                $elector->firstPreference = $choice;
            }
            $ballot->votes[$elector->id] = $choice;
            $ballot->tally[$choice] = ($ballot->tally[$choice] ?? 0) + 1;
        }

        arsort($ballot->tally);
        $leader = array_key_first($ballot->tally);
        $ballot->leaderId = $leader;
        $ballot->majority = $leader !== null && ($ballot->tally[$leader] ?? 0) >= $needed;

        $this->conclave->round = $ballot->round;
        $this->conclave->ballots[] = $ballot;

        if ($ballot->majority) {
            $this->conclave->electedId = $leader;
            $this->conclave->phase = ConclavePhase::ELECTED;
            $this->log[] = ['tick' => $this->tick, 'event' => 'elected', 'candidate' => $leader, 'round' => $ballot->round];

            return $ballot;
        }

        if ($this->conclave->round >= $this->catalog->deadlockAfterRounds) {
            $this->conclave->phase = ConclavePhase::DEADLOCK;
            $this->conclave->deadlockRounds++;
            $ballot->deadlocked = true;
            if ($this->conclave->compromiseCandidateId === null) {
                $this->identifyCompromise();
                $ballot->compromiseCandidateId = $this->conclave->compromiseCandidateId;
            }
            $this->log[] = [
                'tick' => $this->tick,
                'event' => 'deadlock',
                'round' => $ballot->round,
                'compromise' => $this->conclave->compromiseCandidateId,
            ];
        }

        return $ballot;
    }

    public function identifyCompromise(): string
    {
        $present = $this->presentElectors();
        $candidates = $this->candidateIds();
        if ($candidates === []) {
            throw new PapacyException('No compromise is possible without candidates.');
        }

        $bestId = null;
        $bestScore = null;
        $bestReputation = -1;

        foreach ($candidates as $candidateId) {
            $sum = 0;
            $min = PHP_INT_MAX;
            foreach ($present as $elector) {
                $score = $this->scoreFor($elector, $candidateId, $candidates, false);
                $sum += $score;
                $min = min($min, $score);
            }
            $reputation = $this->college[$candidateId]->theologicalReputation;
            $key = [$min, $sum, $reputation];
            if ($bestScore === null || $key > $bestScore || ($key === $bestScore && $reputation > $bestReputation)) {
                $bestScore = $key;
                $bestReputation = $reputation;
                $bestId = $candidateId;
            }
        }

        $this->conclave->compromiseCandidateId = $bestId;
        $this->log[] = ['tick' => $this->tick, 'event' => 'compromise', 'candidate' => $bestId];

        return $bestId;
    }

    public function applyPressure(SecularPressure $pressure): SecularPressure
    {
        if ($pressure->kind === SecularPressureKind::RIVAL_CLAIMANT && $pressure->candidateId) {
            $this->pressures[] = $pressure;
            $this->log[] = ['tick' => $this->tick, 'event' => 'rival_pressure', 'ruler' => $pressure->rulerId];

            return $pressure;
        }

        foreach ($this->college as $elector) {
            if (!$pressure->targets($elector->id)) {
                continue;
            }
            if (in_array($pressure->kind, [SecularPressureKind::PATRONAGE, SecularPressureKind::BRIBERY], true)) {
                $elector->patronageReceived += $pressure->magnitude;
            }
            if (in_array($pressure->kind, [SecularPressureKind::THREAT, SecularPressureKind::MILITARY], true)) {
                $elector->threatReceived += $pressure->magnitude;
                $elector->fear = min(100, $elector->fear + intdiv($pressure->magnitude, 5));
            }
        }

        if ($pressure->kind === SecularPressureKind::MILITARY && $pressure->magnitude >= 60) {
            $this->see->occupied = true;
            $this->see->accessible = false;
        }

        $this->pressures[] = $pressure;
        $this->log[] = [
            'tick' => $this->tick,
            'event' => 'secular_pressure',
            'kind' => $pressure->kind,
            'ruler' => $pressure->rulerId,
            'candidate' => $pressure->candidateId,
        ];

        return $pressure;
    }

    public function pressure(
        string $rulerId,
        string $kind,
        int $magnitude,
        ?string $candidateId = null,
        ?array $targetElectorIds = null,
        ?string $rulerRealmId = null
    ): SecularPressure {
        $this->pressureSeq++;

        return $this->applyPressure(new SecularPressure(
            'pressure-'.$this->pressureSeq,
            $rulerId,
            $kind,
            $magnitude,
            $this->date,
            $candidateId,
            $targetElectorIds,
            $rulerRealmId
        ));
    }

    public function installPope(string $candidateId): void
    {
        unset($candidateId);
        throw new PapacyException('A secular ruler cannot name the pope.');
    }

    public function attemptImperialAppointment(string $rulerId, string $candidateId): void
    {
        if (!$this->catalog->imperialAppointmentPermitted($this)) {
            throw new PapacyException('A secular ruler cannot name the pope.');
        }

        if (!isset($this->college[$candidateId]) && !isset($this->bishopPool[$candidateId])) {
            throw new PapacyException('Imperial appointment requires a living cleric.');
        }

        if ($this->papacyStatus !== PapacyStatus::VACANT) {
            $this->openVacancy($this->date, 'imperial_appointment');
        }

        $this->conclave->electedId = $candidateId;
        $this->conclave->phase = ConclavePhase::ELECTED;
        $this->conclave->recordRule(ExtraordinaryElectionRule::IMPERIAL_APPOINTMENT);
        $this->log[] = ['tick' => $this->tick, 'event' => 'imperial_appointment', 'ruler' => $rulerId, 'candidate' => $candidateId];
    }

    public function acceptElection(): CardinalElector
    {
        if ($this->conclave->phase !== ConclavePhase::ELECTED && $this->conclave->phase !== ConclavePhase::CONTESTED) {
            throw new PapacyException('Acceptance follows a completed election.');
        }

        $elected = $this->cardinal($this->conclave->electedId ?? '');
        if (!$elected->wouldAccept()) {
            $this->log[] = ['tick' => $this->tick, 'event' => 'refused', 'candidate' => $elected->id];
            $elected->present = true;
            $elected->accessible = false;
            $this->conclave->electedId = null;
            $this->conclave->phase = ConclavePhase::VOTING;
            throw new PapacyException('The elect refused the papal office.');
        }

        $this->conclave->acceptedId = $elected->id;
        $this->conclave->phase = ConclavePhase::ACCEPTED;
        $this->log[] = ['tick' => $this->tick, 'event' => 'accepted', 'candidate' => $elected->id];

        return $elected;
    }

    public function enthrone(): CardinalElector
    {
        if ($this->conclave->phase !== ConclavePhase::ACCEPTED && $this->conclave->phase !== ConclavePhase::CONTESTED) {
            throw new PapacyException('Enthronement follows acceptance.');
        }

        $accepted = $this->cardinal($this->conclave->acceptedId ?? $this->conclave->electedId ?? '');
        $this->popeId = $accepted->id;
        $this->conclave->enthronedId = $accepted->id;
        $this->conclave->enthronedDate = $this->date;
        $this->conclave->phase = $this->conclave->phase === ConclavePhase::CONTESTED
            ? ConclavePhase::CONTESTED
            : ConclavePhase::ENTHRONED;
        $this->papacyStatus = $this->schism && $this->schism->open()
            ? PapacyStatus::SCHISM
            : ($this->conclave->phase === ConclavePhase::CONTESTED ? PapacyStatus::DISPUTED : PapacyStatus::OCCUPIED);
        $this->legitimacy->restore($this->catalog->enthronementRestore);
        $this->align(PapalObedienceSubject::ELECTOR, $accepted->id, $accepted->id, $this->date);
        $this->log[] = ['tick' => $this->tick, 'event' => 'enthroned', 'pope' => $accepted->id];

        return $accepted;
    }

    public function contestElection(string $byElectorId): void
    {
        if (!in_array($this->conclave->phase, [ConclavePhase::ELECTED, ConclavePhase::ACCEPTED, ConclavePhase::ENTHRONED], true)) {
            throw new PapacyException('Only a completed election can be contested.');
        }

        $this->conclave->phase = ConclavePhase::CONTESTED;
        $this->conclave->contestedBy = $byElectorId;
        $this->papacyStatus = PapacyStatus::DISPUTED;
        $this->legitimacy->penalize($this->catalog->contestedPenalty);
        $this->log[] = ['tick' => $this->tick, 'event' => 'contested', 'by' => $byElectorId];
    }

    /**
     * @param list<string> $electorIds
     * @param list<string> $seeIds
     * @param list<string> $realmIds
     */
    public function raiseAntipope(
        string $claimantId,
        array $electorIds,
        array $seeIds = [],
        array $realmIds = [],
        ?string $recognizedId = null
    ): AntipopeSchism {
        $recognized = $recognizedId ?? $this->popeId ?? $this->conclave->enthronedId ?? $this->conclave->acceptedId ?? $this->conclave->electedId;
        if ($recognized === null) {
            throw new PapacyException('An antipope requires a recognized papal claimant to oppose.');
        }
        if ($recognized === $claimantId) {
            throw new PapacyException('A claimant cannot be both pope and antipope.');
        }

        $this->schismSeq++;
        $this->schism = new AntipopeSchism(
            'schism-'.$this->schismSeq,
            $recognized,
            $claimantId,
            $this->date
        );
        $this->schism->rivalElectorIds = $electorIds;
        $this->schism->rivalSeeIds = $seeIds;
        $this->schism->rivalRealmIds = $realmIds;

        $this->align(PapalObedienceSubject::CHARACTER, $claimantId, $claimantId, $this->date);
        $this->align(PapalObedienceSubject::CHARACTER, $recognized, $recognized, $this->date);
        foreach ($electorIds as $id) {
            $this->align(PapalObedienceSubject::ELECTOR, $id, $claimantId, $this->date);
        }
        foreach ($seeIds as $id) {
            $this->align(PapalObedienceSubject::SEE, $id, $claimantId, $this->date);
        }
        foreach ($realmIds as $id) {
            $this->align(PapalObedienceSubject::REALM, $id, $claimantId, $this->date);
        }

        $this->papacyStatus = PapacyStatus::SCHISM;
        $this->legitimacy->splitForSchism($this->catalog->schismSplit);
        $this->log[] = ['tick' => $this->tick, 'event' => 'antipope', 'claimant' => $claimantId, 'recognized' => $recognized];

        return $this->schism;
    }

    public function align(string $kind, string $subjectId, string $claimantId, ?string $date = null): PapalObedience
    {
        $date = $date ?? $this->date;
        $this->obediences = array_values(array_filter(
            $this->obediences,
            static fn (PapalObedience $o) => !($o->subjectKind === $kind && $o->subjectId === $subjectId)
        ));
        $obedience = new PapalObedience($kind, $subjectId, $claimantId, $date);
        $this->obediences[] = $obedience;

        if ($this->schism && $this->schism->open()) {
            if ($kind === PapalObedienceSubject::SEE && $claimantId === $this->schism->rivalClaimantId) {
                $this->schism->rivalSeeIds = array_values(array_unique(array_merge($this->schism->rivalSeeIds, [$subjectId])));
            }
            if ($kind === PapalObedienceSubject::REALM && $claimantId === $this->schism->rivalClaimantId) {
                $this->schism->rivalRealmIds = array_values(array_unique(array_merge($this->schism->rivalRealmIds, [$subjectId])));
            }
        }

        return $obedience;
    }

    public function recognize(string $subjectKind, string $subjectId, string $claimantId): PapalObedience
    {
        return $this->align($subjectKind, $subjectId, $claimantId, $this->date);
    }

    public function reconcile(string $survivingClaimantId): AntipopeSchism
    {
        if (!$this->schism || !$this->schism->open()) {
            throw new PapacyException('There is no open schism to heal.');
        }

        $this->schism->status = 'healed';
        $this->schism->healedDate = $this->date;
        $this->schism->recognizedClaimantId = $survivingClaimantId;

        foreach ($this->obediences as $obedience) {
            $obedience->claimantId = $survivingClaimantId;
            $obedience->date = $this->date;
        }

        $this->popeId = $survivingClaimantId;
        $this->papacyStatus = PapacyStatus::OCCUPIED;
        if ($this->conclave->phase === ConclavePhase::CONTESTED) {
            $this->conclave->phase = ConclavePhase::ENTHRONED;
            $this->conclave->enthronedId = $survivingClaimantId;
        }
        $this->legitimacy->absorbRival(10000);
        $this->legitimacy->restore($this->catalog->reconciliationRestore);
        $this->log[] = ['tick' => $this->tick, 'event' => 'reconciled', 'pope' => $survivingClaimantId];

        return $this->schism;
    }

    public function killCardinal(string $id): void
    {
        $elector = $this->cardinal($id);
        $elector->alive = false;
        $elector->present = false;
        $elector->accessible = false;
        if ($this->popeId === $id) {
            $this->openVacancy($this->date, 'death');
        }
        $this->log[] = ['tick' => $this->tick, 'event' => 'cardinal_died', 'id' => $id];
    }

    public function setElectorAccess(string $id, bool $accessible): void
    {
        $this->cardinal($id)->accessible = $accessible;
        if (!$accessible) {
            $this->cardinal($id)->present = false;
        }
    }

    public function setApocalypseStage(string $stage): void
    {
        if (!in_array($stage, ApocalypseStage::all(), true)) {
            throw new PapacyException("Unknown apocalypse stage: {$stage}");
        }
        $this->apocalypseStage = $stage;
        if (ApocalypseStage::atLeast($stage, ApocalypseStage::OPEN_RIFTS) && !$this->see->demonicIncursion) {
            $this->see->demonicIncursion = true;
        }
        if (ApocalypseStage::atLeast($stage, ApocalypseStage::GREAT_MORTALITY)) {
            $this->see->plague = true;
        }
    }

    public function tick(): void
    {
        $this->tick++;

        if ($this->papacyStatus === PapacyStatus::VACANT) {
            $this->legitimacy->applyVacancyDecay($this->catalog->vacancyDecayPerTick);
        }

        if ($this->conclave->phase === ConclavePhase::ASSEMBLING && $this->conclave->assemblyDelayRemaining > 0) {
            $this->conclave->assemblyDelayRemaining--;
            if ($this->conclave->assemblyDelayRemaining === 0) {
                $this->assemble();
            }
        }

        if ($this->schism && $this->schism->open()) {
            $rivalSees = count($this->obediencesOf($this->schism->rivalClaimantId, PapalObedienceSubject::SEE));
            $recognizedSees = count($this->obediencesOf($this->schism->recognizedClaimantId, PapalObedienceSubject::SEE));
            if ($rivalSees > $recognizedSees) {
                $this->legitimacy->splitForSchism(40);
            } else {
                $this->legitimacy->absorbRival(40);
            }
        }
    }

    /**
     * @return PapalObedience[]
     */
    public function obediencesOf(string $claimantId, ?string $kind = null): array
    {
        return array_values(array_filter(
            $this->obediences,
            static function (PapalObedience $o) use ($claimantId, $kind) {
                if ($o->claimantId !== $claimantId) {
                    return false;
                }

                return $kind === null || $o->subjectKind === $kind;
            }
        ));
    }

    public function scoreFor(CardinalElector $elector, string $candidateId, array $candidates, bool $withCompromise = true): int
    {
        $candidate = $this->college[$candidateId] ?? null;
        if (!$candidate) {
            return -1000;
        }

        $score = $elector->preferences[$candidateId] ?? 0;
        $score += ($elector->relationships[$candidateId] ?? 0) * $this->catalog->relationshipWeight;

        if ($elector->theology === $candidate->theology) {
            $score += $this->catalog->theologyWeight;
        } elseif (TheologicalLean::opposed($elector->theology, $candidate->theology)) {
            $score -= $this->catalog->theologyOpposeWeight;
        }

        if ($elector->faction === $candidate->faction) {
            $score += $this->catalog->factionWeight;
        }

        if ($elector->id === $candidateId) {
            $score += intdiv($elector->ambition * $this->catalog->ambitionWeight, 100);
        }

        $score += intdiv($candidate->theologicalReputation * $this->catalog->reputationWeight, 100);

        foreach ($this->pressures as $pressure) {
            if ($pressure->candidateId !== $candidateId || !$pressure->targets($elector->id)) {
                continue;
            }

            if (in_array($pressure->kind, [SecularPressureKind::PATRONAGE, SecularPressureKind::BRIBERY], true)) {
                $score += intdiv($elector->corruption * $pressure->magnitude, 50);
            }
            if (in_array($pressure->kind, [SecularPressureKind::THREAT, SecularPressureKind::MILITARY], true)) {
                $score += intdiv($elector->fear * $pressure->magnitude, 50);
            }
            if (in_array($pressure->kind, [SecularPressureKind::DIPLOMACY, SecularPressureKind::ALLIANCE], true)) {
                $score += $elector->realmTie === $pressure->rulerRealmId
                    ? $pressure->magnitude + $this->catalog->realmTieWeight
                    : intdiv($pressure->magnitude, 3);
            }
        }

        if ($withCompromise && $this->conclave->compromiseCandidateId === $candidateId && $this->conclave->phase === ConclavePhase::DEADLOCK) {
            $score += $this->catalog->compromiseWeight;
            $score += $this->catalog->deadlockFatigueWeight * $this->conclave->deadlockRounds;
        }

        return $score;
    }

    public function assertWorld(int $worldId, string $context): void
    {
        WorldBoundary::assertSameWorld($this->worldId, $worldId, $context);
    }

    private function applyExtraordinaryFallbacks(): void
    {
        $rules = $this->catalog->fallbackRules($this);
        foreach ($rules as $rule) {
            $this->conclave->recordRule($rule);
        }

        $this->placeSeat();

        if (in_array(ExtraordinaryElectionRule::EXPAND_TO_BISHOPS, $rules, true)) {
            foreach ($this->bishopPool as $bishop) {
                if (!$bishop->eligible()) {
                    continue;
                }
                $bishop->isExtraordinary = true;
                $this->college[$bishop->id] = $bishop;
            }
            $this->log[] = ['tick' => $this->tick, 'event' => 'expanded_to_bishops'];
        }
    }

    private function placeSeat(): void
    {
        $seat = $this->see->assemblySeat();
        $this->conclave->seatId = $seat['id'];
        $this->conclave->seatName = $seat['name'];
        $this->conclave->seatRelocated = $seat['relocated'];
        $this->conclave->papalSeeId = $this->see->seeId;
        if ($seat['relocated']) {
            $this->conclave->recordRule(ExtraordinaryElectionRule::RELOCATE_SEAT);
        }
    }

    /**
     * @param list<string> $candidates
     */
    private function shiftCoalitions(array $candidates, int $needed): void
    {
        if ($this->conclave->phase !== ConclavePhase::DEADLOCK || $this->conclave->compromiseCandidateId === null) {
            return;
        }

        $blocSizes = [];
        foreach ($this->presentElectors() as $elector) {
            $pref = $elector->firstPreference ?? $this->rawChoice($elector, $candidates);
            $blocSizes[$pref] = ($blocSizes[$pref] ?? 0) + 1;
        }

        foreach ($this->presentElectors() as $elector) {
            $pref = $elector->firstPreference ?? $this->rawChoice($elector, $candidates);
            $bloc = $blocSizes[$pref] ?? 0;
            if ($pref !== $this->conclave->compromiseCandidateId && $bloc < $needed) {
                $elector->currentVote = $this->conclave->compromiseCandidateId;
            }
        }
    }

    /**
     * @param list<string> $candidates
     */
    private function choiceFor(CardinalElector $elector, array $candidates): string
    {
        if ($this->conclave->phase === ConclavePhase::DEADLOCK
            && $this->conclave->compromiseCandidateId
            && $elector->currentVote === $this->conclave->compromiseCandidateId
        ) {
            return $this->conclave->compromiseCandidateId;
        }

        return $this->rawChoice($elector, $candidates);
    }

    /**
     * @param list<string> $candidates
     */
    private function rawChoice(CardinalElector $elector, array $candidates): string
    {
        $bestId = $candidates[0];
        $bestScore = PHP_INT_MIN;

        foreach ($candidates as $candidateId) {
            $score = $this->scoreFor($elector, $candidateId, $candidates, true);
            if ($score > $bestScore || ($score === $bestScore && $candidateId < $bestId)) {
                $bestScore = $score;
                $bestId = $candidateId;
            }
        }

        return $bestId;
    }
}
