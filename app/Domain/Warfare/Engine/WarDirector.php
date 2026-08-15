<?php

namespace App\Domain\Warfare\Engine;

use App\Domain\Support\WorldBoundary;
use App\Domain\Warfare\Doctrine\DoctrineResolver;
use App\Domain\Warfare\Doctrine\ForceProfile;
use App\Domain\Warfare\Doctrine\WarProfile;
use App\Domain\Warfare\Enums\WarStatus;
use App\Domain\Warfare\Ports\AftermathSink;
use App\Domain\Warfare\Ports\CatastrophePort;
use App\Domain\Warfare\Ports\GeographyPort;
use App\Domain\Warfare\Ports\HellPort;
use App\Domain\Warfare\Ports\LevySource;
use App\Domain\Warfare\Ports\PopulationPort;
use App\Domain\Warfare\Ports\SpiritualPort;
use App\Domain\Warfare\State\Army;
use App\Domain\Warfare\State\BattleResult;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\Commander;
use App\Domain\Warfare\State\OccupationResult;
use App\Domain\Warfare\State\Siege;
use App\Domain\Warfare\State\War;
use App\Domain\Warfare\State\WarGoal;

/**
 * Composition root for the warfare kernel. Actions call this; it does not own Eloquent.
 */
final class WarDirector
{
    public DoctrineResolver $doctrines;

    public LevyService $levies;

    public ArmyFactory $armies;

    public SupplyService $supply;

    public MovementService $movement;

    public BattleService $battles;

    public SiegeService $sieges;

    public OccupationService $occupation;

    public AftermathService $aftermath;

    public WarfareBalance $balance;

    /** @var War[] */
    public array $wars = [];

    /** @var Army[] */
    public array $fieldArmies = [];

    /** @var Siege[] */
    public array $activeSieges = [];

    private int $nextWarId = 1;

    public function __construct(
        LevySource $levySource,
        PopulationPort $population,
        GeographyPort $geography,
        SpiritualPort $spiritual,
        CatastrophePort $catastrophe,
        HellPort $hell,
        AftermathSink $sink,
        ?RandomSource $random = null,
        ?WarfareBalance $balance = null,
    ) {
        $this->balance = $balance ?? new WarfareBalance();
        $random = $random ?? new NullRandom();
        $this->doctrines = new DoctrineResolver();
        $this->levies = new LevyService($levySource, $population, $this->balance);
        $this->armies = new ArmyFactory($this->balance);
        $this->supply = new SupplyService($this->balance, $hell, $population);
        $this->movement = new MovementService($geography, $this->supply, $hell, $this->balance);
        $this->battles = new BattleService($this->balance, $spiritual, $catastrophe, $random);
        $this->sieges = new SiegeService($this->balance);
        $this->occupation = new OccupationService($geography);
        $this->aftermath = new AftermathService($catastrophe, $sink);
    }

    public function declareWar(Belligerent $aggressor, Belligerent $defender, WarGoal $goal, string $startedOn): War
    {
        WorldBoundary::assertSameWorldIds('declare war', $aggressor->worldId, $defender->worldId);
        $profile = $this->doctrines->warProfile($aggressor, $defender);
        $this->doctrines->assertGoalAllowed($profile, $goal);

        foreach ($this->wars as $existing) {
            if (!$existing->active()) {
                continue;
            }
            if ($existing->involves($aggressor->id) && $existing->involves($defender->id)) {
                throw new \InvalidArgumentException('A war already rages between these belligerents.');
            }
        }

        $war = new War(
            $aggressor->worldId,
            $this->nextWarId++,
            $aggressor,
            $defender,
            $profile->kind,
            $goal,
            $startedOn,
        );
        $this->wars[$war->id] = $war;

        return $war;
    }

    public function warProfile(War $war): WarProfile
    {
        return WarProfile::forKind($war->kind);
    }

    public function forceProfile(Belligerent $belligerent): ForceProfile
    {
        return $this->doctrines->forceProfile($belligerent);
    }

    /**
     * @param \App\Domain\Warfare\State\UnitStack[] $menAtArms
     */
    public function formArmy(
        War $war,
        Belligerent $belligerent,
        int $territoryId,
        array $menAtArms,
        ?Commander $commander,
        ?int $portalTerritoryId = null,
    ): Army {
        $force = $this->forceProfile($belligerent);
        $levies = $this->levies->raise($war->worldId, $belligerent->id, $belligerent->kind, $force);
        $cap = $force->manifestationLimited ? $force->manifestationCap : 0;
        $army = $this->armies->form(
            $war->worldId,
            $war->id,
            $belligerent->id,
            $force,
            $territoryId,
            $levies,
            $menAtArms,
            $commander,
            $cap,
            $portalTerritoryId,
        );
        $this->fieldArmies[$army->id] = $army;

        return $army;
    }

    public function fight(Army $attacker, Army $defender, bool $sacked = false): BattleResult
    {
        $war = $this->wars[$attacker->warId];
        $atkForce = ForceProfile::forNature($attacker->nature);
        $defForce = ForceProfile::forNature($defender->nature);
        $profile = $this->warProfile($war);
        $result = $this->battles->resolve($attacker, $defender, $atkForce, $defForce, $profile, $attacker->territoryId);
        $war->attackerCasualties += $result->attackerCasualties->totalRemoved();
        $war->defenderCasualties += $result->defenderCasualties->totalRemoved();
        if ($result->winnerSide === 'attacker') {
            $war->attackerScore += 15;
        } else {
            $war->defenderScore += 15;
        }
        $this->aftermath->fromBattle($result, $profile, $atkForce, $defForce, $war->worldId, $sacked);

        return $result;
    }

    public function occupy(War $war, int $territoryId, int $occupierId): OccupationResult
    {
        $result = $this->occupation->occupy($war, $this->warProfile($war), $territoryId, $occupierId);
        if ($result->militaryControlTransferred || $result->hellOverlayApplied || $result->infiltrationApplied) {
            $war->attackerScore += 20;
        }

        return $result;
    }

    public function settle(War $war, int $winnerId, string $settlementType): War
    {
        if (!$war->active()) {
            return $war;
        }
        $war->status = WarStatus::ENDED;
        $war->winnerBelligerentId = $winnerId;
        $war->settlementType = $settlementType;
        foreach ($this->fieldArmies as $army) {
            if ($army->warId === $war->id && $army->nature === \App\Domain\Warfare\Enums\ArmyNature::HUMAN) {
                foreach ($army->stacks as $i => $stack) {
                    if ($stack->category === \App\Domain\Warfare\Enums\UnitCategory::LEVY) {
                        $army->stacks[$i] = $stack->takeLosses((int) floor($stack->men * 0.3));
                    }
                }
                $army->replaceStacks($army->stacks);
            }
        }

        return $war;
    }
}
