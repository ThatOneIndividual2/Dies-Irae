<?php

namespace App\Domain\Population;

use App\Domain\Enums\ClergyCare;
use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\SocialClass;
use App\Domain\Population\Ports\ArmyAttritionPort;
use App\Domain\Population\Ports\CharacterDeathPort;
use App\Domain\Population\Ports\ClergyVacancyPort;
use App\Domain\Population\Ports\NobleExtinctionPort;
use App\Domain\Population\Ports\NullCharacterDeathPort;
use App\Domain\Population\Ports\RecordingArmyAttritionPort;
use App\Domain\Population\Ports\RecordingClergyVacancyPort;
use App\Domain\Population\Ports\RecordingNobleExtinctionPort;
use App\Domain\Population\Ports\RecordingSuccessionPressurePort;
use App\Domain\Population\Ports\SuccessionPressurePort;
use App\Domain\Support\IntClamp;

final class ApplyMassMortality
{
    private RuinStateMachine $ruin;
    private CharacterDeathPort $characters;
    private SuccessionPressurePort $succession;
    private ClergyVacancyPort $clergy;
    private ArmyAttritionPort $armies;
    private NobleExtinctionPort $nobles;

    public function __construct(
        ?RuinStateMachine $ruin = null,
        ?CharacterDeathPort $characters = null,
        ?SuccessionPressurePort $succession = null,
        ?ClergyVacancyPort $clergy = null,
        ?ArmyAttritionPort $armies = null,
        ?NobleExtinctionPort $nobles = null
    ) {
        $this->ruin = $ruin ?? new RuinStateMachine();
        $this->characters = $characters ?? new NullCharacterDeathPort();
        $this->succession = $succession ?? new RecordingSuccessionPressurePort();
        $this->clergy = $clergy ?? new RecordingClergyVacancyPort();
        $this->armies = $armies ?? new RecordingArmyAttritionPort();
        $this->nobles = $nobles ?? new RecordingNobleExtinctionPort();
    }

    /**
     * @param  array<string,int>  $classWeights
     */
    public function apply(Settlement $settlement, int $deaths, array $classWeights, string $cause = 'plague'): MortalityConsequences
    {
        $result = new MortalityConsequences($settlement->id);
        $result->ruinBefore = $settlement->ruinState;
        $result->workforceBefore = $settlement->workforce();
        $result->taxBaseBefore = $settlement->taxBase();
        $result->levyBaseBefore = $settlement->levyBase();
        $result->foodProductionBefore = $settlement->foodProduction();
        $wasViable = $settlement->isViable();
        $noblesBefore = $settlement->cohorts->nobles;

        $deaths = min(max(0, $deaths), $settlement->souls());
        if ($deaths === 0) {
            $this->fillAfter($settlement, $result, $wasViable, $noblesBefore, $cause);

            return $result;
        }

        $extracted = $settlement->cohorts->extract($deaths, $classWeights);
        $taken = $extracted['taken'];
        $settlement->cohorts = $extracted['remaining'];
        $this->trimInfectionToPopulation($settlement);

        $result->soulsLost = $taken->souls();
        $result->deathsByClass = $taken->toArray();

        $settlement->unburied += $result->soulsLost;
        $buried = intdiv($settlement->unburied * CorpseHandling::burialRate($settlement->corpseHandling), 10000);
        $settlement->unburied = IntClamp::nonNegative($settlement->unburied - $buried);

        $deathShare = $settlement->peakSouls > 0
            ? intdiv($result->soulsLost * 100, $settlement->peakSouls)
            : 100;
        $settlement->despair = IntClamp::between(
            $settlement->despair + min(25, 2 + $deathShare) + CorpseHandling::despairDelta($settlement->corpseHandling) - ClergyCare::despairRelief($settlement->clergyCare),
            0,
            100
        );
        $settlement->morale = IntClamp::between($settlement->morale - min(20, 1 + $deathShare), 0, 100);
        $settlement->corruption = IntClamp::between(
            $settlement->corruption + CorpseHandling::corruptionDelta($settlement->corpseHandling) + ($settlement->corpsePressure() >= 80 ? 4 : 0),
            0,
            100
        );

        $this->ruin->apply($settlement, $settlement->hellOccupation);
        $this->fillAfter($settlement, $result, $wasViable, $noblesBefore, $cause);

        $this->characters->considerDeaths($settlement->worldId, $settlement->id, [
            'cause' => $cause,
            'deaths_by_class' => $result->deathsByClass,
            'named_required' => false,
        ]);

        return $result;
    }

    private function fillAfter(
        Settlement $settlement,
        MortalityConsequences $result,
        bool $wasViable,
        int $noblesBefore,
        string $cause
    ): void {
        $result->workforceAfter = $settlement->workforce();
        $result->taxBaseAfter = $settlement->taxBase();
        $result->levyBaseAfter = $settlement->levyBase();
        $result->foodProductionAfter = $settlement->foodProduction();
        $result->armyAttrition = IntClamp::nonNegative($result->levyBaseBefore - $result->levyBaseAfter);
        $result->clergyVacancies = $result->deathsByClass[SocialClass::CLERGY] ?? 0;
        $result->nobleExtinction = $noblesBefore > 0 && $settlement->cohorts->nobles === 0;
        $result->successionPressure = $this->successionPressure($settlement, $result);
        $result->viabilityLost = $wasViable && !$settlement->isViable();
        $result->rebellionPressure = $settlement->rebellionPressure();
        $result->migrationPressure = $settlement->migrationPressure();
        $result->unburiedCorpses = $settlement->unburied;
        $result->corpsePressure = $settlement->corpsePressure();
        $result->despair = $settlement->despair;
        $result->corruption = $settlement->corruption;
        $result->ruinState = $settlement->ruinState;

        if ($result->successionPressure > 0) {
            $this->succession->record($settlement->worldId, $settlement->id, $result->successionPressure, [
                'cause' => $cause,
                'noble_extinction' => $result->nobleExtinction,
            ]);
        }
        if ($result->clergyVacancies > 0) {
            $this->clergy->openVacancies($settlement->worldId, $settlement->id, $result->clergyVacancies, $cause);
        }
        if ($result->armyAttrition > 0) {
            $this->armies->applyLevyLoss($settlement->worldId, $settlement->id, $result->armyAttrition, $cause);
        }
        if ($result->nobleExtinction) {
            $this->nobles->recordLocalExtinction($settlement->worldId, $settlement->id, $cause);
        }
    }

    private function successionPressure(Settlement $settlement, MortalityConsequences $result): int
    {
        $mods = $settlement->ruinModifiers();
        $pressure = 0;
        if ($mods->successionCrisis) {
            $pressure += 40;
        }
        $nobleDeaths = $result->deathsByClass[SocialClass::NOBLES] ?? 0;
        if ($nobleDeaths > 0) {
            $pressure += min(50, $nobleDeaths * 8);
        }
        if ($result->nobleExtinction) {
            $pressure += 40;
        }

        return IntClamp::between($pressure, 0, 100);
    }

    private function trimInfectionToPopulation(Settlement $settlement): void
    {
        $cap = $settlement->souls();
        $infected = $settlement->incubating + $settlement->infectious + $settlement->recovered;
        if ($infected <= $cap) {
            $settlement->recovered = min($settlement->recovered, $cap);
            $settlement->syncBurdenFromBatches();

            return;
        }

        $overflow = $infected - $cap;
        $settlement->recovered = IntClamp::nonNegative($settlement->recovered - $overflow);
        $overflow = IntClamp::nonNegative(($settlement->incubating + $settlement->infectious + $settlement->recovered) - $cap);
        if ($overflow > 0) {
            $this->trimBatches($settlement->infectiousBatches, $overflow);
        }
        $settlement->syncBurdenFromBatches();
        $overflow = IntClamp::nonNegative(($settlement->incubating + $settlement->infectious + $settlement->recovered) - $cap);
        if ($overflow > 0) {
            $this->trimBatches($settlement->incubationBatches, $overflow);
        }
        $settlement->syncBurdenFromBatches();
        $settlement->recovered = min($settlement->recovered, IntClamp::nonNegative($cap - $settlement->incubating - $settlement->infectious));
    }

    /**
     * @param  list<array{remaining:int,souls:int}>  $batches
     */
    private function trimBatches(array &$batches, int &$overflow): void
    {
        for ($i = count($batches) - 1; $i >= 0 && $overflow > 0; $i--) {
            $take = min($batches[$i]['souls'], $overflow);
            $batches[$i]['souls'] -= $take;
            $overflow -= $take;
            if ($batches[$i]['souls'] <= 0) {
                unset($batches[$i]);
            }
        }
        $batches = array_values($batches);
    }
}
