<?php

namespace Tests\Feature\Foundation;

use App\Actions\World\CreateWorld;
use App\Domain\Support\WorldBoundary;
use App\Models\World;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class WorldBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_world_uses_dies_irae_database(): void
    {
        $world = (new CreateWorld())([
            'slug' => 'test-world',
            'name' => 'Test World',
            'start_date' => '1348-01-01',
        ]);

        $this->assertInstanceOf(World::class, $world);
        $this->assertSame('dies_irae_game_testing', config('database.connections.mysql.database'));
        $this->assertNotSame('feudalism_game', config('database.connections.mysql.database'));
    }

    public function test_cross_world_relationship_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WorldBoundary::assertSameWorld(1, 2, 'title.holder');
    }
}
