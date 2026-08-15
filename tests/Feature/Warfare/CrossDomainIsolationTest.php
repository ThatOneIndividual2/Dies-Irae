<?php

namespace Tests\Feature\Warfare;

use App\Domain\Warfare\Engine\WarfareKernel;
use App\Domain\Warfare\Enums\BelligerentKind;
use App\Domain\Warfare\Enums\WarGoalType;
use App\Domain\Warfare\Enums\WarfareKind;
use App\Domain\Warfare\Ports\RecordingAftermathSink;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\WarGoal;
use App\Domain\Warfare\Support\InMemoryWarfareWorld;
use PHPUnit\Framework\TestCase;

final class CrossDomainIsolationTest extends TestCase
{
    public function test_hostile_supernatural_ports_do_not_change_human_battle_math(): void
    {
        $clean = InMemoryWarfareWorld::europeSample();
        $hostile = InMemoryWarfareWorld::europeSample();
        $hostile->hostileSupernaturalReads();

        $cleanResult = $this->fightHumans($clean);
        $hostileResult = $this->fightHumans($hostile);

        $this->assertSame($cleanResult['kind'], WarfareKind::HUMAN_VS_HUMAN);
        $this->assertSame($cleanResult['winner'], $hostileResult['winner']);
        $this->assertSame($cleanResult['atk_killed'], $hostileResult['atk_killed']);
        $this->assertSame($cleanResult['def_killed'], $hostileResult['def_killed']);
        $this->assertSame($cleanResult['atk_morale'], $hostileResult['atk_morale']);
        $this->assertSame($cleanResult['def_morale'], $hostileResult['def_morale']);
        $this->assertSame($cleanResult['corpses'], $hostileResult['corpses']);
        $this->assertFalse($cleanResult['possession']);
        $this->assertFalse($hostileResult['possession']);
        $this->assertFalse($cleanResult['desecration']);
        $this->assertFalse($hostileResult['desecration']);
        $this->assertTrue($cleanResult['mundane_aftermath']);
        $this->assertTrue($hostileResult['mundane_aftermath']);
        $this->assertSame(0, $cleanResult['corruption']);
        $this->assertSame(0, $hostileResult['corruption']);
        $this->assertSame(0, $cleanResult['plague']);
        $this->assertSame(0, $hostileResult['plague']);
        $this->assertSame(0, $hostile->relicReads);
        $this->assertSame(0, $hostile->plagueReads);
        $this->assertSame(0, $hostile->clergyReads);
    }

    public function test_same_pipeline_used_for_human_and_demon_wars(): void
    {
        $world = InMemoryWarfareWorld::europeSample();
        $director = WarfareKernel::boot($world);
        $human = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $demon = new Belligerent(1, 99, BelligerentKind::DEMONIC_FACTION, 'Rift Court');
        $peer = new Belligerent(1, 11, BelligerentKind::REALM, 'Blois');

        $feudal = $director->declareWar($human, $peer, new WarGoal(WarGoalType::CONQUEST, 2), '1348-01-01');
        $infernal = $director->declareWar($human, $demon, new WarGoal(WarGoalType::SEAL_RIFT, 5), '1348-01-02');

        $this->assertSame(WarfareKind::HUMAN_VS_HUMAN, $feudal->kind);
        $this->assertSame(WarfareKind::HUMAN_VS_DEMON, $infernal->kind);
        $this->assertNotSame($director->warProfile($feudal)->occupationMode, $director->warProfile($infernal)->occupationMode);
        $this->assertFalse($director->warProfile($feudal)->allowsSupernaturalAftermath);
        $this->assertTrue($director->warProfile($infernal)->allowsSupernaturalAftermath);
    }

    /**
     * @return array<string, mixed>
     */
    private function fightHumans(InMemoryWarfareWorld $world): array
    {
        $sink = new RecordingAftermathSink();
        $director = WarfareKernel::boot($world, $sink);
        $a = new Belligerent(1, 10, BelligerentKind::REALM, 'Orleans');
        $b = new Belligerent(1, 11, BelligerentKind::REALM, 'Blois');
        $war = $director->declareWar($a, $b, new WarGoal(WarGoalType::VASSALIZE, null), '1348-06-01');
        $atk = $director->formArmy($war, $a, 1, $director->armies->menAtArms(30, 8, 10, 0), new Commander(1, 10, 0, 0, false, false, false));
        $def = $director->formArmy($war, $b, 1, $director->armies->menAtArms(30, 8, 10, 0), new Commander(2, 10, 0, 0, false, false, false));
        $battle = $director->fight($atk, $def, false);
        $report = $sink->reports[0];

        return [
            'kind' => $war->kind,
            'winner' => $battle->winnerSide,
            'atk_killed' => $battle->attackerCasualties->killed,
            'def_killed' => $battle->defenderCasualties->killed,
            'atk_morale' => $battle->attackerMoraleAfter,
            'def_morale' => $battle->defenderMoraleAfter,
            'corpses' => $battle->corpses,
            'possession' => $battle->possessionEvent,
            'desecration' => $battle->battlefieldDesecrated,
            'mundane_aftermath' => $report->isMundaneHumanAftermath(),
            'corruption' => $report->corruptionDelta,
            'plague' => $report->plagueExposure,
        ];
    }
}
