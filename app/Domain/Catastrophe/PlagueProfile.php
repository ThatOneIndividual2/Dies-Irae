<?php

namespace App\Domain\Catastrophe;

use App\Domain\Enums\SocialClass;

/**
 * Strain parameters for a strategic plague. Not a medical model.
 * Values that are rates live in basis points (10000 = 100%).
 */
final class PlagueProfile
{
    public string $key;
    public string $name;
    public int $infectiousness;
    public int $mortality;
    public int $incubationTicks;
    public int $infectiousTicks;
    public int $supernaturalAmplification;
    public int $corpseAmplification;
    public int $nobleShelter;
    public int $reinfection;
    public bool $supernatural;

    /** @var array<string,int> */
    public array $classMortalityWeights;

    public function __construct(
        string $key,
        string $name,
        int $infectiousness,
        int $mortality,
        int $incubationTicks,
        int $infectiousTicks,
        int $supernaturalAmplification = 10000,
        int $corpseAmplification = 10000,
        int $nobleShelter = 3000,
        int $reinfection = 0,
        bool $supernatural = false,
        array $classMortalityWeights = []
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->infectiousness = max(0, $infectiousness);
        $this->mortality = max(0, $mortality);
        $this->incubationTicks = max(1, $incubationTicks);
        $this->infectiousTicks = max(1, $infectiousTicks);
        $this->supernaturalAmplification = max(0, $supernaturalAmplification);
        $this->corpseAmplification = max(0, $corpseAmplification);
        $this->nobleShelter = max(0, $nobleShelter);
        $this->reinfection = max(0, $reinfection);
        $this->supernatural = $supernatural;
        $this->classMortalityWeights = $classMortalityWeights ?: [
            SocialClass::NOBLES => 70,
            SocialClass::CLERGY => 130,
            SocialClass::BURGHERS => 110,
            SocialClass::PEASANTS => 100,
            SocialClass::UNFREE => 105,
        ];
    }

    public static function blackDeath(): self
    {
        return new self(
            'black_death',
            'The Great Mortality',
            4200,
            5500,
            3,
            5,
            10000,
            10000,
            2500,
            400,
            false,
            [
                SocialClass::NOBLES => 70,
                SocialClass::CLERGY => 145,
                SocialClass::BURGHERS => 115,
                SocialClass::PEASANTS => 100,
                SocialClass::UNFREE => 108,
            ]
        );
    }

    public static function pneumonic(): self
    {
        return new self(
            'pneumonic_death',
            'The Pneumonic Death',
            6200,
            7200,
            2,
            4,
            10000,
            8000,
            1500,
            200,
            false
        );
    }

    /**
     * Infernal miasma: less "natural" contagion, more despair and the dead.
     */
    public static function infernalMiasma(): self
    {
        return new self(
            'infernal_miasma',
            'The Breath of the Pit',
            2800,
            4000,
            4,
            8,
            18000,
            16000,
            0,
            2500,
            true,
            [
                SocialClass::NOBLES => 100,
                SocialClass::CLERGY => 90,
                SocialClass::BURGHERS => 110,
                SocialClass::PEASANTS => 120,
                SocialClass::UNFREE => 125,
            ]
        );
    }

    public function withSupernaturalAmplification(int $bp): self
    {
        $copy = clone $this;
        $copy->supernaturalAmplification = max(0, $bp);

        return $copy;
    }
}
