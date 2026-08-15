<?php

namespace Tests\Feature\Warfare;

use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\DisplacementCause;
use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Engine\SequenceRandom;
use App\Domain\Warfare\Engine\WarfareKernel;
use App\Domain\Warfare\Enums\ArmyNature;
use App\Domain\Warfare\Enums\BelligerentKind;
use App\Domain\Warfare\Enums\OccupationMode;
use App\Domain\Warfare\Enums\SupplyKind;
use App\Domain\Warfare\Enums\UnitCategory;
use App\Domain\Warfare\Enums\WarGoalType;
use App\Domain\Warfare\Enums\WarfareKind;
use App\Domain\Warfare\Ports\RecordingAftermathSink;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\UnitStack;
use App\Domain\Warfare\State\WarGoal;
use App\Domain\Warfare\Support\InMemoryWarfareWorld;
use PHPUnit\Framework\TestCase;

final class DoctrineDifferenceTest extends TestCase
{
    public function test_each_warfare_kind_resolves_to_a_distinct_profile(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $d = WarfareKernel::boot($world);
        $human = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $peer = new Belligerent(1, 11, BelligerentKind::REALM, 'Blois');
        $cult = new Belligerent(1, 30, BelligerentKind::CULT, 'Wound');
        $demon = new Belligerent(1, 99, BelligerentKind::DEMONIC_FACTION, 'Rift');
        $order = new Belligerent(1, 20, BelligerentKind::HOLY_ORDER, 'Order');
        $rot = new Belligerent(1, 40, BelligerentKind::CORRUPTED_HOST, 'Broken');

        $kinds = [
            $d->declareWar($human, $peer, new WarGoal(WarGoalType::CONQUEST, 2), '1348-01-01')->kind,
            $d->declareWar($human, $cult, new WarGoal(WarGoalType::SUPPRESS_CULT, 2), '1348-01-02')->kind,
            $d->declareWar($human, $demon, new WarGoal(WarGoalType::SEAL_RIFT, 5), '1348-01-03')->kind,
            $d->declareWar($order, $cult, new WarGoal(WarGoalType::HOLY_WAR, 2), '1348-01-04')->kind,
            $d->declareWar($human, $rot, new WarGoal(WarGoalType::PURGE_CORRUPTION, 3), '1348-01-05')->kind,
        ];

        $this->assertSame([
            WarfareKind::HUMAN_VS_HUMAN,
            WarfareKind::HUMAN_VS_CULT,
            WarfareKind::HUMAN_VS_DEMON,
            WarfareKind::HOLY_ORDER,
            WarfareKind::CORRUPTED_ARMY,
        ], $kinds);
        $this->assertCount(5, array_unique($kinds));
    }

    public function test_force_supply_kinds_differ(): void
    {
        $this->assertSame(SupplyKind::FOOD, ForceProfile::human()->supplyKind);
        $this->assertSame(SupplyKind::PLUNDER, ForceProfile::cult()->supplyKind);
        $this->assertSame(SupplyKind::PORTAL, ForceProfile::demonic()->supplyKind);
        $this->assertSame(SupplyKind::TITHE, ForceProfile::holyOrder()->supplyKind);
        $this->assertSame(SupplyKind::DEVOUR, ForceProfile::corrupted()->supplyKind);
        $this->assertTrue(ForceProfile::human()->starvable);
        $this->assertFalse(ForceProfile::demonic()->starvable);
        $this->assertTrue(ForceProfile::demonic()->requiresPortal);
        $this->assertFalse(ForceProfile::human()->requiresPortal);
    }

    public function test_cult_war_infiltrates_instead_of_seizing_legal_title(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $sink = new RecordingAftermathSink();
        $d = WarfareKernel::boot($world, $sink);
        $human = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $cult = new Belligerent(1, 30, BelligerentKind::CULT, 'Wound');
        $war = $d->declareWar($human, $cult, new WarGoal(WarGoalType::SUPPRESS_CULT, 2), '1348-04-01');
        $this->assertSame(OccupationMode::INFILTRATION, $d->warProfile($war)->occupationMode);

        $occ = $d->occupy($war, 2, 30);
        $this->assertTrue($occ->infiltrationApplied);
        $this->assertFalse($occ->militaryControlTransferred);
        $this->assertFalse($occ->legalOwnershipTransferred);
        $this->assertSame(11, $occ->ownerBelligerentId);

        $atk = $d->formArmy($war, $human, 2, $d->armies->menAtArms(20, 0, 0, 0), new Commander(1, 10, 40, 0, false, true, false));
        $def = $d->formArmy($war, $cult, 2, [new UnitStack(UnitCategory::CULTIST, 40, 1.0, 'cells')], new Commander(2, 8, 0, 20, false, false, false));
        $d->fight($atk, $def, true);
        $this->assertTrue($sink->reports[0]->infiltrationApplied);
        $this->assertLessThan(0, $sink->reports[0]->localFaithDelta);
        $this->assertFalse($sink->reports[0]->isMundaneHumanAftermath());
    }

    public function test_demons_do_not_use_food_supply_or_legal_occupation(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $sink = new RecordingAftermathSink();
        $d = WarfareKernel::boot($world, $sink, new SequenceRandom([1, 1, 1, 1]));
        $human = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $demon = new Belligerent(1, 99, BelligerentKind::DEMONIC_FACTION, 'Rift');
        $war = $d->declareWar($demon, $human, new WarGoal(WarGoalType::SEAL_RIFT, 5), '1348-05-01');
        $this->assertTrue($d->warProfile($war)->resilientToOccupationRules);
        $this->assertSame(OccupationMode::HELL_OVERLAY, $d->warProfile($war)->occupationMode);

        $host = $d->formArmy(
            $war,
            $demon,
            5,
            [new UnitStack(UnitCategory::DEMONIC, 80, 1.0, 'manifest host')],
            new Commander(9, 18, 0, 80, true, false, false),
            5
        );
        $this->assertSame(ArmyNature::DEMONIC, $host->nature);
        $this->assertGreaterThan(0, $host->manifestationRemaining);

        $d->supply->tick($host, ForceProfile::demonic());
        $this->assertFalse($host->starving);
        $this->assertSame(70, $host->supply); // 50 + floor(portal 80 / 4), not food drain
        $d->supply->tick($host, ForceProfile::demonic());
        $this->assertSame(70, $host->supply);

        $move = $d->movement->march($host, ForceProfile::demonic(), 4);
        $this->assertGreaterThan(0, $move->terrainCorruptionDelta);

        $occ = $d->occupy($war, 4, 99);
        $this->assertTrue($occ->hellOverlayApplied);
        $this->assertFalse($occ->militaryControlTransferred);
        $this->assertFalse($occ->legalOwnershipTransferred);
        $this->assertSame(10, $occ->ownerBelligerentId);

        $levy = $d->formArmy($war, $human, 4, $d->armies->menAtArms(25, 5, 5, 0), new Commander(3, 9, 10, 0, false, false, false));
        $battle = $d->fight($host, $levy, true);
        $this->assertGreaterThan(0, $battle->attackerCasualties->banished + $battle->defenderCasualties->banished);
        $this->assertTrue($battle->battlefieldDesecrated);
        $this->assertSame(CorpseHandling::DESECRATED, $sink->reports[0]->corpseHandling);
        $this->assertSame(DisplacementCause::HELL, $sink->reports[0]->refugeeCause);
        $this->assertGreaterThan(0, $sink->reports[0]->corruptionDelta);
        $this->assertGreaterThan(0, $sink->reports[0]->plagueExposure);
    }

    public function test_demon_host_collapses_without_a_portal(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $world->portals = [];
        $d = WarfareKernel::boot($world);
        $human = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $demon = new Belligerent(1, 99, BelligerentKind::DEMONIC_FACTION, 'Rift');
        $war = $d->declareWar($demon, $human, new WarGoal(WarGoalType::SEAL_RIFT, 5), '1348-05-01');
        $host = $d->formArmy($war, $demon, 5, [new UnitStack(UnitCategory::DEMONIC, 50, 1.0, 'unmoored')], null, null);
        $host->manifestationRemaining = 40;
        $d->supply->tick($host, ForceProfile::demonic());
        $this->assertSame(0, $host->men());
        $this->assertSame(0, $host->morale);
    }

    public function test_holy_order_raises_consecrated_units_and_uses_tithe_supply(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $sink = new RecordingAftermathSink();
        $d = WarfareKernel::boot($world, $sink);
        $order = new Belligerent(1, 20, BelligerentKind::HOLY_ORDER, 'Order');
        $cult = new Belligerent(1, 30, BelligerentKind::CULT, 'Wound');
        $war = $d->declareWar($order, $cult, new WarGoal(WarGoalType::HOLY_WAR, 2), '1348-07-01');
        $this->assertSame(WarfareKind::HOLY_ORDER, $war->kind);
        $this->assertSame(SupplyKind::TITHE, $d->forceProfile($order)->supplyKind);

        $army = $d->formArmy($war, $order, 4, $d->armies->menAtArms(10, 4, 0, 0), new Commander(7, 14, 60, 0, false, true, true));
        $this->assertGreaterThan(0, $army->consecratedMen());
        $this->assertSame(ArmyNature::HOLY_ORDER, $army->nature);

        $occ = $d->occupy($war, 4, 20);
        $this->assertSame(OccupationMode::COMMANDERY, $occ->mode);
        $this->assertTrue($occ->militaryControlTransferred);
        $this->assertFalse($occ->legalOwnershipTransferred);
    }

    public function test_corrupted_army_devours_and_spreads_rot_while_still_occupying(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $world->pops[3] = ['population' => 80, 'baseline' => 3500];
        $sink = new RecordingAftermathSink();
        $d = WarfareKernel::boot($world, $sink);
        $human = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $rot = new Belligerent(1, 40, BelligerentKind::CORRUPTED_HOST, 'Broken');
        $war = $d->declareWar($rot, $human, new WarGoal(WarGoalType::PURGE_CORRUPTION, 1), '1348-08-01');
        $this->assertSame(WarfareKind::CORRUPTED_ARMY, $war->kind);

        $host = $d->formArmy($war, $rot, 3, [new UnitStack(UnitCategory::CORRUPTED, 60, 1.0, 'broken')], new Commander(8, 11, 0, 40, true, false, false));
        $this->assertTrue($d->forceProfile($rot)->devoursPopulation);
        $host->supply = 16;
        $d->supply->tick($host, ForceProfile::corrupted());
        $this->assertTrue($host->starving);

        $occ = $d->occupy($war, 3, 40);
        $this->assertTrue($occ->militaryControlTransferred);
        $this->assertTrue($occ->corruptionApplied);
        $this->assertFalse($occ->hellOverlayApplied);

        $levy = $d->formArmy($war, $human, 3, $d->armies->menAtArms(20, 0, 0, 0), new Commander(4, 8, 5, 0, false, false, false));
        $d->fight($host, $levy, true);
        $this->assertFalse($sink->reports[0]->isMundaneHumanAftermath());
        $this->assertGreaterThan(0, $sink->reports[0]->corruptionDelta);
    }

    public function test_human_occupation_still_keeps_legal_owner_when_other_doctrines_exist_in_the_kernel(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $d = WarfareKernel::boot($world);
        $a = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $b = new Belligerent(1, 11, BelligerentKind::REALM, 'Blois');
        $war = $d->declareWar($a, $b, new WarGoal(WarGoalType::CONQUEST, 2), '1348-01-01');
        $occ = $d->occupy($war, 2, 10);
        $this->assertTrue($occ->militaryControlTransferred);
        $this->assertFalse($occ->legalOwnershipTransferred);
        $this->assertSame(OccupationMode::MILITARY_CONTROL, $occ->mode);
    }
}
