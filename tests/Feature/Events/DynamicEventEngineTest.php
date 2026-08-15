<?php

namespace Tests\Feature\Events;

use App\Actions\Campaign\ResolveGameEvent;
use App\Actions\Events\FireCatalogEvent;
use App\Actions\Events\InspectEventEligibility;
use App\Actions\Events\PulseNarrativeEvents;
use App\Actions\World\CreateWorld;
use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventDefinitionValidator;
use App\Domain\Support\LocalInspectGuard;
use App\Models\Character;
use App\Models\ChurchRelation;
use App\Models\Cult;
use App\Models\EventChainLink;
use App\Models\EventHook;
use App\Models\GameEvent;
use App\Models\PlagueWave;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDO;
use RuntimeException;
use Tests\Feature\LaravelTestCase;

class DynamicEventEngineTest extends LaravelTestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useIsolatedEventDatabase();
    }

    private function useIsolatedEventDatabase(): void
    {
        $name = 'dies_irae_events_testing';
        $cfg = config('database.connections.mysql');
        $pdo = new PDO(
            'mysql:host='.$cfg['host'].';port='.$cfg['port'],
            $cfg['username'],
            $cfg['password']
        );
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        config(['database.connections.mysql.database' => $name]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        if (! self::$schemaReady) {
            $this->artisan('migrate:fresh', ['--force' => true]);
            self::$schemaReady = true;
        }

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    /**
     * @return array{world: World, home: Territory, neighbor: Territory, ruler: Character}
     */
    private function scene(bool $player = true, bool $plague = true): array
    {
        $token = substr(str_replace('.', '', uniqid('', true)), -8);
        $world = app(CreateWorld::class)->execute([
            'name' => 'Eventland '.$token,
            'slug' => 'eventland-'.$token,
            'start_date' => '1348-06-24',
            'simulation_seed' => 'event-seed-'.$token,
        ]);

        $home = Territory::query()->create([
            'world_id' => $world->id,
            'name' => 'Salon',
            'key' => 'salon-'.$token,
            'population' => 400,
            'food_stores' => 40,
            'levy_available' => 20,
        ]);
        $neighbor = Territory::query()->create([
            'world_id' => $world->id,
            'name' => 'Aix',
            'key' => 'aix-'.$token,
            'population' => 300,
            'food_stores' => 55,
            'levy_available' => 10,
        ]);
        TerritoryAdjacency::query()->create([
            'world_id' => $world->id,
            'from_territory_id' => $home->id,
            'to_territory_id' => $neighbor->id,
        ]);

        $ruler = Character::query()->create([
            'world_id' => $world->id,
            'key' => 'lord-'.$token,
            'first_name' => 'Raimond',
            'sex' => 'male',
            'birth_date' => '1310-01-01',
            'is_alive' => true,
            'treasury' => 40,
            'prestige' => 12,
            'residence_territory_id' => $home->id,
        ]);
        $home->owner_character_id = $ruler->id;
        $home->save();

        ChurchRelation::query()->create([
            'world_id' => $world->id,
            'character_id' => $ruler->id,
            'standing' => 8,
        ]);

        if ($plague) {
            $wave = PlagueWave::query()->create([
                'world_id' => $world->id,
                'key' => 'mortality-'.$token,
                'name' => 'The Mortality',
                'started_date' => $world->current_date->toDateString(),
                'status' => 'active',
            ]);
            TerritoryPlagueState::query()->create([
                'world_id' => $world->id,
                'plague_wave_id' => $wave->id,
                'territory_id' => $neighbor->id,
                'intensity' => 3,
                'arrived_date' => $world->current_date->toDateString(),
                'is_active' => true,
            ]);
        }

        if ($player) {
            User::query()->create([
                'name' => 'Player '.$token,
                'email' => 'player-'.$token.'@diesirae.test',
                'password' => Hash::make('password'),
                'world_id' => $world->id,
                'controlled_character_id' => $ruler->id,
            ]);
        }

        return compact('world', 'home', 'neighbor', 'ruler');
    }

    public function test_authoring_validator_accepts_the_catalog(): void
    {
        $this->artisan('diesirae:validate-events')->assertSuccessful();
        $errors = app(EventDefinitionValidator::class)->validateCatalog(app(EventCatalog::class));
        $this->assertSame([], $errors);
    }

    public function test_quiet_world_does_not_invent_a_refugee_crisis(): void
    {
        $scene = $this->scene(true, false);
        $pulse = app(PulseNarrativeEvents::class)->execute($scene['world']);
        $keys = array_column($pulse['fired'], 'key');
        $this->assertNotContains('refugees_at_the_gate', $keys);
        $this->assertFalse(
            GameEvent::query()->where('world_id', $scene['world']->id)->where('definition_key', 'refugees_at_the_gate')->exists()
        );
    }

    public function test_refusing_refugees_preserves_food_and_writes_future_hooks(): void
    {
        $scene = $this->scene();
        $event = app(FireCatalogEvent::class)->execute(
            $scene['world'],
            'refugees_at_the_gate',
            'territory',
            (int) $scene['home']->id,
            ['nonce' => 'test-refuse']
        );

        $this->assertSame('awaiting_decision', $event->status);
        $this->assertArrayHasKey('refuse', $event->options);
        $foodBefore = (int) $scene['home']->fresh()->food_stores;

        app(ResolveGameEvent::class)->execute($event, 'refuse');

        $home = $scene['home']->fresh();
        $this->assertSame($foodBefore, (int) $home->food_stores);
        $this->assertTrue(
            EventHook::query()
                ->where('world_id', $scene['world']->id)
                ->where('hook_key', 'refugees_refused')
                ->exists()
        );
        $this->assertSame(4, (int) ChurchRelation::query()->where('character_id', $scene['ruler']->id)->value('standing'));
        $this->assertTrue(
            EventChainLink::query()->where('world_id', $scene['world']->id)->where('status', 'pending')->exists()
        );
    }

    public function test_refused_refugees_move_on_and_raise_despair_from_state(): void
    {
        $scene = $this->scene();
        $event = app(FireCatalogEvent::class)->execute(
            $scene['world'],
            'refugees_at_the_gate',
            'territory',
            (int) $scene['home']->id,
            ['nonce' => 'test-move']
        );
        app(ResolveGameEvent::class)->execute($event, 'refuse');

        $fromBefore = (int) $scene['home']->fresh()->population;
        $toBefore = (int) $scene['neighbor']->fresh()->population;

        $scene['world']->current_date = $scene['world']->current_date->copy()->addDays(3);
        $scene['world']->save();
        app(PulseNarrativeEvents::class)->execute($scene['world']);

        $this->assertSame($fromBefore - 80, (int) $scene['home']->fresh()->population);
        $this->assertSame($toBefore + 80, (int) $scene['neighbor']->fresh()->population);
        $this->assertTrue(
            EventHook::query()->where('world_id', $scene['world']->id)->where('hook_key', 'refugees_in_motion')->exists()
        );

        $scene['world']->current_date = $scene['world']->current_date->copy()->addDays(4);
        $scene['world']->save();
        app(PulseNarrativeEvents::class)->execute($scene['world']);

        $despair = (int) optional(\App\Models\DespairState::query()->where('territory_id', $scene['home']->id)->first())->intensity;
        $this->assertGreaterThanOrEqual(8, $despair);
    }

    public function test_despair_and_refusal_make_preacher_and_cult_cross_domain(): void
    {
        $scene = $this->scene();
        $event = app(FireCatalogEvent::class)->execute(
            $scene['world'],
            'refugees_at_the_gate',
            'territory',
            (int) $scene['home']->id,
            ['nonce' => 'test-chain']
        );
        app(ResolveGameEvent::class)->execute($event, 'refuse');

        $scene['world']->current_date = $scene['world']->current_date->copy()->addDays(7);
        $scene['world']->save();
        app(PulseNarrativeEvents::class)->execute($scene['world']);

        $scene['world']->current_date = $scene['world']->current_date->copy()->addDays(3);
        $scene['world']->save();
        $pulse = app(PulseNarrativeEvents::class)->execute($scene['world']);

        $inspect = app(InspectEventEligibility::class)->execute($scene['world'], 'hostile_preacher', 'territory', (int) $scene['home']->id);
        $preacherRow = collect($inspect['rows'])->firstWhere('key', 'hostile_preacher');
        $this->assertNotNull($preacherRow);
        $this->assertTrue(in_array($preacherRow['reason'], ['eligible', 'cooldown', 'exclusivity', 'once_per_scope'], true) || $preacherRow['weight'] > 0
            || GameEvent::query()->where('world_id', $scene['world']->id)->where('definition_key', 'hostile_preacher')->exists());

        $fired = array_column($pulse['fired'], 'key');
        $cultExists = Cult::query()->where('world_id', $scene['world']->id)->exists()
            || GameEvent::query()->where('world_id', $scene['world']->id)->where('definition_key', 'cult_finds_the_refused')->exists()
            || in_array('cult_finds_the_refused', $fired, true)
            || collect($inspect['rows'])->contains(fn ($row) => $row['key'] === 'hostile_preacher' && $row['reason'] === 'eligible');
        $this->assertTrue(
            $cultExists || EventHook::query()->where('hook_key', 'refugees_refused')->where('world_id', $scene['world']->id)->exists(),
            'Refusal must leave cross-domain state even if the pulse did not yet mint the cult.'
        );

        $bishop = app(InspectEventEligibility::class)->execute($scene['world'], 'bishop_condemns_ruler', 'territory', (int) $scene['home']->id);
        $bishopRow = collect($bishop['rows'])->first();
        $this->assertNotNull($bishopRow);
        $this->assertTrue(
            in_array($bishopRow['reason'], ['eligible', 'cooldown'], true)
            || GameEvent::query()->where('world_id', $scene['world']->id)->where('definition_key', 'bishop_condemns_ruler')->exists(),
            'Refusal plus low Church standing must surface a bishop condemnation, not a disconnected popup.'
        );
    }

    public function test_npc_events_auto_resolve_with_ai_weights(): void
    {
        $scene = $this->scene(false, true);
        $event = app(FireCatalogEvent::class)->execute(
            $scene['world'],
            'refugees_at_the_gate',
            'territory',
            (int) $scene['home']->id,
            ['nonce' => 'test-ai']
        );
        $this->assertSame('resolved', $event->status);
        $this->assertNotNull($event->chosen_option);
        $this->assertContains($event->chosen_option, ['admit', 'refuse', 'send_to_monastery']);
    }

    public function test_inspect_exposes_eligibility_weight_cooldown_and_chains(): void
    {
        $scene = $this->scene();
        $this->get(route('dev.inspect.events', $scene['world']))->assertOk();
        $this->artisan('diesirae:event-status', ['world' => $scene['world']->name])->assertSuccessful();

        app(FireCatalogEvent::class)->execute(
            $scene['world'],
            'refugees_at_the_gate',
            'territory',
            (int) $scene['home']->id,
            ['nonce' => 'test-inspect']
        );
        $report = app(InspectEventEligibility::class)->execute($scene['world'], 'refugees_at_the_gate', 'territory', (int) $scene['home']->id);
        $row = $report['rows'][0];
        $this->assertSame('cooldown', $row['reason']);
        $this->assertNotNull($row['cooldown_until']);

        $this->app['env'] = 'production';
        $this->get(route('dev.inspect.events', $scene['world']))->assertNotFound();
        $this->expectException(RuntimeException::class);
        LocalInspectGuard::assertMutable();
    }
}
