<?php

namespace Tests\Unit\Population;

use App\Domain\Enums\SocialClass;
use App\Domain\Population\PopulationCohorts;
use Tests\DomainTestCase;

class PopulationCohortsTest extends DomainTestCase
{
    public function test_extract_conserves_souls(): void
    {
        $cohorts = new PopulationCohorts(40, 80, 400, 2400, 200);
        $before = $cohorts->souls();
        $split = $cohorts->extract(333, [
            SocialClass::PEASANTS => 140,
            SocialClass::BURGHERS => 90,
            SocialClass::UNFREE => 110,
            SocialClass::CLERGY => 40,
            SocialClass::NOBLES => 25,
        ]);

        $this->assertSame(333, $split['taken']->souls());
        $this->assertSame($before - 333, $split['remaining']->souls());
        $this->assertSame($before, $split['taken']->souls() + $split['remaining']->souls());
    }

    public function test_cannot_construct_a_negative_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PopulationCohorts(0, 0, 0, -1, 0);
    }

    public function test_extracting_more_than_exist_takes_everyone(): void
    {
        $cohorts = new PopulationCohorts(1, 2, 3, 4, 5);
        $split = $cohorts->extract(1000, [SocialClass::PEASANTS => 100]);

        $this->assertSame(0, $split['remaining']->souls());
        $this->assertSame(15, $split['taken']->souls());
    }

    public function test_military_age_and_workforce_are_derived(): void
    {
        $cohorts = new PopulationCohorts(100, 100, 100, 100, 100);
        $this->assertGreaterThan(0, $cohorts->militaryAge());
        $this->assertGreaterThan(0, $cohorts->workforce());
        $this->assertGreaterThan($cohorts->souls(), $cohorts->foodDemand());
        $this->assertSame(500, $cohorts->souls());
    }
}
