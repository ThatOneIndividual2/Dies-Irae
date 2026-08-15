<?php

namespace App\Domain\Warfare\Doctrine;

use App\Domain\Warfare\Enums\OccupationMode;
use App\Domain\Warfare\Enums\WarGoalType;
use App\Domain\Warfare\Enums\WarfareKind;

/**
 * Per-war physics: occupation, goals, and which aftermath channels may fire.
 * Human vs human sets every supernatural channel to false.
 */
final class WarProfile
{
    public function __construct(
        public string $kind,
        public string $occupationMode,
        public bool $transfersMilitaryControl,
        public bool $transfersLegalOwnershipOnOccupy,
        public bool $appliesHellOverlay,
        public bool $appliesInfiltration,
        public bool $allowsSupernaturalAftermath,
        public bool $allowsPossessionEvents,
        public bool $desecratesBattlefield,
        public bool $mundaneCorpses,
        public bool $mundaneRefugees,
        public bool $mundaneSettlementMorale,
        public bool $allowsPlagueExposure,
        public bool $allowsFaithShock,
        public bool $resilientToOccupationRules,
        public int $pressureToOccupy,
        public array $allowedWarGoals,
    ) {
    }

    public static function humanVsHuman(): self
    {
        return new self(
            WarfareKind::HUMAN_VS_HUMAN,
            OccupationMode::MILITARY_CONTROL,
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            true,
            true,
            true,
            false,
            false,
            false,
            100,
            [WarGoalType::CONQUEST, WarGoalType::VASSALIZE, WarGoalType::DEFENSIVE],
        );
    }

    public static function humanVsCult(): self
    {
        return new self(
            WarfareKind::HUMAN_VS_CULT,
            OccupationMode::INFILTRATION,
            false,
            false,
            false,
            true,
            true,
            false,
            true,
            true,
            true,
            true,
            false,
            true,
            false,
            80,
            [WarGoalType::SUPPRESS_CULT, WarGoalType::HOLY_WAR, WarGoalType::DEFENSIVE],
        );
    }

    public static function humanVsDemon(): self
    {
        return new self(
            WarfareKind::HUMAN_VS_DEMON,
            OccupationMode::HELL_OVERLAY,
            false,
            false,
            true,
            false,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            140,
            [WarGoalType::SEAL_RIFT, WarGoalType::DEFENSIVE, WarGoalType::HOLY_WAR],
        );
    }

    public static function holyOrder(): self
    {
        return new self(
            WarfareKind::HOLY_ORDER,
            OccupationMode::COMMANDERY,
            true,
            false,
            false,
            false,
            true,
            false,
            false,
            true,
            true,
            true,
            false,
            true,
            false,
            90,
            [WarGoalType::HOLY_WAR, WarGoalType::SUPPRESS_CULT, WarGoalType::SEAL_RIFT, WarGoalType::DEFENSIVE],
        );
    }

    public static function corruptedArmy(): self
    {
        return new self(
            WarfareKind::CORRUPTED_ARMY,
            OccupationMode::CORRUPT_CONTROL,
            true,
            false,
            false,
            false,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            false,
            90,
            [WarGoalType::PURGE_CORRUPTION, WarGoalType::CONQUEST, WarGoalType::DEFENSIVE],
        );
    }

    public static function forKind(string $kind): self
    {
        return match ($kind) {
            WarfareKind::HUMAN_VS_CULT => self::humanVsCult(),
            WarfareKind::HUMAN_VS_DEMON => self::humanVsDemon(),
            WarfareKind::HOLY_ORDER => self::holyOrder(),
            WarfareKind::CORRUPTED_ARMY => self::corruptedArmy(),
            default => self::humanVsHuman(),
        };
    }

    public function allowsGoal(string $goalType): bool
    {
        return in_array($goalType, $this->allowedWarGoals, true);
    }
}
