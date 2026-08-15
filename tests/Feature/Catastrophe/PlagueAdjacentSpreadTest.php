<?php

namespace Tests\Feature\Catastrophe;

use App\Domain\Enums\SpreadVector;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class PlagueAdjacentSpreadTest extends DomainTestCase
{
    public function test_plague_spreads_to_an_adjacent_settlement(): void
    {
        $engine = WorldFixture::adjacentPair();
        $engine->seedPlague('aix', 900);

        $this->assertSame(0, $engine->settlement('salon')->incubating);
        $this->assertSame(0, $engine->settlement('salon')->infectious);

        $engine->tick(1);

        $salon = $engine->settlement('salon');
        $this->assertGreaterThan(0, $salon->incubating + $salon->infectious);
        $engine->assertNonNegative();
    }

    public function test_adjacent_spread_uses_the_adjacent_vector(): void
    {
        $engine = WorldFixture::adjacentPair();
        $vectors = [];
        foreach ($engine->links as $link) {
            $vectors[] = $link->vector;
        }

        $this->assertContains(SpreadVector::ADJACENT, $vectors);
        $this->assertNotContains(SpreadVector::TRADE, $vectors);
    }

    public function test_incubation_delays_infectious_onset_in_the_neighbor(): void
    {
        $engine = WorldFixture::adjacentPair();
        $engine->seedPlague('aix', 900);
        $engine->tick(1);

        $this->assertGreaterThan(0, $engine->settlement('salon')->incubating);
        $this->assertSame(0, $engine->settlement('salon')->infectious);

        $engine->tick($engine->profile->incubationTicks);
        $this->assertGreaterThan(0, $engine->settlement('salon')->infectious);
    }
}
