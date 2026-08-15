<?php

namespace Tests\Feature\VerticalSlice;

use App\Actions\Campaign\SeedVerticalSlice;
use App\Actions\World\CreateWorld;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Models\GameEvent;
use App\Models\Title;
use App\Validation\WorldIntegrityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualAuthorityAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bishopric_cannot_be_stored_as_a_title(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();

        Title::query()->create([
            'world_id' => $slice->world->id,
            'key' => 'fake-bishopric',
            'name' => 'Fake Bishopric',
            'rank' => SpiritualOfficeRank::BISHOP,
        ]);

        $report = (new WorldIntegrityValidator())->validate($slice->world);
        $this->assertFalse($report->passed());
        $this->assertTrue($report->hasCode('separation.spiritual_title_rank'));
    }

    public function test_a_second_world_does_not_see_slice_events(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $other = app(CreateWorld::class)->execute([
            'slug' => 'other-1347',
            'name' => 'Other',
            'start_date' => '1347-11-01',
        ]);

        $this->assertSame(
            10,
            GameEvent::query()->where('world_id', $slice->world->id)->count()
        );
        $this->assertSame(
            0,
            GameEvent::query()->where('world_id', $other->id)->count()
        );
        $this->assertNotSame('feudalism_game', config('database.connections.mysql.database'));
        $this->assertSame('dies_irae_game_testing', config('database.connections.mysql.database'));
    }
}
