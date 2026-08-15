<?php

namespace Tests\Feature\World;

use App\Domain\Enums\PapacyStatus;
use App\Domain\World\HistoricalPack;
use App\Models\Character;
use App\Models\Papacy;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\World;
use App\Validation\WorldIntegrityValidator;
use Database\Seeders\FixtureWorldSeeder;
use Database\Seeders\HistoricalWorldSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HistoricalWorldSeedTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HistoricalWorldSeeder::class);
    }

    public function test_pack_loads_europa_1347(): void
    {
        $pack = HistoricalPack::load();
        $this->assertSame('europa-1347', $pack['world']['slug']);
        $this->assertSame('1347-10-01', $pack['world']['start_date']);
        $this->assertGreaterThanOrEqual(50, count($pack['geography']['territories']));
    }

    public function test_seed_creates_edward_clement_and_avignon_papacy(): void
    {
        $world = World::query()->where('slug', HistoricalPack::SLUG)->firstOrFail();
        $this->assertSame(1347, (int) $world->start_date->format('Y'));
        $this->assertSame('1347-10-01', $world->current_date->toDateString());

        $edward = Character::query()->where('world_id', $world->id)->where('key', 'edward-iii')->firstOrFail();
        $england = Title::query()->where('world_id', $world->id)->where('key', 'k-england')->firstOrFail();
        $this->assertSame($edward->id, $england->currentOwnership->holder_character_id);

        $clement = Character::query()->where('world_id', $world->id)->where('key', 'clement-vi')->firstOrFail();
        $papacy = Papacy::query()->where('world_id', $world->id)->firstOrFail();
        $this->assertSame(PapacyStatus::OCCUPIED, $papacy->status);
        $this->assertSame('see-avignon', $papacy->papalSee->key);
        $this->assertSame($clement->id, $papacy->papalOffice->currentHoldership->holder_character_id);
    }

    public function test_plague_is_at_entry_ports_not_western_capitals(): void
    {
        $world = World::query()->where('slug', HistoricalPack::SLUG)->firstOrFail();
        $infected = TerritoryPlagueState::query()
            ->where('world_id', $world->id)
            ->pluck('territory_id');
        $keys = Territory::query()->whereIn('id', $infected)->pluck('key')->all();

        $this->assertContains('messina', $keys);
        $this->assertContains('caffa', $keys);
        $this->assertContains('constantinople', $keys);
        $this->assertNotContains('london', $keys);
        $this->assertNotContains('paris', $keys);
    }

    public function test_historical_validator_accepts_europa_1347(): void
    {
        $world = World::query()->where('slug', HistoricalPack::SLUG)->firstOrFail();
        $report = (new WorldIntegrityValidator())->validate($world, true);

        $this->assertTrue($report->passed(), json_encode($report->toArray()));
    }

    public function test_historical_flag_on_command_passes_europa(): void
    {
        $this->artisan('diesirae:validate-world', [
            'world' => HistoricalPack::SLUG,
            '--historical' => true,
        ])->assertSuccessful();
    }

    public function test_historical_flag_rejects_fixture_world(): void
    {
        $this->seed(FixtureWorldSeeder::class);

        $this->artisan('diesirae:validate-world', [
            'world' => FixtureWorldSeeder::WORLD_SLUG,
            '--historical' => true,
        ])->assertFailed();

        $fixture = World::query()->where('slug', FixtureWorldSeeder::WORLD_SLUG)->firstOrFail();
        $report = (new WorldIntegrityValidator())->validate($fixture, true);
        $this->assertFalse($report->passed());
        $this->assertTrue($report->hasCode('historical.year') || $report->hasCode('historical.scope'));
    }
}
