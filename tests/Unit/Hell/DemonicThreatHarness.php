<?php

namespace Tests\Unit\Hell;

use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\PossessionStage;
use App\Domain\Hell\State\CorruptedArmy;
use App\Domain\Hell\State\CultCell;
use App\Domain\Hell\State\DemonicFactionRecord;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\PossessionLink;
use App\Domain\Hell\State\TerritoryThreat;
use App\Domain\Hell\State\ThreatWorld;

/**
 * Deterministic fixture builder for the hell overlay. No database, no clock side effects.
 */
final class DemonicThreatHarness
{
    public string $dataDir;

    public static function dataDir(): string
    {
        return dirname(__DIR__, 3).'/database/data/hell';
    }

    public static function engine(): DemonicThreatEngine
    {
        return DemonicThreatEngine::fromDataDirectory(self::dataDir());
    }

    public static function ordinaryCounties(): ThreatWorld
    {
        $world = new ThreatWorld();
        $world->worldId = 1;
        $world->seed = 'dies-irae-hell-harness';
        $world->date = '1348-06-01';
        $world->apocalypseIntensity = 0;
        $world->apocalypsePhaseKey = 'ordinary';

        foreach (['west', 'center', 'east'] as $id) {
            $t = new TerritoryThreat($id);
            $t->legalTitleId = 'county-'.$id;
            $t->rulerCharacterId = 'lord-'.$id;
            $t->population = 4000;
            $t->morale = 72;
            $t->clergyPresence = 14;
            $t->incursionState = IncursionState::DORMANT;
            $world->addTerritory($t);
        }

        $world->link('west', 'center');
        $world->link('center', 'east');

        $moth = new DemonicFactionRecord('court_of_the_gilded_moth', 'Court of the Gilded Moth');
        $moth->themes = ['greed', 'pride'];
        $world->factions[$moth->key] = $moth;

        $locust = new DemonicFactionRecord('locust_host', 'The Locust Host');
        $locust->themes = ['pestilence', 'wrath'];
        $world->factions[$locust->key] = $locust;

        return $world;
    }

    public static function withCult(ThreatWorld $world, string $territoryId, int $activity, string $faction = 'court_of_the_gilded_moth'): CultCell
    {
        $cult = new CultCell('cult-'.$territoryId, $territoryId, $faction);
        $cult->activity = $activity;
        $world->cults[$cult->id] = $cult;
        $world->territory($territoryId)->cultActivity = $world->cultActivityIn($territoryId);

        return $cult;
    }

    public static function withNamed(
        ThreatWorld $world,
        string $instanceId,
        string $catalogKey,
        string $status = NamedDemonStatus::LATENT,
        ?string $territoryId = null
    ): NamedDemonRecord {
        $engine = self::engine();
        $record = $engine->named()->spawnLatent($world, $instanceId, $catalogKey, 'locust_host');
        $record->status = $status;
        $record->territoryId = $territoryId;

        return $record;
    }

    public static function withPossession(ThreatWorld $world, string $characterId, string $stage = PossessionStage::TEMPTATION): PossessionLink
    {
        $link = new PossessionLink('pos-'.$characterId, $characterId);
        $link->stage = $stage;
        $link->sourceCatalogKey = 'inhabiting_spirit';
        $link->intensity = $stage === PossessionStage::TEMPTATION ? 20 : 50;
        $world->possessions[$link->id] = $link;

        return $link;
    }

    public static function withArmy(ThreatWorld $world, string $id, string $territoryId, int $corruption = 10): CorruptedArmy
    {
        $army = new CorruptedArmy($id, $territoryId);
        $army->corruption = $corruption;
        $army->strength = 40;
        $army->originalRealmId = 'realm-france-fragment';
        $world->armies[$id] = $army;

        return $army;
    }

    public static function ripeForCorruption(ThreatWorld $world, string $id): void
    {
        $t = $world->territory($id);
        $t->corruption = 45;
        $t->despair = 42;
        $t->cultActivity = 35;
        self::withCult($world, $id, 35);
    }
}
