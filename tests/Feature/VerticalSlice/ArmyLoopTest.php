<?php

namespace Tests\Feature\VerticalSlice;

use App\Actions\Army\MoveArmy;
use App\Actions\Army\RaiseArmy;
use App\Actions\Campaign\SeedVerticalSlice;
use App\Models\Territory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ArmyLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_levy_can_only_march_into_adjacent_land(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $army = app(RaiseArmy::class)->execute($slice->ruler, $slice->territory('salon'), 80);

        $this->expectException(RuntimeException::class);
        app(MoveArmy::class)->execute($army, $slice->territory('miramas'));
    }

    public function test_cannot_raise_more_than_available_levy(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $salon = $slice->territory('salon');

        $this->expectException(RuntimeException::class);
        app(RaiseArmy::class)->execute($slice->ruler, $salon, ((int) $salon->levy_available) + 1);
    }

    public function test_raising_an_army_reduces_settlement_levy(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $salon = $slice->territory('salon');
        $before = (int) $salon->levy_available;

        app(RaiseArmy::class)->execute($slice->ruler, $salon, 50);

        $this->assertSame($before - 50, (int) Territory::query()->whereKey($salon->id)->value('levy_available'));
    }
}
