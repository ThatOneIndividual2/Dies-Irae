<?php

namespace Tests\Unit\Warfare;

use App\Domain\Warfare\Doctrine\DoctrineResolver;
use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Doctrine\WarProfile;
use App\Domain\Warfare\Engine\WarfareBalance;
use App\Domain\Warfare\Enums\BelligerentKind;
use App\Domain\Warfare\Enums\WarGoalType;
use App\Domain\Warfare\Enums\WarfareKind;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\Casualties;
use App\Domain\Warfare\State\WarGoal;
use PHPUnit\Framework\TestCase;

final class WarfareContractsTest extends TestCase
{
    public function test_donor_levy_formula(): void
    {
        $balance = new WarfareBalance();
        $this->assertSame(20, $balance->contractLevyDue(50, 40));
        $this->assertSame(0, $balance->contractLevyDue(50, 0));
        $this->assertSame(50, $balance->contractLevyDue(50, 100));
        $this->assertSame(50, $balance->contractLevyDue(50, 140));
    }

    public function test_donor_unit_weights_preserved(): void
    {
        $balance = new WarfareBalance();
        $this->assertSame(1.0, $balance->unitPower['infantry']);
        $this->assertSame(1.2, $balance->unitPower['archers']);
        $this->assertSame(2.0, $balance->unitPower['cavalry']);
        $this->assertSame(3.0, $balance->unitPower['siege']);
        $this->assertSame(100, $balance->pressureToOccupy);
        $this->assertSame(0.08, $balance->tickCasualtyRate);
    }

    public function test_human_war_profile_is_the_sealed_baseline(): void
    {
        $p = WarProfile::humanVsHuman();
        $this->assertFalse($p->allowsSupernaturalAftermath);
        $this->assertFalse($p->allowsPossessionEvents);
        $this->assertFalse($p->desecratesBattlefield);
        $this->assertFalse($p->appliesHellOverlay);
        $this->assertFalse($p->appliesInfiltration);
        $this->assertFalse($p->allowsPlagueExposure);
        $this->assertFalse($p->resilientToOccupationRules);
        $this->assertTrue($p->transfersMilitaryControl);
        $this->assertFalse($p->transfersLegalOwnershipOnOccupy);
        $this->assertTrue($p->mundaneCorpses);
    }

    public function test_self_war_is_rejected(): void
    {
        $a = new Belligerent(1, 10, BelligerentKind::REALM, 'A');
        $this->expectException(\InvalidArgumentException::class);
        new \App\Domain\Warfare\State\War(1, 1, $a, $a, WarfareKind::HUMAN_VS_HUMAN, new WarGoal(WarGoalType::CONQUEST, 1), '1348-01-01');
    }

    public function test_cross_world_declare_is_rejected(): void
    {
        $resolver = new DoctrineResolver();
        $a = new Belligerent(1, 10, BelligerentKind::REALM, 'A');
        $b = new Belligerent(2, 11, BelligerentKind::REALM, 'B');
        $this->expectException(\InvalidArgumentException::class);
        \App\Domain\Support\WorldBoundary::assertSameWorldIds('war', $a->worldId, $b->worldId);
        $resolver->warKind($a, $b);
    }

    public function test_mundane_casualties_have_no_banishment_or_possession(): void
    {
        $c = new Casualties(10, 8, 5, 2, 0, 0, 0);
        $this->assertTrue($c->mundaneOnly());
        $this->assertSame(25, $c->totalRemoved());
        $this->assertFalse((new Casualties(10, 0, 0, 0, 4, 0, 0))->mundaneOnly());
    }

    public function test_holy_order_versus_demon_uses_demon_war_physics(): void
    {
        $resolver = new DoctrineResolver();
        $order = new Belligerent(1, 20, BelligerentKind::HOLY_ORDER, 'Order');
        $demon = new Belligerent(1, 99, BelligerentKind::DEMONIC_FACTION, 'Rift');
        $this->assertSame(WarfareKind::HUMAN_VS_DEMON, $resolver->warKind($order, $demon));
        $this->assertSame(ForceProfile::holyOrder()->supplyKind, $resolver->forceProfile($order)->supplyKind);
        $this->assertSame(ForceProfile::demonic()->supplyKind, $resolver->forceProfile($demon)->supplyKind);
    }
}
