<?php

namespace Tests\Feature\Catastrophe;

use App\Domain\Enums\RuinState;
use App\Domain\Enums\SocialClass;
use App\Domain\Population\ApplyMassMortality;
use App\Domain\Population\RuinModifiers;
use App\Domain\Population\RuinStateMachine;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class RuinStateTest extends DomainTestCase
{
    public function test_settlements_progress_functioning_strained_depopulated_abandoned(): void
    {
        $engine = WorldFixture::engine();
        $town = WorldFixture::city($engine, 'aix', 'Aix');
        $this->assertSame(RuinState::FUNCTIONING, $town->ruinState);

        $apply = new ApplyMassMortality();
        $peak = $town->souls();

        $apply->apply($town, (int) floor($peak * 0.40), [SocialClass::PEASANTS => 100]);
        $this->assertSame(RuinState::STRAINED, $town->ruinState);
        $this->assertLessThan(10000, $town->ruinModifiers()->tax);

        $apply->apply($town, (int) floor($town->souls() * 0.70), [SocialClass::PEASANTS => 100]);
        $this->assertContains($town->ruinState, [RuinState::DEPOPULATED, RuinState::ABANDONED]);

        $apply->apply($town, $town->souls(), [SocialClass::PEASANTS => 100]);
        $this->assertSame(RuinState::ABANDONED, $town->ruinState);
        $this->assertSame(0, $town->taxBase());
        $this->assertSame(0, $town->levyBase());
        $this->assertFalse($town->isViable());
        $this->assertFalse($town->ruinModifiers()->parish);
        $this->assertFalse($town->ruinModifiers()->recruit);
    }

    public function test_abandoned_settlements_decay_to_ruined(): void
    {
        $engine = WorldFixture::engine();
        $town = WorldFixture::town($engine, 'empty', 'Empty');
        (new ApplyMassMortality())->apply($town, $town->souls(), [SocialClass::PEASANTS => 100]);
        $this->assertSame(RuinState::ABANDONED, $town->ruinState);

        $machine = new RuinStateMachine();
        for ($i = 0; $i < RuinStateMachine::ABANDONED_TO_RUINED_TICKS + 1; $i++) {
            $machine->apply($town, false);
        }

        $this->assertSame(RuinState::RUINED, $town->ruinState);
        $this->assertSame(0, $town->ruinModifiers()->food);
        $this->assertGreaterThan(0, $town->ruinModifiers()->armyAttrition);
    }

    public function test_despair_and_corruption_open_a_corrupted_ruin(): void
    {
        $engine = WorldFixture::engine();
        $town = WorldFixture::town($engine, 'wounded', 'Wounded');
        $town->despair = 80;
        $town->corruption = 70;
        (new RuinStateMachine())->apply($town, false);

        $this->assertSame(RuinState::CORRUPTED, $town->ruinState);
        $mods = new RuinModifiers(RuinState::CORRUPTED);
        $this->assertFalse($mods->parish);
        $this->assertGreaterThan(0, $mods->rebellion);
        $this->assertGreaterThan(0, $mods->corruptionDrift);
    }

    public function test_hell_occupation_overruns_the_settlement(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::town($engine, 'rift', 'Rift');
        $engine->occupyWithHell('rift');

        $town = $engine->settlement('rift');
        $this->assertSame(RuinState::OVERRUN, $town->ruinState);
        $this->assertFalse($town->ruinModifiers()->acceptsRefugees);
        $this->assertSame(0, $town->taxBase());
        $this->assertGreaterThan(5000, $town->ruinModifiers()->armyAttrition);
    }

    public function test_each_ruin_state_changes_tax_levy_or_parish_rules(): void
    {
        $seenTax = [];
        $seenLevy = [];
        $seenParish = [];
        foreach (RuinState::all() as $state) {
            $mods = new RuinModifiers($state);
            $seenTax[$state] = $mods->tax;
            $seenLevy[$state] = $mods->levy;
            $seenParish[$state] = $mods->parish;
        }

        $this->assertGreaterThan($seenTax[RuinState::STRAINED], $seenTax[RuinState::FUNCTIONING]);
        $this->assertGreaterThan($seenTax[RuinState::DEPOPULATED], $seenTax[RuinState::STRAINED]);
        $this->assertSame(0, $seenTax[RuinState::ABANDONED]);
        $this->assertSame(0, $seenTax[RuinState::RUINED]);
        $this->assertSame(0, $seenLevy[RuinState::OVERRUN]);
        $this->assertTrue($seenParish[RuinState::FUNCTIONING]);
        $this->assertFalse($seenParish[RuinState::CORRUPTED]);
        $this->assertFalse($seenParish[RuinState::OVERRUN]);
    }
}
