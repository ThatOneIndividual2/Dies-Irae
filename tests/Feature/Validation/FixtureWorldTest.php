<?php

namespace Tests\Feature\Validation;

use App\Domain\Church\ChurchContract;
use App\Domain\Enums\HoldingType;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Domain\World\WorldContract;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\CorruptionState;
use App\Models\DemonicThreat;
use App\Models\Dynasty;
use App\Models\Holding;
use App\Models\Monastery;
use App\Models\Realm;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\VassalRelationship;
use App\Models\World;
use App\Validation\WorldIntegrityValidator;
use Database\Seeders\FixtureWorldSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FixtureWorldTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FixtureWorldSeeder::class);
    }

    public function test_fixture_contains_required_political_and_spiritual_actors(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();

        $king = Character::query()->where('world_id', $world->id)->where('key', 'aldric-lys')->firstOrFail();
        $duke = Character::query()->where('world_id', $world->id)->where('key', 'renard-lys')->firstOrFail();
        $count = Character::query()->where('world_id', $world->id)->where('key', 'michel-cressy')->firstOrFail();
        $bishop = Character::query()->where('world_id', $world->id)->where('key', 'tomas-of-lys')->firstOrFail();

        $this->assertTrue($king->is_alive);
        $this->assertTrue($duke->is_alive);
        $this->assertTrue($count->is_alive);
        $this->assertTrue($bishop->is_alive);

        $kingdom = Title::query()->where('world_id', $world->id)->where('rank', TitleRank::KINGDOM)->firstOrFail();
        $duchy = Title::query()->where('world_id', $world->id)->where('rank', TitleRank::DUCHY)->firstOrFail();
        $county = Title::query()->where('world_id', $world->id)->where('rank', TitleRank::COUNTY)->firstOrFail();

        $this->assertSame($king->id, $kingdom->currentOwnership->holder_character_id);
        $this->assertSame($duke->id, $duchy->currentOwnership->holder_character_id);
        $this->assertSame($count->id, $county->currentOwnership->holder_character_id);

        $this->assertSame(0, $bishop->currentTitleOwnerships()->count());
        $this->assertSame(1, $bishop->currentSpiritualHolderships()->count());
        $this->assertSame(SpiritualOfficeRank::BISHOP, $bishop->currentSpiritualHolderships()->first()->office->rank);
    }

    public function test_vassal_chain_and_realm_are_linked(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();
        $king = Character::query()->where('key', 'aldric-lys')->firstOrFail();
        $duke = Character::query()->where('key', 'renard-lys')->firstOrFail();
        $count = Character::query()->where('key', 'michel-cressy')->firstOrFail();

        $realm = Realm::query()->where('world_id', $world->id)->firstOrFail();
        $this->assertSame($king->id, $realm->top_liege_character_id);
        $this->assertSame(1, Realm::query()->where('world_id', $world->id)->count());

        $dukeBond = VassalRelationship::query()
            ->where('world_id', $world->id)
            ->where('vassal_character_id', $duke->id)
            ->where('is_current', 1)
            ->firstOrFail();
        $countBond = VassalRelationship::query()
            ->where('world_id', $world->id)
            ->where('vassal_character_id', $count->id)
            ->where('is_current', 1)
            ->firstOrFail();

        $this->assertSame($king->id, $dukeBond->liege_character_id);
        $this->assertSame($duke->id, $countBond->liege_character_id);
        $this->assertSame($realm->id, $dukeBond->realm_id);
        $this->assertSame($realm->id, $countBond->realm_id);
    }

    public function test_one_dynasty_one_church_province_and_settlements(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();

        $this->assertSame(1, Dynasty::query()->where('world_id', $world->id)->count());
        $this->assertSame(1, ChurchProvince::query()->where('world_id', $world->id)->count());
        $this->assertSame(1, See::query()->where('world_id', $world->id)->count());
        $this->assertSame(1, Monastery::query()->where('world_id', $world->id)->count());

        $types = Holding::query()->where('world_id', $world->id)->pluck('holding_type')->all();
        $this->assertContains(HoldingType::TOWN, $types);
        $this->assertContains(HoldingType::VILLAGE, $types);
        $this->assertContains(HoldingType::MONASTERY, $types);
    }

    public function test_plague_corruption_and_dormant_demonic_threat_exist(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();

        $this->assertSame(1, TerritoryPlagueState::query()->where('world_id', $world->id)->count());
        $this->assertSame(1, CorruptionState::query()->where('world_id', $world->id)->count());

        $threat = DemonicThreat::query()->where('world_id', $world->id)->firstOrFail();
        $this->assertSame('dormant', $threat->status);
        $this->assertSame('dormant', $threat->faction->status);
    }

    public function test_integrity_validator_accepts_fixture(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();
        $report = (new WorldIntegrityValidator())->validate($world);

        $this->assertTrue($report->passed(), json_encode($report->toArray()));
    }

    public function test_spiritual_rank_on_a_title_fails_validation(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();

        Title::query()->create([
            'world_id' => $world->id,
            'key' => 'fake-bishopric',
            'name' => 'Fake Bishopric',
            'rank' => SpiritualOfficeRank::BISHOP,
        ]);

        $report = (new WorldIntegrityValidator())->validate($world);

        $this->assertFalse($report->passed());
        $this->assertTrue($report->hasCode('separation.spiritual_title_rank'));
    }

    public function test_domain_contracts_are_bound(): void
    {
        $this->assertSame('world', app(WorldContract::class)->domainKey());
        $this->assertSame('church', app(ChurchContract::class)->domainKey());
    }

    public function test_inspect_pages_render(): void
    {
        $world = World::query()->where('slug', 'lys-1348')->firstOrFail();

        $this->get('/dev/inspect')->assertOk()->assertSee('lys-1348');
        $this->get('/dev/inspect/worlds/'.$world->id)->assertOk()->assertSee('Aldric');
    }
}
