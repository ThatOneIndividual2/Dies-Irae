<?php

namespace Tests\Feature\Catastrophe;

use App\Actions\Population\DisplaceCohorts;
use App\Domain\Enums\DisplacementCause;
use App\Domain\Enums\RuinState;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class RefugeeFlowTest extends DomainTestCase
{
    public function test_refugees_leave_one_settlement_and_arrive_in_another(): void
    {
        $engine = WorldFixture::adjacentPair();
        $source = $engine->settlement('aix');
        $dest = $engine->settlement('salon');
        $sourceBefore = $source->souls();
        $destBefore = $dest->souls();

        $flow = (new DisplaceCohorts())->execute($engine, 'aix', 'salon', 500, DisplacementCause::PLAGUE);

        $this->assertSame('absorbed', $flow->status);
        $this->assertSame($sourceBefore - 500, $source->souls());
        $this->assertGreaterThan($destBefore, $dest->souls());
        $this->assertLessThan($destBefore + 500, $dest->souls());
        $engine->assertNonNegative();
    }

    public function test_refugees_are_cohorts_not_individual_npcs(): void
    {
        $engine = WorldFixture::adjacentPair();
        $flow = $engine->displace('aix', 'salon', 400, DisplacementCause::PLAGUE);

        $this->assertGreaterThan(0, $flow->cohorts->peasants);
        $this->assertSame(
            $flow->souls(),
            $flow->cohorts->nobles + $flow->cohorts->clergy + $flow->cohorts->burghers + $flow->cohorts->peasants + $flow->cohorts->unfree
        );
    }

    public function test_fleeing_people_carry_plague_with_them(): void
    {
        $engine = WorldFixture::adjacentPair();
        $engine->seedPlague('aix', 800);
        $destBefore = $engine->settlement('salon')->incubating + $engine->settlement('salon')->infectious;

        $engine->displace('aix', 'salon', 600, DisplacementCause::PLAGUE);

        $carried = $engine->settlement('salon')->incubating + $engine->settlement('salon')->infectious;
        $this->assertGreaterThan($destBefore, $carried);
    }

    public function test_overrun_settlements_turn_refugees_away(): void
    {
        $engine = WorldFixture::adjacentPair();
        $engine->occupyWithHell('salon');
        $sourceBefore = $engine->settlement('aix')->souls();

        $flow = $engine->displace('aix', 'salon', 200, DisplacementCause::HELL);

        $this->assertSame('turned_away', $flow->status);
        $this->assertSame($sourceBefore - 200, $engine->settlement('aix')->souls());
        $this->assertSame(RuinState::OVERRUN, $engine->settlement('salon')->ruinState);
    }

    public function test_population_never_goes_negative_when_everyone_flees(): void
    {
        $engine = WorldFixture::adjacentPair();
        $souls = $engine->settlement('salon')->souls();
        $engine->displace('salon', 'aix', $souls, DisplacementCause::RUIN);

        $this->assertSame(0, $engine->settlement('salon')->souls());
        $engine->assertNonNegative();
        foreach ($engine->settlement('salon')->cohorts->toArray() as $n) {
            $this->assertGreaterThanOrEqual(0, $n);
        }
    }

    public function test_road_deaths_are_taken_from_the_column_not_invented(): void
    {
        $engine = WorldFixture::adjacentPair();
        $sourceBefore = $engine->settlement('aix')->souls();
        $destBefore = $engine->settlement('salon')->souls();
        $unburiedBefore = $engine->settlement('salon')->unburied;

        $engine->displace('aix', 'salon', 500, DisplacementCause::PLAGUE);

        $sourceAfter = $engine->settlement('aix')->souls();
        $destAfter = $engine->settlement('salon')->souls();
        $unburiedAfter = $engine->settlement('salon')->unburied;
        $accounted = $sourceAfter + $destAfter + ($unburiedAfter - $unburiedBefore);

        $this->assertSame($sourceBefore + $destBefore, $accounted);
    }
}
