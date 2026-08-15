<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\DisplacementCause;
use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Doctrine\WarProfile;
use App\Domain\Warfare\Enums\SanctityState;
use App\Domain\Warfare\Enums\WarfareKind;
use App\Domain\Warfare\Ports\AftermathSink;
use App\Domain\Warfare\Ports\CatastrophePort;
use App\Domain\Warfare\State\AftermathReport;
use App\Domain\Warfare\State\BattleResult;

final class AftermathService
{
    public function __construct(
        private CatastrophePort $catastrophe,
        private AftermathSink $sink,
    ) {
    }

    public function fromBattle(
        BattleResult $battle,
        WarProfile $war,
        ForceProfile $attackerForce,
        ForceProfile $defenderForce,
        int $worldId,
        bool $sacked,
    ): AftermathReport {
        $handling = $this->corpseHandling($war, $attackerForce, $defenderForce, $battle);
        $mundaneDisease = $this->catastrophe->campFeverRiskFromCorpses($battle->corpses);

        $plague = 0;
        $corruption = 0;
        $faith = 0;
        $morale = 0;
        $refugees = 0;
        $refugeeCause = DisplacementCause::WAR;
        $sanctity = SanctityState::ORDINARY;
        $possession = false;
        $desecration = false;
        $overlay = false;
        $infiltration = false;
        $notes = [];

        if ($war->mundaneSettlementMorale) {
            $morale -= 4;
            if ($sacked) {
                $morale -= 10;
            }
        }
        if ($war->mundaneRefugees && $sacked) {
            $refugees = 40 + (int) floor($battle->corpses / 20);
        }

        if ($war->kind === WarfareKind::HUMAN_VS_HUMAN) {
            $notes[] = 'mundane_feudal_aftermath';
            $report = new AftermathReport(
                $worldId,
                $battle->warId,
                $battle->territoryId,
                $war->kind,
                $battle->corpses,
                $handling,
                $mundaneDisease,
                0,
                $morale,
                0,
                0,
                $refugees,
                $refugeeCause,
                SanctityState::ORDINARY,
                false,
                false,
                false,
                false,
                $notes,
            );
            $this->sink->apply($report);

            return $report;
        }

        if ($war->allowsPlagueExposure) {
            $plague = min(40, (int) floor($battle->corpses / 50) + CorpseHandling::infectiousnessBonus($handling) / 200);
        }
        if ($war->allowsSupernaturalAftermath) {
            $corruption = CorpseHandling::corruptionDelta($handling);
            if ($attackerForce->corruptsTerrain || $defenderForce->corruptsTerrain) {
                $corruption += 6;
            }
            if ($war->allowsFaithShock) {
                $faith -= 8;
                if ($battle->battlefieldDesecrated) {
                    $faith -= 10;
                }
            }
        }
        if ($war->allowsPossessionEvents) {
            $possession = $battle->possessionEvent;
        }
        if ($war->desecratesBattlefield && $battle->battlefieldDesecrated) {
            $desecration = true;
            $sanctity = SanctityState::DESECRATED;
            $handling = CorpseHandling::DESECRATED;
        }
        if ($war->appliesHellOverlay) {
            $overlay = true;
            $refugeeCause = DisplacementCause::HELL;
            $refugees += 30;
        }
        if ($war->appliesInfiltration) {
            $infiltration = true;
            $faith -= 5;
        }

        $notes[] = 'supernatural_aftermath';
        $report = new AftermathReport(
            $worldId,
            $battle->warId,
            $battle->territoryId,
            $war->kind,
            $battle->corpses,
            $handling,
            $mundaneDisease,
            $plague,
            $morale,
            $corruption,
            $faith,
            $refugees,
            $refugeeCause,
            $sanctity,
            $possession,
            $desecration,
            $overlay,
            $infiltration,
            $notes,
        );
        $this->sink->apply($report);

        return $report;
    }

    private function corpseHandling(
        WarProfile $war,
        ForceProfile $attackerForce,
        ForceProfile $defenderForce,
        BattleResult $battle,
    ): string {
        if ($war->kind === WarfareKind::HUMAN_VS_HUMAN) {
            return $battle->corpses > 400 ? CorpseHandling::MASS_GRAVE : CorpseHandling::CONSECRATED;
        }
        if ($war->desecratesBattlefield && ($attackerForce->corruptsTerrain || $defenderForce->corruptsTerrain)) {
            return CorpseHandling::DESECRATED;
        }
        if ($attackerForce->consecratedByDefault || $defenderForce->consecratedByDefault) {
            return CorpseHandling::CONSECRATED;
        }

        return CorpseHandling::ABANDONED;
    }
}
