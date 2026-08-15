<?php

namespace App\Domain\Warfare\Doctrine;

use App\Domain\Warfare\Enums\ArmyNature;
use App\Domain\Warfare\Enums\SupplyKind;

/**
 * Per-army physics. A human army in a demonic war still eats food.
 */
final class ForceProfile
{
    public function __construct(
        public string $nature,
        public string $supplyKind,
        public bool $starvable,
        public bool $requiresPortal,
        public bool $manifestationLimited,
        public int $manifestationCap,
        public int $fearAura,
        public bool $consecratedByDefault,
        public bool $usesFeudalLevies,
        public bool $usesForcedLevies,
        public bool $usesHolyOrderCall,
        public float $banishInsteadOfKill,
        public float $captiveConversion,
        public bool $emitsPossession,
        public bool $corruptsTerrain,
        public bool $spreadsPlague,
        public float $moraleInstability,
        public float $clergySensitivity,
        public float $relicSensitivity,
        public float $fearVulnerability,
        public bool $devoursPopulation,
        public float $openFieldPenalty,
        public float $corruptedTerrainBonus,
    ) {
    }

    public static function human(): self
    {
        return new self(
            ArmyNature::HUMAN,
            SupplyKind::FOOD,
            true,
            false,
            false,
            0,
            0,
            false,
            true,
            false,
            false,
            0.0,
            0.0,
            false,
            false,
            false,
            0.0,
            0.35,
            0.25,
            1.0,
            false,
            0.0,
            0.0,
        );
    }

    public static function cult(): self
    {
        return new self(
            ArmyNature::CULT,
            SupplyKind::PLUNDER,
            false,
            false,
            false,
            0,
            12,
            false,
            false,
            true,
            false,
            0.0,
            0.25,
            false,
            true,
            false,
            0.15,
            0.1,
            0.1,
            0.7,
            false,
            0.15,
            0.25,
        );
    }

    public static function demonic(): self
    {
        return new self(
            ArmyNature::DEMONIC,
            SupplyKind::PORTAL,
            false,
            true,
            true,
            4000,
            40,
            false,
            false,
            false,
            false,
            1.0,
            0.0,
            true,
            true,
            true,
            0.0,
            0.0,
            0.0,
            0.0,
            false,
            0.0,
            0.35,
        );
    }

    public static function holyOrder(): self
    {
        return new self(
            ArmyNature::HOLY_ORDER,
            SupplyKind::TITHE,
            true,
            false,
            false,
            0,
            0,
            true,
            false,
            false,
            true,
            0.0,
            0.0,
            false,
            false,
            false,
            0.0,
            1.0,
            1.0,
            0.35,
            false,
            0.0,
            0.0,
        );
    }

    public static function corrupted(): self
    {
        return new self(
            ArmyNature::CORRUPTED,
            SupplyKind::DEVOUR,
            true,
            false,
            false,
            0,
            22,
            false,
            false,
            true,
            false,
            0.15,
            0.1,
            true,
            true,
            true,
            0.35,
            0.2,
            0.4,
            0.5,
            true,
            0.0,
            0.2,
        );
    }

    public static function forNature(string $nature): self
    {
        return match ($nature) {
            ArmyNature::CULT => self::cult(),
            ArmyNature::DEMONIC => self::demonic(),
            ArmyNature::HOLY_ORDER => self::holyOrder(),
            ArmyNature::CORRUPTED => self::corrupted(),
            default => self::human(),
        };
    }
}
