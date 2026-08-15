<?php

namespace Tests\Feature\Careers;

use App\Domain\Ai\Constraints\CareerAiGate;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;
use App\Domain\Careers\CareerEngine;
use App\Domain\Careers\CareerException;
use App\Domain\Careers\CareerNpcAdvisor;
use App\Domain\Careers\CharacterSkills;
use App\Domain\Enums\CareerKey;
use App\Domain\Enums\EducationSource;
use App\Domain\Enums\LifeEventKey;
use App\Domain\Enums\LifeStateKey;
use App\Domain\Enums\NpcIntent;
use App\Domain\Enums\SkillKey;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use Tests\DomainTestCase;

class CareerTrajectoryTest extends DomainTestCase
{
    private CareerEngine $engine;
    private CareerNpcAdvisor $npc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CareerEngine();
        $this->npc = new CareerNpcAdvisor($this->engine);
    }

    public function test_noble_child_to_knight_to_duke(): void
    {
        $person = $this->engine->person(1, 'gilles', 'Gilles', 8);
        $this->engine->completeEducation($person, EducationSource::NOBLE_HOUSEHOLD);
        $this->engine->assignCareer($person, CareerKey::PAGE);

        $person->age = 14;
        $this->engine->assignCareer($person, CareerKey::SQUIRE);

        $person->age = 21;
        $this->engine->assignCareer($person, CareerKey::KNIGHT);

        $person->age = 25;
        $this->engine->apply($person, LifeEventKey::INHERIT, ['rank' => TitleRank::DUCHY]);

        $this->assertSame(CareerKey::LANDED_NOBLE, $person->career);
        $this->assertSame(TitleRank::DUCHY, $person->titleRank);
        $this->assertContains(CareerKey::PAGE, $person->careerKeys());
        $this->assertContains(CareerKey::SQUIRE, $person->careerKeys());
        $this->assertContains(CareerKey::KNIGHT, $person->careerKeys());
        $this->assertTrue($this->npc->wouldAcceptAppointment($person, 'secular', TitleRank::DUCHY));
        $this->assertGreaterThan(5, $person->skills->martial());
        $this->assertArrayNotHasKey('faith', $person->publicSkills());
        $this->assertArrayNotHasKey('piety', $person->publicSkills());
    }

    public function test_novice_to_bishop(): void
    {
        $person = $this->engine->person(1, 'etienne', 'Etienne', 12);
        $this->engine->completeEducation($person, EducationSource::MONASTERY_SCHOOL);
        $this->engine->apply($person, LifeEventKey::ENTER_CLERGY);
        $this->assertSame(CareerKey::NOVICE, $person->career);
        $this->engine->apply($person, LifeEventKey::TAKE_VOWS, ['solemn' => false]);

        $person->age = 18;
        $this->engine->assignCareer($person, CareerKey::MONK);
        $this->engine->apply($person, LifeEventKey::TAKE_VOWS, ['solemn' => true]);

        $person->age = 25;
        $this->engine->apply($person, LifeEventKey::ORDAIN);
        $this->assertSame(CareerKey::PRIEST, $person->career);
        $this->assertTrue($person->hasLifeState(LifeStateKey::ORDAINED));

        $this->engine->apply($person, LifeEventKey::RECEIVE_BENEFICE);

        $person->age = 32;
        $this->engine->assignCareer($person, CareerKey::BISHOP);

        $suit = $this->engine->suitability($person, 'spiritual', SpiritualOfficeRank::BISHOP);
        $this->assertTrue($suit->eligible);
        $this->assertTrue($this->npc->wouldAcceptAppointment($person, 'spiritual', SpiritualOfficeRank::BISHOP));
        $this->assertFalse($this->npc->wouldAcceptAppointment($person, 'secular', TitleRank::COUNTY));
        $this->assertGreaterThan(5, $person->skills->theology());
        $this->assertGreaterThan(5, $person->skills->pietyReputation());
        $this->assertContains(NpcIntent::GOVERN_SEE, $this->npc->behavior($person)->intents);
    }

    public function test_priest_to_heretic(): void
    {
        $person = $this->priest();
        $before = $person->skills->pietyReputation();
        $this->engine->apply($person, LifeEventKey::BECOME_HERETIC);

        $this->assertSame(CareerKey::PRIEST, $person->career);
        $this->assertTrue($person->hasLifeState(LifeStateKey::HERETIC));
        $this->assertLessThan($before, $person->skills->pietyReputation());
        $this->assertFalse($this->engine->suitability($person, 'spiritual', SpiritualOfficeRank::BISHOP)->eligible);
        $profile = $this->npc->behavior($person);
        $this->assertTrue($profile->has(NpcIntent::PREACH_HERESY));
        $this->assertTrue($profile->hostileToChurch);
        $this->assertFalse($profile->willAcceptSpiritualOffice);
        $this->assertArrayNotHasKey('hope', $person->publicSkills());
        $this->assertArrayNotHasKey('lust', $person->publicSkills());
    }

    public function test_knight_to_holy_order(): void
    {
        $person = $this->engine->person(1, 'hugues', 'Hugues', 8);
        $this->engine->completeEducation($person, EducationSource::MILITARY_HOUSEHOLD);
        $this->engine->assignCareer($person, CareerKey::PAGE);
        $person->age = 15;
        $this->engine->assignCareer($person, CareerKey::SQUIRE);
        $person->age = 21;
        $this->engine->assignCareer($person, CareerKey::KNIGHT);

        $this->engine->apply($person, LifeEventKey::JOIN_HOLY_ORDER);

        $this->assertSame(CareerKey::KNIGHT, $person->career);
        $this->assertTrue($person->hasLifeState(LifeStateKey::HOLY_ORDER));
        $this->assertTrue($person->hasLifeState(LifeStateKey::SOLEMN_VOWS));
        $this->assertContains(NpcIntent::CRUSADE, $this->npc->behavior($person)->intents);

        $this->expectException(CareerException::class);
        $this->engine->apply($person, LifeEventKey::MARRY);
    }

    public function test_courtier_to_exiled_claimant(): void
    {
        $person = $this->engine->person(1, 'robert', 'Robert', 16);
        $this->engine->completeEducation($person, EducationSource::NOBLE_HOUSEHOLD);
        $this->engine->assignCareer($person, CareerKey::COURTIER);
        $this->engine->apply($person, LifeEventKey::EXILE, ['claimant' => true]);

        $this->assertSame(CareerKey::COURTIER, $person->career);
        $this->assertTrue($person->hasLifeState(LifeStateKey::EXILE));
        $this->assertTrue($person->hasLifeState(LifeStateKey::CLAIMANT));
        $this->assertTrue($person->hasClaim);
        $profile = $this->npc->behavior($person);
        $this->assertSame('exiled_claimant', $profile->posture);
        $this->assertTrue($profile->has(NpcIntent::PLOT_RETURN));
        $this->assertTrue($profile->has(NpcIntent::PRESS_CLAIM));
    }

    public function test_novice_may_leave_but_ordained_priest_may_not(): void
    {
        $novice = $this->engine->person(1, 'novice', 'Adam', 12);
        $this->engine->apply($novice, LifeEventKey::ENTER_CLERGY);
        $this->engine->apply($novice, LifeEventKey::LEAVE_ROLE, ['to' => CareerKey::SCHOLAR]);
        $this->assertSame(CareerKey::SCHOLAR, $novice->career);
        $this->assertFalse($novice->hasLifeState(LifeStateKey::IN_CLERGY));

        $priest = $this->priest();
        $this->expectException(CareerException::class);
        $this->engine->apply($priest, LifeEventKey::LEAVE_ROLE);
    }

    public function test_skills_reject_hidden_spiritual_state(): void
    {
        $skills = new CharacterSkills();
        $this->assertSame(SkillKey::all(), array_keys($skills->toArray()));
        $this->expectException(\InvalidArgumentException::class);
        $skills->set('piety', 10);
    }

    public function test_widowed_courtier_can_marry_again(): void
    {
        $person = $this->engine->person(1, 'alice', 'Alice', 18);
        $this->engine->assignCareer($person, CareerKey::COURTIER);
        $this->engine->apply($person, LifeEventKey::MARRY);
        $this->engine->apply($person, LifeEventKey::WIDOW);
        $this->assertTrue($person->hasLifeState(LifeStateKey::WIDOWED));
        $this->engine->apply($person, LifeEventKey::MARRY);
        $this->assertTrue($person->hasLifeState(LifeStateKey::MARRIED));
        $this->assertFalse($person->hasLifeState(LifeStateKey::WIDOWED));
    }

    public function test_imprisoned_courtier_is_captive_to_ai(): void
    {
        $person = $this->engine->person(1, 'jailed', 'Jailed', 22);
        $this->engine->assignCareer($person, CareerKey::COURTIER);
        $this->engine->apply($person, LifeEventKey::IMPRISON);

        $this->assertFalse($this->engine->suitability($person, 'secular', TitleRank::COUNTY)->eligible);
        $situation = Situation::quiet();
        $this->npc->applyToSituation($situation, $person);
        $this->assertSame('captive', $situation->careerPosture);

        $gate = new CareerAiGate();
        $this->assertNotNull($gate->reject(new CandidateAction('press_claim', 'Press claim'), $situation));
        $this->assertNull($gate->reject(new CandidateAction('wait', 'Wait'), $situation));
    }

    private function priest()
    {
        $person = $this->engine->person(1, 'andre', 'Andre', 12);
        $this->engine->completeEducation($person, EducationSource::CATHEDRAL_SCHOOL);
        $this->engine->apply($person, LifeEventKey::ENTER_CLERGY);
        $person->age = 18;
        $this->engine->assignCareer($person, CareerKey::MONK);
        $person->age = 25;
        $this->engine->apply($person, LifeEventKey::ORDAIN);

        return $person;
    }
}
