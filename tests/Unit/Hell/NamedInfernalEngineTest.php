<?php

namespace Tests\Unit\Hell;

use App\Domain\Hell\Enums\ActChannel;
use App\Domain\Hell\Enums\DefeatKind;
use App\Domain\Hell\Enums\GrudgeSubjectType;
use App\Domain\Hell\Enums\KnowledgeFacet;
use App\Domain\Hell\Enums\KnowledgeSource;
use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\NamedDemonStrategy;
use App\Domain\Hell\Enums\ObjectiveStatus;
use App\Domain\Hell\State\Breach;
use PHPUnit\Framework\TestCase;

final class NamedInfernalEngineTest extends TestCase
{
    public function test_corrupter_cult_organizer_and_invader_use_different_channels(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();

        $corrupter = $named->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');
        $organizer = $named->spawnFromCatalog($world, 'whisper-1', 'the_whisper_in_the_nave', 'nave_whisperers');
        $invader = $named->spawnFromCatalog($world, 'apollyon-1', 'apollyon_the_unmaker', 'locust_host');

        $this->assertSame(NamedDemonStrategy::CORRUPTER, $corrupter->strategy);
        $this->assertSame(NamedDemonStrategy::CULT_ORGANIZER, $organizer->strategy);
        $this->assertSame(NamedDemonStrategy::MILITARY_INVADER, $invader->strategy);

        $named->actThrough($world, 'mammon-1', ActChannel::TEMPTATION, ['territoryId' => 'east']);
        $this->assertSame(10, $world->territory('east')->rulerTemptation);
        $this->assertGreaterThan(0, $corrupter->territoryInfluence['east']);

        $named->actThrough($world, 'whisper-1', ActChannel::CULT_AGENTS, ['territoryId' => 'west']);
        $this->assertNotEmpty($world->cults);
        $cell = array_values($world->cults)[0];
        $this->assertSame('the_whisper_in_the_nave', $cell->patronNamedKey);
        $this->assertGreaterThan(0, $world->territory('west')->cultActivity);

        $this->expectException(\InvalidArgumentException::class);
        $named->actThrough($world, 'apollyon-1', ActChannel::INFERNAL_ARMIES, ['territoryId' => 'center']);
    }

    public function test_physical_manifestation_is_refused_in_ordinary_years(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 90;
        $world->apocalypsePhaseKey = 'ordinary';
        $named->spawnFromCatalog($world, 'apollyon-1', 'apollyon_the_unmaker', 'locust_host');

        $this->assertFalse($named->canPhysicallyManifest($world, $world->namedDemons['apollyon-1']));

        $this->expectException(\InvalidArgumentException::class);
        $named->attemptPhysicalManifestation($world, 'apollyon-1', 'center');
    }

    public function test_invader_may_take_a_body_when_the_age_permits(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 60;
        $world->apocalypsePhaseKey = 'open_incursions';
        $named->spawnFromCatalog($world, 'apollyon-1', 'apollyon_the_unmaker', 'locust_host');

        $record = $named->attemptPhysicalManifestation($world, 'apollyon-1', 'center');

        $this->assertSame(NamedDemonStatus::MANIFESTED, $record->status);
        $this->assertSame('center', $record->territoryId);
    }

    public function test_dossier_hides_true_identity_until_revealed(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $named->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');
        $observer = 'character:lord-east';

        $hidden = $named->dossier($world, 'mammon-1', $observer);
        $this->assertFalse($hidden->trueIdentityKnown);
        $this->assertSame('the gilded moth', $hidden->shownName);
        $this->assertNull($hidden->rank);
        $this->assertNull($hidden->motives);
        $this->assertNull($hidden->location);
        $this->assertSame([], $hidden->vulnerabilities);
        $payload = $hidden->toArray();
        $this->assertStringNotContainsString('Mammon', json_encode($payload));
        $this->assertArrayNotHasKey('hiddenTrueState', $payload);

        $named->revealKnowledge($world, 'mammon-1', $observer, KnowledgeFacet::TRUE_IDENTITY, KnowledgeSource::MANUSCRIPT);
        $named->revealKnowledge($world, 'mammon-1', $observer, KnowledgeFacet::RANK, KnowledgeSource::CLERGY);
        $named->revealKnowledge($world, 'mammon-1', $observer, KnowledgeFacet::MOTIVES, KnowledgeSource::CULT_DOCUMENT);
        $named->revealKnowledge($world, 'mammon-1', $observer, KnowledgeFacet::VULNERABILITIES, KnowledgeSource::EXORCISM);

        $open = $named->dossier($world, 'mammon-1', $observer);
        $this->assertTrue($open->trueIdentityKnown);
        $this->assertSame('Mammon', $open->shownName);
        $this->assertSame('court_officer', $open->rank);
        $this->assertStringContainsString('treasury', (string) $open->motives);
        $this->assertNotEmpty($open->vulnerabilities);
    }

    public function test_defeat_kinds_are_not_destruction(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();

        foreach (DefeatKind::all() as $kind) {
            $this->assertFalse(DefeatKind::isPermanentDestruction($kind));
            $world = DemonicThreatHarness::ordinaryCounties();
            $world->apocalypseIntensity = 60;
            $world->apocalypsePhaseKey = 'open_incursions';
            $demon = $named->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');
            $named->actThrough($world, 'mammon-1', ActChannel::TEMPTATION, ['territoryId' => 'east']);
            $named->actThrough($world, 'mammon-1', ActChannel::CORRUPTED_NOBLES, ['subjectId' => 'lord-east']);
            $named->attemptPhysicalManifestation($world, 'mammon-1', 'east');
            $breach = new Breach('breach-east', 'east', $world->date);
            $breach->namedDemonId = 'mammon-1';
            $world->breaches[$breach->id] = $breach;

            $context = match ($kind) {
                DefeatKind::SEVER_LOCAL_INFLUENCE => ['territoryId' => 'east'],
                DefeatKind::IMPRISON_SERVANT => ['servantId' => 'lord-east'],
                DefeatKind::PREVENT_OBJECTIVE => ['objectiveKey' => 'bind_the_purse'],
                default => [],
            };

            $after = $named->applyDefeat($world, 'mammon-1', $kind, $context);
            $this->assertNotSame(NamedDemonStatus::DESTROYED, $after->status, $kind);
        }
    }

    public function test_banishment_is_not_unmaking_and_true_destroy_still_exists(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $named->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');
        $named->applyDefeat($world, 'mammon-1', DefeatKind::BANISHMENT);

        $this->assertSame(NamedDemonStatus::BANISHED, $world->namedDemons['mammon-1']->status);

        $engine->destroyNamedDemon($world, 'mammon-1', 'relic');
        $this->assertSame(NamedDemonStatus::DESTROYED, $world->namedDemons['mammon-1']->status);
    }

    public function test_prevent_objective_and_destroy_cult_network(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $named->spawnFromCatalog($world, 'whisper-1', 'the_whisper_in_the_nave', 'nave_whisperers');
        $named->actThrough($world, 'whisper-1', ActChannel::CULT_AGENTS, ['territoryId' => 'center']);

        $named->applyDefeat($world, 'whisper-1', DefeatKind::PREVENT_OBJECTIVE, ['objectiveKey' => 'hollow_the_parish']);
        $this->assertSame(ObjectiveStatus::PREVENTED, $world->namedDemons['whisper-1']->objective('hollow_the_parish')['status']);

        $named->applyDefeat($world, 'whisper-1', DefeatKind::DESTROY_CULT_NETWORK);
        foreach ($world->cults as $cult) {
            $this->assertTrue($cult->destroyed);
        }
        $this->assertSame(NamedDemonStatus::LATENT, $world->namedDemons['whisper-1']->status);
    }

    public function test_grudges_persist_and_select_targets(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $demon = $named->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');

        $named->rememberGrudge($world, 'mammon-1', GrudgeSubjectType::DYNASTY, 'adhemar', 'refused the bargain', 8);
        $named->rememberGrudge($world, 'mammon-1', GrudgeSubjectType::MONASTERY, 'st-cesar', 'alms without witness', 4);
        $named->rememberGrudge($world, 'mammon-1', GrudgeSubjectType::DYNASTY, 'adhemar', 'refused again', 3);

        $this->assertTrue($named->wouldTarget($demon, GrudgeSubjectType::DYNASTY, 'adhemar'));
        $this->assertTrue($named->wouldTarget($demon, GrudgeSubjectType::MONASTERY, 'st-cesar'));
        $this->assertFalse($named->wouldTarget($demon, GrudgeSubjectType::RULER, 'raimond'));
        $this->assertSame(11, $demon->grudges[0]['intensity']);
    }

    public function test_pulse_named_is_idle_without_a_foothold(): void
    {
        $engine = DemonicThreatHarness::engine();
        $world = DemonicThreatHarness::ordinaryCounties();
        $engine->namedInfernal()->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');
        $before = $world->territory('center')->rulerTemptation;

        $engine->pulse($world);

        $this->assertSame($before, $world->territory('center')->rulerTemptation);
        $this->assertSame(NamedDemonStatus::LATENT, $world->namedDemons['mammon-1']->status);
    }

    public function test_strategies_diverge_once_they_hold_ground(): void
    {
        $engine = DemonicThreatHarness::engine();
        $named = $engine->namedInfernal();
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 12;

        $corrupter = $named->spawnFromCatalog($world, 'mammon-1', 'mammon_the_gilded', 'court_of_the_gilded_moth');
        $organizer = $named->spawnFromCatalog($world, 'whisper-1', 'the_whisper_in_the_nave', 'nave_whisperers');
        $invader = $named->spawnFromCatalog($world, 'apollyon-1', 'apollyon_the_unmaker', 'locust_host');
        $corrupter->territoryInfluence['east'] = 20;
        $corrupter->territoryId = 'east';
        $organizer->territoryInfluence['west'] = 20;
        $organizer->territoryId = 'west';
        $invader->territoryInfluence['center'] = 20;
        $invader->territoryId = 'center';

        $eastBefore = $world->territory('east')->rulerTemptation;
        $westBefore = $world->territory('west')->cultActivity;
        $centerBefore = $world->territory('center')->localManifestation;

        $engine->pulse($world);

        $this->assertGreaterThan($eastBefore, $world->territory('east')->rulerTemptation);
        $this->assertGreaterThan($westBefore, $world->territory('west')->cultActivity);
        $this->assertGreaterThan($centerBefore, $world->territory('center')->localManifestation);
    }
}
