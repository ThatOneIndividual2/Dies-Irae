<?php

namespace Tests\Feature\Warfare;

use App\Actions\Warfare\AssignCommander;
use App\Actions\Warfare\DeclareWar;
use App\Actions\Warfare\FormArmy;
use App\Actions\Warfare\MarchArmy;
use App\Actions\Warfare\OccupyTerritory;
use App\Actions\Warfare\RaiseLevies;
use App\Actions\Warfare\RaiseMenAtArms;
use App\Actions\Warfare\ResolveBattle;
use App\Actions\Warfare\SettleWar;
use App\Actions\Warfare\TickSiege;
use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\DisplacementCause;
use App\Domain\Warfare\Engine\WarfareKernel;
use App\Domain\Warfare\Enums\ArmyNature;
use App\Domain\Warfare\Enums\BelligerentKind;
use App\Domain\Warfare\Enums\UnitCategory;
use App\Domain\Warfare\Enums\WarGoalType;
use App\Domain\Warfare\Enums\WarStatus;
use App\Domain\Warfare\Enums\WarfareKind;
use App\Domain\Warfare\Ports\RecordingAftermathSink;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\UnitStack;
use App\Domain\Warfare\State\WarGoal;
use App\Domain\Warfare\Support\InMemoryWarfareWorld;
use PHPUnit\Framework\TestCase;

final class HumanVsHumanBaselineTest extends TestCase
{
    public function test_feudal_war_raises_levies_marches_fights_occupies_and_settles(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $sink = new RecordingAftermathSink();
        $director = WarfareKernel::boot($world, $sink);
        $orleans = $this->lord(10, 'Orleans');
        $blois = $this->lord(11, 'Blois');

        $war = (new DeclareWar($director))->execute(
            $orleans,
            $blois,
            new WarGoal(WarGoalType::CONQUEST, 2),
            '1348-03-01'
        );
        $this->assertSame(WarfareKind::HUMAN_VS_HUMAN, $war->kind);
        $this->assertTrue($war->active());

        $levies = (new RaiseLevies($director))->execute($orleans);
        $this->assertNotEmpty($levies);
        $this->assertSame(UnitCategory::LEVY, $levies[0]->category);
        $this->assertSame(52, $levies[0]->men);

        $maa = (new RaiseMenAtArms($director))->execute(40, 10, 15, 2);
        $this->assertCount(4, $maa);

        $commander = new Commander(100, 12, 20, 0, false, false, false);
        $attacker = (new FormArmy($director))->execute($war, $orleans, 1, $maa, $commander);
        (new AssignCommander())->execute($attacker, $commander);
        $defender = (new FormArmy($director))->execute(
            $war,
            $blois,
            2,
            (new RaiseMenAtArms($director))->execute(20, 5, 8, 0),
            new Commander(101, 8, 10, 0, false, false, false)
        );

        $this->assertSame(ArmyNature::HUMAN, $attacker->nature);
        $this->assertFalse($attacker->starving);
        $this->assertGreaterThan(0, $attacker->men());

        $march = (new MarchArmy($director))->execute($attacker, 2);
        $this->assertSame(2, $march->toTerritoryId);
        $this->assertFalse($march->blockedByPortal);
        $this->assertSame(0, $march->terrainCorruptionDelta);
        $this->assertLessThan(80, $attacker->supply);

        $siege = (new TickSiege($director))->open($attacker, 2, 20);
        $this->assertSame('siege', $siege->method);
        $siege = (new TickSiege($director))->execute($siege, $attacker);
        $this->assertGreaterThan(0, $siege->progress);

        $battle = (new ResolveBattle($director))->execute($attacker, $defender, true);
        $this->assertTrue($battle->attackerCasualties->mundaneOnly());
        $this->assertTrue($battle->defenderCasualties->mundaneOnly());
        $this->assertFalse($battle->possessionEvent);
        $this->assertFalse($battle->battlefieldDesecrated);
        $this->assertGreaterThan(0, $battle->corpses);

        $occ = (new OccupyTerritory($director))->execute($war, 2, 10);
        $this->assertTrue($occ->militaryControlTransferred);
        $this->assertFalse($occ->legalOwnershipTransferred);
        $this->assertFalse($occ->hellOverlayApplied);
        $this->assertFalse($occ->infiltrationApplied);
        $this->assertSame(10, $occ->controllerBelligerentId);
        $this->assertSame(11, $occ->ownerBelligerentId);

        $ended = (new SettleWar($director))->execute($war, 10, 'conquest');
        $this->assertSame(WarStatus::ENDED, $ended->status);
        $this->assertSame(10, $ended->winnerBelligerentId);

        $this->assertCount(1, $sink->reports);
        $this->assertTrue($sink->reports[0]->isMundaneHumanAftermath());
        $this->assertSame(DisplacementCause::WAR, $sink->reports[0]->refugeeCause);
        $this->assertContains($sink->reports[0]->corpseHandling, [CorpseHandling::MASS_GRAVE, CorpseHandling::CONSECRATED]);
        $this->assertSame(0, $world->relicReads);
        $this->assertSame(0, $world->plagueReads);
    }

    public function test_levy_math_matches_feudalism_contract_then_scales_by_population(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $world->holdings[1] = new \App\Domain\Warfare\Ports\HoldingLevySnapshot(
            1, 1, 1, 10, 50, 40, 4000, 8000, \App\Domain\Enums\RuinState::FUNCTIONING, 3, false
        );
        $director = WarfareKernel::boot($world);
        $due = $director->levies->holdingLevy($world->holdings[1]);
        $this->assertSame(10, $due); // floor(50*40/100)=20, then * 4000/8000 = 10
    }

    public function test_ruined_holdings_raise_no_levies(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $world->holdings[1] = new \App\Domain\Warfare\Ports\HoldingLevySnapshot(
            1, 1, 1, 10, 80, 40, 8000, 8000, \App\Domain\Enums\RuinState::RUINED, 3, false
        );
        $director = WarfareKernel::boot($world);
        $this->assertSame(0, $director->levies->holdingLevy($world->holdings[1]));
    }

    public function test_conquest_goal_is_legal_and_seal_rift_is_not(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $director = WarfareKernel::boot($world);
        $a = $this->lord(10, 'Orleans');
        $b = $this->lord(11, 'Blois');
        (new DeclareWar($director))->execute($a, $b, new WarGoal(WarGoalType::CONQUEST, 2), '1348-03-01');

        $this->expectException(\InvalidArgumentException::class);
        $director->doctrines->assertGoalAllowed(
            $director->warProfile($director->wars[1]),
            new WarGoal(WarGoalType::SEAL_RIFT, 5)
        );
    }

    private function lord(int $id, string $name): Belligerent
    {
        return new Belligerent(1, $id, BelligerentKind::REALM, $name, 10);
    }
}
