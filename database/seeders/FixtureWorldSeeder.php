<?php

namespace Database\Seeders;

use App\Actions\World\CreateWorld;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\HoldingType;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Models\ApocalypseState;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\ClergyStatus;
use App\Models\CorruptionState;
use App\Models\DemonicFaction;
use App\Models\DemonicThreat;
use App\Models\Dynasty;
use App\Models\DynastyHouse;
use App\Models\Faith;
use App\Models\Holding;
use App\Models\Monastery;
use App\Models\PlagueWave;
use App\Models\Realm;
use App\Models\Region;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use App\Models\SuccessionLaw;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\TitleOwnership;
use App\Models\VassalRelationship;
use App\Models\World;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixtureWorldSeeder extends Seeder
{
    public const WORLD_SLUG = 'lys-1348';

    public function run(): void
    {
        if (World::query()->where('slug', self::WORLD_SLUG)->exists()) {
            return;
        }

        $pack = json_decode(
            file_get_contents(database_path('data/europa/fixture.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($pack) {
            $this->seed($pack);
        });
    }

    private function seed(array $pack): void
    {
        $world = (new CreateWorld())($pack['world']);
        $date = $world->current_date->toDateString();

        $law = SuccessionLaw::query()->firstOrCreate(
            ['key' => $pack['succession_law']['key']],
            [
                'name' => $pack['succession_law']['name'],
                'description' => $pack['succession_law']['description'],
            ]
        );

        $faith = Faith::query()->create([
            'world_id' => $world->id,
            'key' => $pack['faith']['key'],
            'name' => $pack['faith']['name'],
            'rite' => $pack['faith']['rite'],
        ]);

        $dynasty = Dynasty::query()->create([
            'world_id' => $world->id,
            'key' => $pack['dynasty']['key'],
            'name' => $pack['dynasty']['name'],
            'motto' => $pack['dynasty']['motto'],
            'founded_date' => '1280-01-01',
        ]);

        $house = DynastyHouse::query()->create([
            'world_id' => $world->id,
            'dynasty_id' => $dynasty->id,
            'key' => 'lys-main',
            'name' => 'Lys',
        ]);

        $king = $this->makeCharacter($world, $dynasty, $house, $faith, $pack['characters']['king']);
        $duke = $this->makeCharacter($world, $dynasty, $house, $faith, $pack['characters']['duke']);
        $count = $this->makeCharacter($world, $dynasty, $house, $faith, $pack['characters']['count']);
        $bishop = $this->makeCharacter($world, null, null, $faith, $pack['characters']['bishop']);

        $dynasty->update(['founder_character_id' => $king->id]);
        $house->update(['head_character_id' => $king->id]);

        $region = Region::query()->create([
            'world_id' => $world->id,
            'key' => 'lys-vale',
            'name' => 'Vale of Lys',
            'map_key' => 'lys-vale',
        ]);

        $capitalTerritory = Territory::query()->create([
            'world_id' => $world->id,
            'region_id' => $region->id,
            'key' => 'lys-city',
            'name' => 'Lys',
            'territory_type' => 'county',
            'terrain_type' => 'riverland',
            'population' => 12000,
            'owner_character_id' => $king->id,
            'controller_character_id' => $king->id,
        ]);

        $valeTerritory = Territory::query()->create([
            'world_id' => $world->id,
            'region_id' => $region->id,
            'key' => 'vale-high',
            'name' => 'High Vale',
            'territory_type' => 'county',
            'terrain_type' => 'hills',
            'population' => 7000,
            'owner_character_id' => $duke->id,
            'controller_character_id' => $duke->id,
        ]);

        $cressyTerritory = Territory::query()->create([
            'world_id' => $world->id,
            'region_id' => $region->id,
            'key' => 'cressy',
            'name' => 'Cressy',
            'territory_type' => 'county',
            'terrain_type' => 'plains',
            'population' => 2800,
            'owner_character_id' => $count->id,
            'controller_character_id' => $count->id,
        ]);

        Holding::query()->create([
            'world_id' => $world->id,
            'territory_id' => $capitalTerritory->id,
            'key' => 'lys-keep',
            'name' => 'Keep of Lys',
            'holding_type' => HoldingType::CASTLE,
            'fortification' => 8,
            'base_tax' => 20,
            'base_levy' => 40,
            'owner_character_id' => $king->id,
        ]);

        $town = Holding::query()->create([
            'world_id' => $world->id,
            'territory_id' => $capitalTerritory->id,
            'key' => 'lys-port',
            'name' => 'Lys-port',
            'holding_type' => HoldingType::TOWN,
            'base_tax' => 30,
            'base_levy' => 10,
            'owner_character_id' => $king->id,
        ]);

        $village = Holding::query()->create([
            'world_id' => $world->id,
            'territory_id' => $cressyTerritory->id,
            'key' => 'cressy-village',
            'name' => 'Cressy Village',
            'holding_type' => HoldingType::VILLAGE,
            'base_tax' => 6,
            'base_levy' => 8,
            'owner_character_id' => $count->id,
        ]);

        $monasteryHolding = Holding::query()->create([
            'world_id' => $world->id,
            'territory_id' => $valeTerritory->id,
            'key' => 'st-amand',
            'name' => 'St. Amand',
            'holding_type' => HoldingType::MONASTERY,
            'base_tax' => 4,
            'base_levy' => 2,
        ]);

        Monastery::query()->create([
            'world_id' => $world->id,
            'holding_id' => $monasteryHolding->id,
            'faith_id' => $faith->id,
            'key' => 'st-amand',
            'name' => 'Abbey of St. Amand',
            'rule' => 'benedictine',
            'religious_population' => 40,
        ]);

        $kingdom = Title::query()->create([
            'world_id' => $world->id,
            'key' => 'kingdom-lys',
            'name' => 'Kingdom of Lys',
            'adjective' => 'Lysian',
            'rank' => TitleRank::KINGDOM,
            'capital_territory_id' => $capitalTerritory->id,
            'primary_territory_id' => $capitalTerritory->id,
            'succession_law_id' => $law->id,
        ]);

        $duchy = Title::query()->create([
            'world_id' => $world->id,
            'key' => 'duchy-vale',
            'name' => 'Duchy of the Vale',
            'rank' => TitleRank::DUCHY,
            'parent_title_id' => $kingdom->id,
            'de_jure_liege_title_id' => $kingdom->id,
            'capital_territory_id' => $valeTerritory->id,
            'primary_territory_id' => $valeTerritory->id,
            'succession_law_id' => $law->id,
        ]);

        $county = Title::query()->create([
            'world_id' => $world->id,
            'key' => 'county-cressy',
            'name' => 'County of Cressy',
            'rank' => TitleRank::COUNTY,
            'parent_title_id' => $duchy->id,
            'de_jure_liege_title_id' => $duchy->id,
            'capital_territory_id' => $cressyTerritory->id,
            'primary_territory_id' => $cressyTerritory->id,
            'succession_law_id' => $law->id,
        ]);

        $this->grantTitle($world, $kingdom, $king, $date);
        $this->grantTitle($world, $duchy, $duke, $date);
        $this->grantTitle($world, $county, $count, $date);

        $realm = Realm::query()->create([
            'world_id' => $world->id,
            'key' => 'kingdom-of-lys',
            'name' => 'Kingdom of Lys',
            'top_liege_character_id' => $king->id,
            'primary_title_id' => $kingdom->id,
            'realm_authority' => 55,
        ]);

        VassalRelationship::query()->create([
            'world_id' => $world->id,
            'realm_id' => $realm->id,
            'liege_character_id' => $king->id,
            'vassal_character_id' => $duke->id,
            'primary_title_id' => $duchy->id,
            'started_date' => $date,
            'is_current' => 1,
        ]);

        VassalRelationship::query()->create([
            'world_id' => $world->id,
            'realm_id' => $realm->id,
            'liege_character_id' => $duke->id,
            'vassal_character_id' => $count->id,
            'primary_title_id' => $county->id,
            'started_date' => $date,
            'is_current' => 1,
        ]);

        $province = ChurchProvince::query()->create([
            'world_id' => $world->id,
            'faith_id' => $faith->id,
            'key' => 'province-lys',
            'name' => 'Ecclesiastical Province of Lys',
        ]);

        $see = See::query()->create([
            'world_id' => $world->id,
            'church_province_id' => $province->id,
            'territory_id' => $capitalTerritory->id,
            'key' => 'diocese-lys',
            'name' => 'Diocese of Lys',
            'see_type' => 'diocese',
        ]);

        $bishopric = SpiritualOffice::query()->create([
            'world_id' => $world->id,
            'key' => 'bishop-of-lys',
            'name' => 'Bishop of Lys',
            'rank' => SpiritualOfficeRank::BISHOP,
            'see_id' => $see->id,
            'church_province_id' => $province->id,
        ]);

        SpiritualOfficeHoldership::query()->create([
            'world_id' => $world->id,
            'spiritual_office_id' => $bishopric->id,
            'holder_character_id' => $bishop->id,
            'acquired_date' => '1340-04-04',
            'is_current' => 1,
        ]);

        ClergyStatus::query()->create([
            'world_id' => $world->id,
            'character_id' => $bishop->id,
            'status' => 'bishop',
            'started_date' => '1340-04-04',
            'is_current' => 1,
        ]);

        $wave = PlagueWave::query()->create($this->plagueWaveAttributes((int) $world->id, $date));

        TerritoryPlagueState::query()->create([
            'world_id' => $world->id,
            'plague_wave_id' => $wave->id,
            'territory_id' => $capitalTerritory->id,
            'intensity' => 4,
            'arrived_date' => $date,
        ]);

        CorruptionState::query()->create([
            'world_id' => $world->id,
            'subject_type' => 'territory',
            'subject_id' => $cressyTerritory->id,
            'intensity' => 2,
            'source' => 'unquiet_burial',
            'started_date' => $date,
        ]);

        $faction = DemonicFaction::query()->create([
            'world_id' => $world->id,
            'key' => 'court-of-the-ash-prince',
            'name' => 'Court of the Ash Prince',
            'status' => 'dormant',
            'agenda' => 'Wait beneath the river until the bells of Lys fall silent.',
        ]);

        DemonicThreat::query()->create([
            'world_id' => $world->id,
            'demonic_faction_id' => $faction->id,
            'territory_id' => $cressyTerritory->id,
            'key' => 'cressy-whisper',
            'name' => 'The Cressy Whisper',
            'status' => 'dormant',
            'intensity' => 0,
        ]);

        $apocalypse = ApocalypseState::query()->firstOrNew(['world_id' => $world->id]);
        if (Schema::hasColumn('apocalypse_states', 'stage')) {
            $apocalypse->stage = ApocalypseStage::GREAT_MORTALITY;
        }
        if (Schema::hasColumn('apocalypse_states', 'signs')) {
            $apocalypse->signs = ['plague_in_lys_port'];
        }
        if (Schema::hasColumn('apocalypse_states', 'stage_entered_date')) {
            $apocalypse->stage_entered_date = $date;
        }
        if (Schema::hasColumn('apocalypse_states', 'phase_key') && ! $apocalypse->phase_key) {
            $apocalypse->phase_key = ApocalypseStage::GREAT_MORTALITY;
        }
        $apocalypse->save();

        unset($town, $village);
    }

    private function plagueWaveAttributes(int $worldId, string $date): array
    {
        $attributes = [
            'world_id' => $worldId,
            'name' => 'The Great Mortality in Lys',
        ];

        if (Schema::hasColumn('plague_waves', 'key')) {
            $attributes['key'] = 'great-mortality-lys';
        }
        if (Schema::hasColumn('plague_waves', 'status')) {
            $attributes['status'] = 'active';
        }
        if (Schema::hasColumn('plague_waves', 'started_date')) {
            $attributes['started_date'] = $date;
        }
        if (Schema::hasColumn('plague_waves', 'strain_key')) {
            $attributes['strain_key'] = 'great-mortality-lys';
            $attributes['infectiousness'] = 40;
            $attributes['mortality'] = 35;
            $attributes['incubation_ticks'] = 2;
            $attributes['infectious_ticks'] = 4;
        }
        if (Schema::hasColumn('plague_waves', 'started_on')) {
            $attributes['started_on'] = $date;
        }

        return $attributes;
    }

    private function makeCharacter(World $world, ?Dynasty $dynasty, ?DynastyHouse $house, Faith $faith, array $data): Character
    {
        return Character::query()->create([
            'world_id' => $world->id,
            'key' => $data['key'],
            'dynasty_id' => $dynasty?->id,
            'house_id' => $house?->id,
            'first_name' => $data['first_name'],
            'epithet' => $data['epithet'] ?? null,
            'sex' => $data['sex'],
            'birth_date' => $data['birth_date'],
            'is_alive' => true,
            'culture' => $data['culture'] ?? null,
            'faith_id' => $faith->id,
            'legitimacy_status' => 'legitimate',
        ]);
    }

    private function grantTitle(World $world, Title $title, Character $holder, string $date): void
    {
        TitleOwnership::query()->create([
            'world_id' => $world->id,
            'title_id' => $title->id,
            'holder_character_id' => $holder->id,
            'acquired_date' => $date,
            'acquisition_type' => 'grant',
            'is_current' => 1,
        ]);
    }
}
