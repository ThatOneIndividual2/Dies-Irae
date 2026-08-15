<?php

namespace App\Domain\Papacy;

use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ExtraordinaryElectionRule;

/**
 * Election weights and extraordinary fallbacks. Not a random table.
 */
final class ConclaveCatalog
{
    public int $majorityNumerator = 2;
    public int $majorityDenominator = 3;
    public int $deadlockAfterRounds = 3;
    public int $maxVotingRounds = 8;
    public int $minQuorum = 3;
    public int $minQuorumExtraordinary = 1;
    public int $assemblyDelayTicks = 1;

    public int $theologyWeight = 30;
    public int $theologyOpposeWeight = 25;
    public int $factionWeight = 35;
    public int $relationshipWeight = 1;
    public int $ambitionWeight = 40;
    public int $reputationWeight = 20;
    public int $compromiseWeight = 50;
    public int $deadlockFatigueWeight = 20;
    public int $realmTieWeight = 20;

    public int $legitimacyBase = 8000;
    public int $vacancyDecayPerTick = 80;
    public int $schismSplit = 3500;
    public int $enthronementRestore = 500;
    public int $contestedPenalty = 1200;
    public int $reconciliationRestore = 2000;

    public string $imperialMinApocalypseStage = ApocalypseStage::COLLAPSE_OF_OFFICES;

    public function majorityNeeded(int $present): int
    {
        if ($present < 1) {
            return 1;
        }

        return (int) ceil($present * $this->majorityNumerator / $this->majorityDenominator);
    }

    public function extraordinaryMajorityNeeded(int $present, bool $reducedCollege): int
    {
        if ($present < 1) {
            return 1;
        }

        if ($reducedCollege && $present < $this->minQuorum) {
            return max(1, (int) ceil($present / 2));
        }

        return $this->majorityNeeded($present);
    }

    public function imperialAppointmentPermitted(PapacyEngine $engine): bool
    {
        $living = $engine->livingAccessibleElectors();
        $collegeExtinct = count($living) === 0;
        $romeBlocked = !$engine->see->accessible;
        $stageOk = ApocalypseStage::atLeast($engine->apocalypseStage, $this->imperialMinApocalypseStage);

        return $collegeExtinct && $romeBlocked && $stageOk;
    }

    public function fallbackRules(PapacyEngine $engine): array
    {
        $rules = [];

        if (!$engine->see->accessible) {
            $rules[] = ExtraordinaryElectionRule::RELOCATE_SEAT;
        }

        if ($engine->see->communicationsDestroyed || $engine->see->plague || $engine->see->demonicIncursion) {
            $rules[] = ExtraordinaryElectionRule::DELAYED_ASSEMBLY;
        }

        $living = $engine->livingAccessibleElectors();
        if (count($living) < $this->minQuorum) {
            $rules[] = ExtraordinaryElectionRule::REDUCED_COLLEGE;
            if (count($living) === 0 && $engine->bishopPool !== []) {
                $rules[] = ExtraordinaryElectionRule::EXPAND_TO_BISHOPS;
            }
            if (count($living) > 0 && count($living) < $this->minQuorum) {
                $rules[] = ExtraordinaryElectionRule::SIMPLE_MAJORITY;
            }
        }

        if (count($engine->livingAccessibleElectors()) === 0 && $engine->bishopPool === []) {
            $rules[] = ExtraordinaryElectionRule::SEDE_VACANTE_PERSISTS;
        }

        return array_values(array_unique($rules));
    }
}
