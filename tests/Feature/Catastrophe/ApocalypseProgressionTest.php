<?php

namespace Tests\Feature\Catastrophe;

use App\Actions\Apocalypse\ProcessApocalypseTick;
use App\Actions\Apocalypse\RecordApocalypseSignal;
use App\Actions\Time\ProcessDueWorldEvents;
use App\Actions\World\CreateWorld;
use App\Domain\Support\LocalInspectGuard;
use App\Models\ApocalypseMilestoneRecord;
use App\Models\ApocalypseSignal;
use App\Models\ScheduledWorldEvent;
use App\Models\Territory;
use App\Models\TerritorySpiritualWeather;
use App\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Feature\LaravelTestCase;

class ApocalypseProgressionTest extends LaravelTestCase
{
    use RefreshDatabase;

    private function world(array $attributes = []): World
    {
        $token = $this->uniqueName();

        return app(CreateWorld::class)->execute(array_merge([
            'name' => 'Test Europa '.$token,
            'slug' => 'test-europa-'.$token,
            'start_date' => '1347-10-01',
        ], $attributes));
    }

    private function uniqueName(): string
    {
        return substr(str_replace('.', '', uniqid('', true)), -8);
    }

    public function test_new_world_begins_in_ordinary_phase(): void
    {
        $world = $this->world();
        $state = $world->apocalypseState;

        $this->assertNotNull($state);
        $this->assertSame('ordinary', $state->phase_key);
        $this->assertSame(1, $state->phase_ordinal);
        $this->assertSame(0, $state->plague_severity);
        $this->assertSame(0, $state->demonic_manifestation);
        $this->assertGreaterThan(70, $state->church_cohesion);
        $this->assertLessThan(10, $state->pressure);
        $this->assertSame(1, ScheduledWorldEvent::query()
            ->where('world_id', $world->id)
            ->where('event_type', 'apocalypse_stage_check')
            ->count());
    }

    public function test_time_alone_does_not_leave_the_ordinary_world(): void
    {
        $world = $this->world();
        $tick = app(ProcessApocalypseTick::class);

        for ($i = 0; $i < 40; $i++) {
            $world->current_date = $world->current_date->copy()->addDays(7);
            $world->save();
            $tick->execute($world);
        }

        $state = $world->fresh()->apocalypseState;
        $this->assertSame('ordinary', $state->phase_key);
        $this->assertSame(0, $state->plague_severity);
        $this->assertSame(40, $state->tick_count);
    }

    public function test_desecration_advances_to_portents(): void
    {
        $world = $this->world();
        app(RecordApocalypseSignal::class)->execute($world, 'desecration', 25);

        $state = $world->fresh()->apocalypseState;
        $this->assertSame('portents', $state->phase_key);
        $this->assertGreaterThanOrEqual(4, $state->meter_floors['global_corruption'] ?? 0);
        $this->assertSame(1, $world->apocalypseChronicle()->where('entry_type', 'phase')->count());
    }

    public function test_pestilence_requires_plague_mortality_not_generic_sin(): void
    {
        $world = $this->world();
        $record = app(RecordApocalypseSignal::class);
        $record->execute($world, 'desecration', 25);
        $this->assertSame('portents', $world->fresh()->apocalypseState->phase_key);

        $record->execute($world, 'mass_sin', 8);
        $this->assertSame('portents', $world->fresh()->apocalypseState->phase_key);

        $record->execute($world, 'plague_mortality', 15);
        $this->assertSame('great_pestilence', $world->fresh()->apocalypseState->phase_key);
        $this->assertTrue(
            ApocalypseMilestoneRecord::query()
                ->where('world_id', $world->id)
                ->where('milestone_key', 'first_plague_wave')
                ->exists()
        );
    }

    public function test_resistance_cannot_farm_the_world_backwards(): void
    {
        $world = $this->world();
        $record = app(RecordApocalypseSignal::class);
        $record->execute($world, 'desecration', 25);
        $record->execute($world, 'plague_mortality', 20);

        $state = $world->fresh()->apocalypseState;
        $this->assertSame('great_pestilence', $state->phase_key);
        $plagueFloor = (int) ($state->meter_floors['plague_severity'] ?? 0);
        $this->assertGreaterThan(0, $plagueFloor);

        for ($i = 0; $i < 30; $i++) {
            $record->execute($world, 'restored_monastery', 100);
            $record->execute($world, 'saint', 100);
            $record->execute($world, 'repentance', 100);
        }

        $after = $world->fresh()->apocalypseState;
        $this->assertSame('great_pestilence', $after->phase_key);
        $this->assertGreaterThanOrEqual($plagueFloor, $after->plague_severity);
        $this->assertGreaterThanOrEqual((int) ($after->meter_floors['despair'] ?? 0), $after->despair);
        $this->assertSame(
            1,
            ApocalypseMilestoneRecord::query()
                ->where('world_id', $world->id)
                ->where('milestone_key', 'first_plague_wave')
                ->count()
        );
    }

    public function test_signal_idempotency_and_cross_world_isolation(): void
    {
        $a = $this->world();
        $b = $this->world();
        $record = app(RecordApocalypseSignal::class);

        $first = $record->execute($a, 'mass_sin', 10, ['idempotency_key' => 'sin:once']);
        $again = $record->execute($a, 'mass_sin', 10, ['idempotency_key' => 'sin:once']);
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, ApocalypseSignal::query()->where('world_id', $a->id)->count());

        $record->execute($b, 'desecration', 25);
        $this->assertSame('ordinary', $a->fresh()->apocalypseState->phase_key);
        $this->assertSame('portents', $b->fresh()->apocalypseState->phase_key);
    }

    public function test_local_sanctuary_can_improve_without_resetting_the_age(): void
    {
        $world = $this->world();
        $territory = Territory::query()->create([
            'world_id' => $world->id,
            'name' => 'Cluny',
            'key' => 'cluny',
        ]);
        $record = app(RecordApocalypseSignal::class);
        $record->execute($world, 'desecration', 25);
        $record->execute($world, 'plague_mortality', 20);
        $record->execute($world, 'destroyed_monastery', 20, ['territory_id' => $territory->id]);
        $record->execute($world, 'restored_monastery', 40, ['territory_id' => $territory->id]);

        $state = $world->fresh()->apocalypseState;
        $weather = TerritorySpiritualWeather::query()
            ->where('world_id', $world->id)
            ->where('territory_id', $territory->id)
            ->firstOrFail();

        $this->assertSame('great_pestilence', $state->phase_key);
        $this->assertLessThan($state->despair, $weather->local_despair);
        $this->assertGreaterThan(0, $weather->local_sanctity);
    }

    public function test_scheduled_tick_is_idempotent_on_the_same_date(): void
    {
        $world = $this->world();
        $world->current_date = $world->current_date->copy()->addDays(7);
        $world->save();

        $first = app(ProcessApocalypseTick::class)->execute($world);
        $second = app(ProcessApocalypseTick::class)->execute($world);

        $this->assertFalse($first['skipped']);
        $this->assertTrue($second['skipped']);
        $this->assertSame(1, $world->fresh()->apocalypseState->tick_count);
    }

    public function test_due_world_event_runs_the_stage_check_handler(): void
    {
        $world = $this->world();
        $event = ScheduledWorldEvent::query()
            ->where('world_id', $world->id)
            ->where('event_type', 'apocalypse_stage_check')
            ->firstOrFail();
        $world->current_date = $event->process_at;
        $world->save();

        $result = app(ProcessDueWorldEvents::class)->execute($world);
        $this->assertSame(1, $result['processed']);
        $this->assertGreaterThanOrEqual(1, $world->fresh()->apocalypseState->tick_count);
    }

    public function test_debug_inspect_and_commands_are_local_only(): void
    {
        $world = $this->world();

        $this->get(route('dev.inspect.apocalypse', $world))->assertOk();
        $this->post(route('dev.inspect.apocalypse.signal', $world), [
            'signal_key' => 'mass_sin',
            'magnitude' => 5,
        ])->assertRedirect();

        $this->artisan('diesirae:apocalypse-status', ['world' => $world->name])->assertSuccessful();
        $this->artisan('diesirae:apocalypse-tick', ['world' => $world->name, '--force' => true])->assertSuccessful();

        $this->app['env'] = 'production';
        $this->get(route('dev.inspect.apocalypse', $world))->assertNotFound();
        $this->expectException(RuntimeException::class);
        LocalInspectGuard::assertMutable();
    }

    public function test_phase_ordinal_never_decreases(): void
    {
        $world = $this->world();
        $record = app(RecordApocalypseSignal::class);
        $record->execute($world, 'desecration', 25);
        $record->execute($world, 'plague_mortality', 20);
        $ordinal = (int) $world->fresh()->apocalypseState->phase_ordinal;

        $record->execute($world, 'holy_victory', 100);
        $this->assertGreaterThanOrEqual($ordinal, (int) $world->fresh()->apocalypseState->phase_ordinal);
    }
}
