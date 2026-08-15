<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Doctrine\WarProfile;
use App\Domain\Warfare\Ports\CatastrophePort;
use App\Domain\Warfare\Ports\SpiritualPort;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\BattleResult;
use App\Domain\Warfare\State\Casualties;
use App\Domain\Warfare\State\UnitStack;

final class BattleService
{
    public function __construct(
        private WarfareBalance $balance,
        private SpiritualPort $spiritual,
        private CatastrophePort $catastrophe,
        private RandomSource $random,
    ) {
    }

    public function resolve(
        Army $attacker,
        Army $defender,
        ForceProfile $attackerForce,
        ForceProfile $defenderForce,
        WarProfile $war,
        int $territoryId,
    ): BattleResult {
        $this->applyFear($attacker, $defenderForce, $attackerForce, $war);
        $this->applyFear($defender, $attackerForce, $defenderForce, $war);

        $atkMult = $this->multiplier($attacker, $attackerForce, $war, $territoryId, true);
        $defMult = $this->multiplier($defender, $defenderForce, $war, $territoryId, false);

        $atkPower = max(0.1, $attacker->power($this->balance) * $atkMult);
        $defPower = max(0.1, $defender->power($this->balance) * $defMult);

        $shared = min($atkPower, $defPower);
        $atkLossMen = (int) max(0, round($attacker->men() * $this->balance->tickCasualtyRate * ($defPower / $atkPower)));
        $defLossMen = (int) max(0, round($defender->men() * $this->balance->tickCasualtyRate * ($atkPower / $defPower)));

        // Donor: both sides take ~8% of the lesser power as casualties.
        $donorAtk = (int) max(0, round($shared * $this->balance->tickCasualtyRate));
        $donorDef = (int) max(0, round($shared * $this->balance->tickCasualtyRate));
        $atkLossMen = max($atkLossMen, (int) floor($donorAtk / max(1.0, $this->balance->categoryWeight(\App\Domain\Warfare\Enums\UnitCategory::LEVY))));
        $defLossMen = max($defLossMen, (int) floor($donorDef / max(1.0, $this->balance->categoryWeight(\App\Domain\Warfare\Enums\UnitCategory::LEVY))));

        $atkCasualties = $this->applyLosses($attacker, $atkLossMen, $attackerForce, $war);
        $defCasualties = $this->applyLosses($defender, $defLossMen, $defenderForce, $war);

        $attacker->morale = Army::clamp($attacker->morale - $this->moraleHit($atkCasualties, $attackerForce, $defPower, $atkPower));
        $defender->morale = Army::clamp($defender->morale - $this->moraleHit($defCasualties, $defenderForce, $atkPower, $defPower));

        $possession = false;
        if ($war->allowsPossessionEvents && ($attackerForce->emitsPossession || $defenderForce->emitsPossession)) {
            $risk = 8 + (int) floor(($attackerForce->fearAura + $defenderForce->fearAura) / 5);
            $possession = $this->random->chance($risk);
            if ($possession) {
                $victim = $attackerForce->emitsPossession ? $defender : $attacker;
                $victim->morale = Army::clamp($victim->morale - 12);
            }
        }

        $desecrated = $war->desecratesBattlefield && ($attackerForce->corruptsTerrain || $defenderForce->corruptsTerrain);

        $atkRouted = $attacker->routed();
        $defRouted = $defender->routed();
        if ($atkRouted && !$defRouted) {
            $winner = $defender;
            $loser = $attacker;
            $side = 'defender';
        } elseif ($defRouted && !$atkRouted) {
            $winner = $attacker;
            $loser = $defender;
            $side = 'attacker';
        } elseif ($atkPower >= $defPower) {
            $winner = $attacker;
            $loser = $defender;
            $side = 'attacker';
        } else {
            $winner = $defender;
            $loser = $attacker;
            $side = 'defender';
        }

        $corpses = $atkCasualties->killed + $defCasualties->killed + $atkCasualties->wounded + $defCasualties->wounded;

        return new BattleResult(
            $attacker->warId,
            $territoryId,
            $winner->id,
            $loser->id,
            $atkCasualties,
            $defCasualties,
            $attacker->morale,
            $defender->morale,
            $possession,
            $desecrated,
            $corpses,
            $side,
        );
    }

    private function applyFear(Army $victim, ForceProfile $enemy, ForceProfile $self, WarProfile $war): void
    {
        $aura = $enemy->fearAura;
        if ($aura <= 0) {
            return;
        }
        $resist = (int) floor((1.0 - $self->fearVulnerability) * 40);
        if ($victim->commander) {
            $resist += $victim->commander->fearResistance();
        }
        $consecrated = $victim->consecratedMen();
        if ($consecrated > 0 && $victim->men() > 0) {
            $resist += (int) floor(25 * ($consecrated / $victim->men()));
        }
        $applied = max(0, $aura - $resist);
        if (!$war->allowsSupernaturalAftermath && $enemy->nature !== \App\Domain\Warfare\Enums\ArmyNature::HUMAN) {
            $applied = 0;
        }
        $victim->fear = Army::clamp($victim->fear + $applied);
        $victim->morale = Army::clamp($victim->morale - (int) floor($applied / 3));
    }

    private function multiplier(Army $army, ForceProfile $force, WarProfile $war, int $territoryId, bool $attacking): float
    {
        $moraleMod = max($this->balance->moraleFloor, min($this->balance->moraleCeil, $army->morale / 100));
        $supplyMod = $army->starving ? 0.6 : 1.0;
        $fearMod = max(0.5, 1.0 - ($army->fear / 200));
        $cmd = $army->commander ? ($army->commander->battleBonus() * 0.02) : 0.0;

        $clergy = 0.0;
        $relic = 0.0;
        if ($war->kind !== \App\Domain\Warfare\Enums\WarfareKind::HUMAN_VS_HUMAN) {
            $clergy = $this->spiritual->clergySupport($army->worldId, $territoryId, $army->belligerentId) / 100 * $force->clergySensitivity;
            $relic = $this->spiritual->relicSupport($army->worldId, $territoryId, $army->belligerentId) / 100 * $force->relicSensitivity;
        }

        $terrain = $attacking ? (1.0 - $force->openFieldPenalty) : (1.0 + $force->corruptedTerrainBonus);
        $plague = 1.0;
        if ($war->allowsPlagueExposure) {
            $plague = 1.0 - min(0.2, $this->catastrophe->plagueIntensity($army->worldId, $territoryId) / 500);
        }

        return max(0.2, $moraleMod * $supplyMod * $fearMod * $terrain * $plague + $cmd + $clergy + $relic);
    }

    private function applyLosses(Army $army, int $lostMen, ForceProfile $force, WarProfile $war): Casualties
    {
        $lostMen = max(0, min($army->men(), $lostMen));
        if ($lostMen === 0) {
            return Casualties::none();
        }

        $banished = 0;
        $converted = 0;
        $possessed = 0;
        $killed = (int) floor($lostMen * 0.45);
        $wounded = (int) floor($lostMen * 0.30);
        $fled = (int) floor($lostMen * 0.20);
        $captured = $lostMen - $killed - $wounded - $fled;

        if ($force->banishInsteadOfKill > 0) {
            $banished = (int) floor($killed * $force->banishInsteadOfKill);
            $killed -= $banished;
        }
        if ($war->allowsSupernaturalAftermath && $force->captiveConversion > 0) {
            $converted = (int) floor($captured * $force->captiveConversion);
            $captured -= $converted;
        }
        if ($war->allowsPossessionEvents && $force->emitsPossession) {
            $possessed = (int) floor($wounded * 0.1);
            $wounded -= $possessed;
        }

        $this->removeMen($army, $lostMen);

        return new Casualties($killed, $wounded, $fled, $captured, $banished, $possessed, $converted);
    }

    private function removeMen(Army $army, int $lostMen): void
    {
        $remaining = $lostMen;
        $next = [];
        foreach ($army->stacks as $stack) {
            if ($remaining <= 0) {
                $next[] = $stack;
                continue;
            }
            $take = min($stack->men, $remaining);
            $next[] = $stack->takeLosses($take);
            $remaining -= $take;
        }
        $army->replaceStacks($next);
    }

    private function moraleHit(Casualties $c, ForceProfile $force, float $enemyPower, float $ownPower): int
    {
        $base = 4 + (int) floor($c->killed / 80);
        if ($enemyPower > $ownPower * 1.5) {
            $base += 6;
        }
        $base += (int) floor(10 * $force->moraleInstability);

        return $base;
    }
}
