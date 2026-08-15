<?php

namespace Tests\Feature\Papacy;

use App\Actions\Papacy\AcceptPapalElection;
use App\Actions\Papacy\ApplySecularPapalPressure;
use App\Actions\Papacy\AssembleConclave;
use App\Actions\Papacy\ConductConclaveRound;
use App\Actions\Papacy\EnthronePope;
use App\Actions\Papacy\OpenPapalVacancy;
use App\Actions\Papacy\RaiseAntipope;
use App\Actions\Papacy\RecognizePapalClaimant;
use App\Actions\Papacy\ReconcilePapalSchism;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ConclavePhase;
use App\Domain\Enums\ExtraordinaryElectionRule;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\PapalObedienceSubject;
use App\Domain\Enums\SecularPressureKind;
use App\Domain\Papacy\PapacyException;
use Tests\DomainTestCase;
use Tests\Support\PapacyFixture;

class PapacyAndConclaveTest extends DomainTestCase
{
    public function test_normal_election_runs_as_a_process_then_enthrones(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        $officeId = $engine->papalOfficeId;

        (new OpenPapalVacancy())->execute($engine, '1348-12-06', 'death');
        $this->assertSame(PapacyStatus::VACANT, $engine->papacyStatus);
        $this->assertSame(ConclavePhase::VACANT, $engine->conclave->phase);
        $this->assertNull($engine->popeId);

        (new AssembleConclave())->execute($engine);
        $this->assertSame(ConclavePhase::VOTING, $engine->conclave->phase);
        $this->assertCount(9, $engine->presentElectors());
        $this->assertFalse($engine->conclave->seatRelocated);

        $ballot = (new ConductConclaveRound())->execute($engine);
        $this->assertSame(1, $ballot->round);
        $this->assertTrue($ballot->majority);
        $this->assertSame('orsini', $ballot->leaderId);
        $this->assertGreaterThanOrEqual(6, $ballot->votesFor('orsini'));
        $this->assertSame(ConclavePhase::ELECTED, $engine->conclave->phase);

        (new AcceptPapalElection())->execute($engine);
        $this->assertSame(ConclavePhase::ACCEPTED, $engine->conclave->phase);

        $pope = (new EnthronePope())->execute($engine);
        $this->assertSame('orsini', $pope->id);
        $this->assertSame(PapacyStatus::OCCUPIED, $engine->papacyStatus);
        $this->assertSame(ConclavePhase::ENTHRONED, $engine->conclave->phase);
        $this->assertSame($officeId, $engine->papalOfficeId);
        $this->assertCount(1, $engine->conclave->ballots);
        $this->assertGreaterThan($engine->catalog->legitimacyBase, $engine->legitimacy->recognizedBp);
    }

    public function test_deadlocked_election_names_a_compromise_then_shifts_coalitions(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::deadlockCollege($engine);
        (new OpenPapalVacancy())->execute($engine, '1348-12-06');
        PapacyFixture::seatCollege($engine);

        $first = (new ConductConclaveRound())->execute($engine);
        $this->assertFalse($first->majority);
        $this->assertSame(3, $first->votesFor('orsini'));
        $this->assertSame(3, $first->votesFor('foix'));
        $this->assertSame(3, $first->votesFor('colonna'));

        (new ConductConclaveRound())->execute($engine);
        $third = (new ConductConclaveRound())->execute($engine);
        $this->assertTrue($third->deadlocked);
        $this->assertSame(ConclavePhase::DEADLOCK, $engine->conclave->phase);
        $this->assertSame('colonna', $engine->conclave->compromiseCandidateId);

        $fourth = (new ConductConclaveRound())->execute($engine);
        $this->assertTrue($fourth->majority);
        $this->assertSame('colonna', $fourth->leaderId);
        $this->assertSame(ConclavePhase::ELECTED, $engine->conclave->phase);
        $this->assertGreaterThan(1, count($engine->conclave->ballots));
    }

    public function test_secular_interference_shifts_votes_but_cannot_name_the_pope(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        foreach (['ita-1', 'ita-2', 'ita-3', 'ita-4'] as $id) {
            $engine->cardinal($id)->corruption = 90;
        }

        (new OpenPapalVacancy())->execute($engine, '1348-12-06');

        $this->expectException(PapacyException::class);
        $this->expectExceptionMessage('A secular ruler cannot name the pope.');
        $engine->installPope('foix');
    }

    public function test_secular_patronage_can_swing_a_conclave_without_selecting_the_pope(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        foreach (['ita-1', 'ita-2', 'ita-3', 'ita-4'] as $id) {
            $engine->cardinal($id)->corruption = 90;
        }

        (new OpenPapalVacancy())->execute($engine, '1348-12-06');
        (new ApplySecularPapalPressure())->execute(
            $engine,
            'philip',
            SecularPressureKind::PATRONAGE,
            100,
            'foix',
            ['ita-1', 'ita-2', 'ita-3', 'ita-4'],
            'france'
        );
        (new ApplySecularPapalPressure())->execute(
            $engine,
            'philip',
            SecularPressureKind::THREAT,
            40,
            'foix',
            ['ita-1', 'ita-2'],
            'france'
        );

        PapacyFixture::seatCollege($engine);
        $ballot = (new ConductConclaveRound())->execute($engine);

        $this->assertTrue($ballot->majority);
        $this->assertSame('foix', $ballot->leaderId);
        $this->assertSame('foix', $engine->conclave->electedId);

        try {
            $engine->attemptImperialAppointment('philip', 'foix');
            $this->fail('Imperial appointment must stay closed in ordinary order.');
        } catch (PapacyException $e) {
            $this->assertSame('A secular ruler cannot name the pope.', $e->getMessage());
        }
    }

    public function test_antipope_splits_obedience_without_replacing_the_papal_office(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        (new OpenPapalVacancy())->execute($engine, '1348-12-06');
        PapacyFixture::elect($engine);
        (new AcceptPapalElection())->execute($engine);
        (new EnthronePope())->execute($engine);

        $officeId = $engine->papalOfficeId;
        $before = $engine->legitimacy->recognizedBp;

        $schism = (new RaiseAntipope())->execute(
            $engine,
            'foix',
            ['foix', 'fra-1'],
            ['avignon'],
            ['france']
        );

        $this->assertTrue($schism->open());
        $this->assertSame('orsini', $schism->recognizedClaimantId);
        $this->assertSame('foix', $schism->rivalClaimantId);
        $this->assertSame(PapacyStatus::SCHISM, $engine->papacyStatus);
        $this->assertSame('orsini', $engine->popeId);
        $this->assertSame($officeId, $engine->papalOfficeId);
        $this->assertLessThan($before, $engine->legitimacy->recognizedBp);
        $this->assertGreaterThan(0, $engine->legitimacy->rivalBp);
        $this->assertCount(2, $engine->obediencesOf('foix', PapalObedienceSubject::ELECTOR));
        $this->assertCount(1, $engine->obediencesOf('foix', PapalObedienceSubject::SEE));
        $this->assertCount(1, $engine->obediencesOf('foix', PapalObedienceSubject::REALM));

        (new RecognizePapalClaimant())->execute($engine, PapalObedienceSubject::SEE, 'reims', 'foix');
        $this->assertCount(2, $engine->obediencesOf('foix', PapalObedienceSubject::SEE));
    }

    public function test_pope_dying_during_apocalypse_still_elects_from_the_surviving_college(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        $engine->popeId = 'orsini';
        $engine->setApocalypseStage(ApocalypseStage::OPEN_RIFTS);
        $engine->see->accessible = false;
        $engine->see->occupied = true;

        foreach (['ita-1', 'ita-2', 'ita-3', 'ita-4', 'ita-5', 'foix', 'fra-1'] as $id) {
            $engine->killCardinal($id);
        }

        $bishop = PapacyFixture::cardinal('bishop-lyon', 'Lyon', 'none', 'conservative', ['orsini' => 40], 40);
        $engine->addBishop($bishop);

        $engine->killCardinal('orsini');
        $this->assertSame(PapacyStatus::VACANT, $engine->papacyStatus);
        $this->assertContains(ExtraordinaryElectionRule::REDUCED_COLLEGE, $engine->conclave->extraordinaryRules);
        $this->assertContains(ExtraordinaryElectionRule::RELOCATE_SEAT, $engine->conclave->extraordinaryRules);

        PapacyFixture::seatCollege($engine);
        $this->assertTrue($engine->conclave->seatRelocated);
        $this->assertSame('avignon', $engine->conclave->seatId);
        $this->assertSame('rome', $engine->conclave->papalSeeId);
        $this->assertCount(1, $engine->presentElectors());
        $this->assertArrayNotHasKey('bishop-lyon', $engine->college);

        $ballot = (new ConductConclaveRound())->execute($engine);
        $this->assertTrue($ballot->majority);
        $this->assertNotNull($engine->conclave->electedId);
        $this->assertSame(ConclavePhase::ELECTED, $engine->conclave->phase);
        $this->assertNotContains(ExtraordinaryElectionRule::SEDE_VACANTE_PERSISTS, $engine->conclave->extraordinaryRules);
    }

    public function test_extinct_college_expands_to_bishops_instead_of_inventing_a_pope(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        $engine->see->accessible = false;
        foreach (array_keys($engine->college) as $id) {
            $engine->killCardinal($id);
        }
        $engine->addBishop(PapacyFixture::cardinal('bishop-lyon', 'Lyon', 'none', 'conservative', [], 60, null, 40));
        $engine->addBishop(PapacyFixture::cardinal('bishop-reims', 'Reims', 'none', 'conservative', [], 55, null, 35));

        (new OpenPapalVacancy())->execute($engine, '1349-01-01', 'death');
        PapacyFixture::seatCollege($engine);

        $this->assertContains(ExtraordinaryElectionRule::EXPAND_TO_BISHOPS, $engine->conclave->extraordinaryRules);
        $this->assertCount(2, $engine->presentElectors());
        $this->assertTrue($engine->college['bishop-lyon']->isExtraordinary);

        $ballot = (new ConductConclaveRound())->execute($engine);
        $this->assertTrue($ballot->majority);
        $this->assertContains($engine->conclave->electedId, ['bishop-lyon', 'bishop-reims']);
    }

    public function test_no_electors_leaves_the_see_vacant(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        $engine->see->accessible = false;
        $engine->setApocalypseStage(ApocalypseStage::COLLAPSE_OF_OFFICES);
        foreach (array_keys($engine->college) as $id) {
            $engine->killCardinal($id);
        }

        (new OpenPapalVacancy())->execute($engine, '1349-03-01', 'death');
        $session = (new AssembleConclave())->execute($engine);

        $this->assertSame(ConclavePhase::VACANT, $session->phase);
        $this->assertContains(ExtraordinaryElectionRule::SEDE_VACANTE_PERSISTS, $session->extraordinaryRules);
        $this->assertNull($engine->popeId);
        $this->assertSame(PapacyStatus::VACANT, $engine->papacyStatus);

        try {
            $engine->attemptImperialAppointment('charles', 'bishop-lyon');
            $this->fail('Imperial appointment still needs a living cleric.');
        } catch (PapacyException $e) {
            $this->assertSame('Imperial appointment requires a living cleric.', $e->getMessage());
        }
    }

    public function test_inaccessible_rome_relocates_the_conclave_without_breaking_succession(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        $engine->see->accessible = false;
        $engine->see->occupied = true;
        $engine->see->communicationsDestroyed = true;

        (new OpenPapalVacancy())->execute($engine, '1348-12-06', 'death');
        $session = (new AssembleConclave())->execute($engine);
        $this->assertSame(ConclavePhase::ASSEMBLING, $session->phase);
        $this->assertContains(ExtraordinaryElectionRule::DELAYED_ASSEMBLY, $session->extraordinaryRules);
        $this->assertContains(ExtraordinaryElectionRule::RELOCATE_SEAT, $session->extraordinaryRules);
        $this->assertTrue($session->seatRelocated);
        $this->assertSame('avignon', $session->seatId);
        $this->assertSame('rome', $session->papalSeeId);

        $engine->tick();
        $this->assertSame(ConclavePhase::VOTING, $engine->conclave->phase);
        $this->assertCount(9, $engine->presentElectors());

        $ballot = (new ConductConclaveRound())->execute($engine);
        $this->assertTrue($ballot->majority);
        $this->assertSame('orsini', $engine->conclave->electedId);
        (new AcceptPapalElection())->execute($engine);
        (new EnthronePope())->execute($engine);
        $this->assertSame(PapacyStatus::OCCUPIED, $engine->papacyStatus);
        $this->assertSame('rome', $engine->see->seeId);
    }

    public function test_schism_reconciliation_returns_one_obedience_and_restores_legitimacy(): void
    {
        $engine = PapacyFixture::engine();
        PapacyFixture::majorityCollege($engine);
        (new OpenPapalVacancy())->execute($engine, '1348-12-06');
        PapacyFixture::elect($engine);
        (new AcceptPapalElection())->execute($engine);
        (new EnthronePope())->execute($engine);
        (new RaiseAntipope())->execute($engine, 'foix', ['foix', 'fra-1'], ['avignon'], ['france']);
        (new RecognizePapalClaimant())->execute($engine, PapalObedienceSubject::SEE, 'reims', 'foix');
        (new RecognizePapalClaimant())->execute($engine, PapalObedienceSubject::REALM, 'burgundy', 'orsini');

        $split = $engine->legitimacy->recognizedBp;
        $this->assertSame(PapacyStatus::SCHISM, $engine->papacyStatus);

        $healed = (new ReconcilePapalSchism())->execute($engine, 'orsini');
        $this->assertSame('healed', $healed->status);
        $this->assertNotNull($healed->healedDate);
        $this->assertSame(PapacyStatus::OCCUPIED, $engine->papacyStatus);
        $this->assertSame('orsini', $engine->popeId);
        $this->assertSame(0, $engine->legitimacy->rivalBp);
        $this->assertGreaterThan($split, $engine->legitimacy->recognizedBp);

        foreach ($engine->obediences as $obedience) {
            $this->assertSame('orsini', $obedience->claimantId);
        }
        $this->assertCount(2, $engine->obediencesOf('orsini', PapalObedienceSubject::SEE));
        $this->assertCount(2, $engine->obediencesOf('orsini', PapalObedienceSubject::REALM));
    }
}
