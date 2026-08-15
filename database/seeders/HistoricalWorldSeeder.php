<?php

namespace Database\Seeders;

use App\Actions\World\CreateWorld;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\HoldingType;
use App\Domain\Enums\PapacyStatus;
use App\Domain\World\HistoricalPack;
use App\Models\ApocalypseState;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\ClergyStatus;
use App\Models\Dynasty;
use App\Models\DynastyHouse;
use App\Models\Faith;
use App\Models\Holding;
use App\Models\Monastery;
use App\Models\Papacy;
use App\Models\PilgrimageSite;
use App\Models\PlagueWave;
use App\Models\PoliticalRelation;
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
use App\Models\TradeCorridor;
use App\Models\VassalRelationship;
use App\Models\World;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HistoricalWorldSeeder extends Seeder
{
    public const WORLD_SLUG = HistoricalPack::SLUG;

    /** @var array<string, int> */
    private array $ids = [];

    /** @var array<string, array<int, string>> */
    private array $columns = [];

    public function run(): void
    {
        if (World::query()->where('slug', self::WORLD_SLUG)->exists()) {
            return;
        }

        $pack = HistoricalPack::load();

        DB::transaction(function () use ($pack) {
            $this->seed($pack);
        });
    }

    private function seed(array $pack): void
    {
        $world = app(CreateWorld::class)->execute($pack['world']);
        $date = $world->current_date->toDateString();
        $this->ids['world'] = (int) $world->id;

        foreach ($pack['world']['succession_laws'] as $law) {
            $row = SuccessionLaw::query()->firstOrCreate(
                ['key' => $law['key']],
                ['name' => $law['name'], 'description' => $law['description'] ?? null]
            );
            $this->ids['law:'.$law['key']] = (int) $row->id;
        }

        foreach ($pack['world']['faiths'] as $faith) {
            $row = $this->insert(Faith::class, [
                'world_id' => $world->id,
                'key' => $faith['key'],
                'name' => $faith['name'],
                'rite' => $faith['rite'] ?? null,
                'is_primary' => (bool) ($faith['primary'] ?? false),
            ]);
            $this->ids['faith:'.$faith['key']] = (int) $row->id;
        }

        $this->seedGeography($world, $pack['geography']);
        $this->seedPeople($world, $pack);
        $this->assignTerritoryOwners($pack['geography']['territories']);
        $this->seedTitles($world, $pack['titles'], $date);
        $this->seedRealms($world, $pack['realms']);
        $this->seedVassals($world, $pack['vassals'], $date);
        $this->seedChurch($world, $pack['church'], $date);
        $this->seedSacred($world, $pack['sacred']);
        $this->seedTrade($world, $pack['trade']);
        $this->seedRelations($world, $pack['relations'], $date);
        $this->seedPlague($world, $pack['plague']);
        $this->seedApocalypse($world, $date);
    }

    private function seedGeography(World $world, array $geo): void
    {
        foreach ($geo['regions'] as $region) {
            $row = $this->insert(Region::class, [
                'world_id' => $world->id,
                'key' => $region['key'],
                'name' => $region['name'],
                'map_key' => $region['map_key'] ?? $region['key'],
            ]);
            $this->ids['region:'.$region['key']] = (int) $row->id;
        }

        foreach ($geo['territories'] as $t) {
            $pop = (int) $t['population'];
            $agri = (int) ($t['agriculture'] ?? 0);
            $row = $this->insert(Territory::class, [
                'world_id' => $world->id,
                'region_id' => $this->ids['region:'.$t['region']],
                'key' => $t['key'],
                'name' => $t['name'],
                'territory_type' => $t['type'] ?? 'county',
                'terrain_type' => $t['terrain'] ?? 'plains',
                'population' => $pop,
                'development' => min(100, (int) floor($pop / 2000)),
                'control' => 100,
                'agricultural_capacity' => $agri,
                'levy_available' => (int) floor($pop * (float) config('game.levy_ratio', 0.08)),
                'food_stores' => $agri * 25,
                'ruin_state' => 'intact',
                'supernatural_state' => 'ordinary',
            ]);
            $this->ids['territory:'.$t['key']] = (int) $row->id;

            $this->insert(Holding::class, [
                'world_id' => $world->id,
                'territory_id' => $row->id,
                'key' => $t['key'].'-town',
                'name' => $t['name'],
                'holding_type' => HoldingType::TOWN,
                'development' => min(80, (int) floor($pop / 2500)),
                'fortification' => $pop >= 40000 ? 6 : 3,
                'base_tax' => max(4, (int) floor($pop / 4000)),
                'base_levy' => max(4, (int) floor($pop / 5000)),
            ]);
        }

        foreach ($geo['territories'] as $t) {
            $from = $this->ids['territory:'.$t['key']];
            foreach ($t['neighbors'] ?? [] as $neighbor) {
                if (! isset($this->ids['territory:'.$neighbor])) {
                    throw new \RuntimeException("Unknown neighbor {$neighbor} of {$t['key']}");
                }
                $to = $this->ids['territory:'.$neighbor];
                $this->addAdjacency((int) $world->id, $from, $to);
            }
        }
    }

    private function addAdjacency(int $worldId, int $from, int $to): void
    {
        if (! Schema::hasTable('territory_adjacencies') || $from === $to) {
            return;
        }

        foreach ([[$from, $to], [$to, $from]] as [$a, $b]) {
            DB::table('territory_adjacencies')->insertOrIgnore([
                'world_id' => $worldId,
                'from_territory_id' => $a,
                'to_territory_id' => $b,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPeople(World $world, array $pack): void
    {
        foreach ($pack['dynasties'] as $dyn) {
            $row = $this->insert(Dynasty::class, [
                'world_id' => $world->id,
                'key' => $dyn['key'],
                'name' => $dyn['name'],
                'motto' => $dyn['motto'] ?? null,
                'founded_date' => $dyn['founded'] ?? null,
            ]);
            $this->ids['dynasty:'.$dyn['key']] = (int) $row->id;

            $house = $this->insert(DynastyHouse::class, [
                'world_id' => $world->id,
                'dynasty_id' => $row->id,
                'key' => $dyn['key'].'-main',
                'name' => $dyn['name'],
            ]);
            $this->ids['house:'.$dyn['key']] = (int) $house->id;
        }

        foreach ($pack['characters'] as $ch) {
            $dynKey = $ch['dynasty'] ?? null;
            $row = $this->insert(Character::class, [
                'world_id' => $world->id,
                'key' => $ch['key'],
                'dynasty_id' => $dynKey ? ($this->ids['dynasty:'.$dynKey] ?? null) : null,
                'house_id' => $dynKey ? ($this->ids['house:'.$dynKey] ?? null) : null,
                'first_name' => $ch['first_name'],
                'epithet' => $ch['epithet'] ?? null,
                'sex' => $ch['sex'],
                'birth_date' => $ch['birth_date'],
                'is_alive' => true,
                'culture' => $ch['culture'] ?? null,
                'faith_id' => $this->ids['faith:'.$ch['faith']],
                'residence_territory_id' => isset($ch['residence']) ? $this->ids['territory:'.$ch['residence']] : null,
                'legitimacy_status' => 'legitimate',
            ]);
            $this->ids['character:'.$ch['key']] = (int) $row->id;
        }

        foreach ($pack['characters'] as $ch) {
            if (empty($ch['father'])) {
                continue;
            }
            Character::query()->whereKey($this->ids['character:'.$ch['key']])->update([
                'father_id' => $this->ids['character:'.$ch['father']],
            ]);
        }

        foreach ($pack['dynasties'] as $dyn) {
            $founder = collect($pack['characters'])->firstWhere('dynasty', $dyn['key']);
            if ($founder) {
                Dynasty::query()->whereKey($this->ids['dynasty:'.$dyn['key']])->update([
                    'founder_character_id' => $this->ids['character:'.$founder['key']],
                ]);
                DynastyHouse::query()->whereKey($this->ids['house:'.$dyn['key']])->update([
                    'head_character_id' => $this->ids['character:'.$founder['key']],
                ]);
            }
        }
    }

    private function assignTerritoryOwners(array $territories): void
    {
        foreach ($territories as $t) {
            $owner = $this->ids['character:'.$t['owner']];
            Territory::query()->whereKey($this->ids['territory:'.$t['key']])->update([
                'owner_character_id' => $owner,
                'controller_character_id' => $owner,
            ]);
            Holding::query()
                ->where('world_id', $this->ids['world'])
                ->where('key', $t['key'].'-town')
                ->update(['owner_character_id' => $owner]);
        }
    }

    private function seedTitles(World $world, array $titles, string $date): void
    {
        $pending = $titles;
        $guard = 0;
        while ($pending !== [] && $guard < 20) {
            $guard++;
            $next = [];
            foreach ($pending as $title) {
                $parentKey = $title['parent'] ?? null;
                $deJureKey = $title['de_jure'] ?? null;
                if ($parentKey && ! isset($this->ids['title:'.$parentKey])) {
                    $next[] = $title;
                    continue;
                }
                if ($deJureKey && ! isset($this->ids['title:'.$deJureKey])) {
                    $next[] = $title;
                    continue;
                }

                $lawKey = $title['law'] ?? 'agnatic_primogeniture';
                $row = $this->insert(Title::class, [
                    'world_id' => $world->id,
                    'key' => $title['key'],
                    'name' => $title['name'],
                    'adjective' => $title['adjective'] ?? null,
                    'rank' => $title['rank'],
                    'parent_title_id' => $parentKey ? $this->ids['title:'.$parentKey] : null,
                    'de_jure_liege_title_id' => $deJureKey ? $this->ids['title:'.$deJureKey] : null,
                    'capital_territory_id' => $this->ids['territory:'.$title['capital']],
                    'primary_territory_id' => $this->ids['territory:'.$title['capital']],
                    'succession_law_id' => $this->ids['law:'.$lawKey] ?? $this->ids['law:agnatic_primogeniture'],
                    'is_active' => true,
                    'is_titular' => false,
                ]);
                $this->ids['title:'.$title['key']] = (int) $row->id;

                $this->insert(TitleOwnership::class, [
                    'world_id' => $world->id,
                    'title_id' => $row->id,
                    'holder_character_id' => $this->ids['character:'.$title['holder']],
                    'acquired_date' => $date,
                    'acquisition_type' => 'historical',
                    'is_current' => 1,
                ]);

                $this->insert(Holding::class, [
                    'world_id' => $world->id,
                    'territory_id' => $this->ids['territory:'.$title['capital']],
                    'key' => $title['key'].'-seat',
                    'name' => $title['name'].' seat',
                    'holding_type' => HoldingType::CASTLE,
                    'fortification' => 8,
                    'base_tax' => 8,
                    'base_levy' => 12,
                    'owner_character_id' => $this->ids['character:'.$title['holder']],
                ]);
            }
            $pending = $next;
        }

        if ($pending !== []) {
            $keys = implode(', ', array_column($pending, 'key'));
            throw new \RuntimeException("Unresolvable title parents: {$keys}");
        }
    }

    private function seedRealms(World $world, array $realms): void
    {
        foreach ($realms as $realm) {
            $row = $this->insert(Realm::class, [
                'world_id' => $world->id,
                'key' => $realm['key'],
                'name' => $realm['name'],
                'top_liege_character_id' => $this->ids['character:'.$realm['liege']],
                'primary_title_id' => $this->ids['title:'.$realm['title']],
                'realm_authority' => $realm['authority'] ?? 50,
            ]);
            $this->ids['realm:'.$realm['key']] = (int) $row->id;
        }
    }

    private function seedVassals(World $world, array $vassals, string $date): void
    {
        foreach ($vassals as $v) {
            if (! empty($v['skip'])) {
                continue;
            }
            $this->insert(VassalRelationship::class, [
                'world_id' => $world->id,
                'realm_id' => $this->ids['realm:'.$v['realm']],
                'liege_character_id' => $this->ids['character:'.$v['liege']],
                'vassal_character_id' => $this->ids['character:'.$v['vassal']],
                'primary_title_id' => $this->ids['title:'.$v['title']],
                'started_date' => $date,
                'is_current' => 1,
            ]);
        }
    }

    private function seedChurch(World $world, array $church, string $date): void
    {
        foreach ($church['provinces'] as $p) {
            $row = $this->insert(ChurchProvince::class, [
                'world_id' => $world->id,
                'faith_id' => $this->ids['faith:'.($p['faith'] ?? 'latin-rite')],
                'key' => $p['key'],
                'name' => $p['name'],
            ]);
            $this->ids['province:'.$p['key']] = (int) $row->id;
        }

        foreach ($church['sees'] as $see) {
            $row = $this->insert(See::class, [
                'world_id' => $world->id,
                'church_province_id' => $this->ids['province:'.$see['province']],
                'territory_id' => $this->ids['territory:'.$see['territory']],
                'key' => $see['key'],
                'name' => $see['name'],
                'see_type' => $see['type'] ?? 'diocese',
            ]);
            $this->ids['see:'.$see['key']] = (int) $row->id;
        }

        foreach ($church['sees'] as $see) {
            if (! empty($see['parent'])) {
                See::query()->whereKey($this->ids['see:'.$see['key']])->update([
                    'parent_see_id' => $this->ids['see:'.$see['parent']],
                ]);
            }
        }

        foreach ($church['provinces'] as $p) {
            if (! empty($p['metropolitan']) && Schema::hasColumn('church_provinces', 'metropolitan_see_id')) {
                ChurchProvince::query()->whereKey($this->ids['province:'.$p['key']])->update([
                    'metropolitan_see_id' => $this->ids['see:'.$p['metropolitan']],
                ]);
            }
        }

        foreach ($church['sees'] as $see) {
            $officeKey = 'office-'.$see['key'];
            $office = $this->insert(SpiritualOffice::class, [
                'world_id' => $world->id,
                'key' => $officeKey,
                'name' => $see['name'].' ordinary',
                'rank' => $see['rank'],
                'see_id' => $this->ids['see:'.$see['key']],
                'church_province_id' => $this->ids['province:'.$see['province']],
                'is_active' => true,
            ]);
            $this->ids['office:'.$officeKey] = (int) $office->id;

            if (Schema::hasColumn('sees', 'ordinary_office_id')) {
                See::query()->whereKey($this->ids['see:'.$see['key']])->update([
                    'ordinary_office_id' => $office->id,
                ]);
            }

            if (empty($see['holder'])) {
                continue;
            }

            $this->insert(SpiritualOfficeHoldership::class, [
                'world_id' => $world->id,
                'spiritual_office_id' => $office->id,
                'holder_character_id' => $this->ids['character:'.$see['holder']],
                'acquired_date' => $date,
                'is_current' => 1,
            ]);

            $this->insert(ClergyStatus::class, [
                'world_id' => $world->id,
                'character_id' => $this->ids['character:'.$see['holder']],
                'status' => $see['rank'],
                'started_date' => $date,
                'is_current' => 1,
            ]);
        }

        $papal = $church['papacy'];
        $office = SpiritualOffice::query()->where('world_id', $world->id)->where('key', 'office-'.$papal['see'])->first();
        if ($office === null) {
            throw new \RuntimeException('Papal office missing for see '.$papal['see']);
        }
        $this->insert(Papacy::class, [
            'world_id' => $world->id,
            'faith_id' => $this->ids['faith:latin-rite'],
            'papal_see_id' => $this->ids['see:'.$papal['see']],
            'papal_office_id' => $office->id,
            'status' => $papal['status'] ?? PapacyStatus::OCCUPIED,
        ]);
    }

    private function seedSacred(World $world, array $sacred): void
    {
        foreach ($sacred['monasteries'] as $m) {
            $holding = $this->insert(Holding::class, [
                'world_id' => $world->id,
                'territory_id' => $this->ids['territory:'.$m['territory']],
                'key' => $m['key'].'-holding',
                'name' => $m['name'],
                'holding_type' => HoldingType::MONASTERY,
                'base_tax' => 3,
                'base_levy' => 1,
            ]);
            $faithKey = (($m['rule'] ?? '') === 'basilian') ? 'greek-rite' : 'latin-rite';
            $this->insert(Monastery::class, [
                'world_id' => $world->id,
                'holding_id' => $holding->id,
                'territory_id' => $this->ids['territory:'.$m['territory']],
                'faith_id' => $this->ids['faith:'.$faithKey] ?? $this->ids['faith:latin-rite'],
                'key' => $m['key'],
                'name' => $m['name'],
                'rule' => $m['rule'] ?? 'benedictine',
                'religious_population' => $m['population'] ?? 20,
            ]);
        }

        foreach ($sacred['pilgrimage_sites'] as $p) {
            $this->insert(PilgrimageSite::class, [
                'world_id' => $world->id,
                'key' => $p['key'],
                'name' => $p['name'],
                'territory_id' => $this->ids['territory:'.$p['territory']],
                'dedication' => $p['dedication'] ?? null,
                'importance' => $p['importance'] ?? 50,
            ]);
        }
    }

    private function seedTrade(World $world, array $trade): void
    {
        foreach ($trade['corridors'] as $c) {
            $stops = [];
            foreach ($c['stops'] as $key) {
                $stops[] = $this->ids['territory:'.$key];
            }
            $this->insert(TradeCorridor::class, [
                'world_id' => $world->id,
                'key' => $c['key'],
                'name' => $c['name'],
                'good' => $c['good'] ?? null,
                'stop_territory_ids' => $stops,
            ]);
        }
    }

    private function seedRelations(World $world, array $relations, string $date): void
    {
        foreach ($relations as $rel) {
            if (! empty($rel['skip'])) {
                continue;
            }
            if (($rel['since'] ?? $date) > $date) {
                continue;
            }
            $this->insert(PoliticalRelation::class, [
                'world_id' => $world->id,
                'from_realm_id' => $this->ids['realm:'.$rel['from_realm']],
                'to_realm_id' => $this->ids['realm:'.$rel['to_realm']],
                'type' => $rel['type'],
                'name' => $rel['name'],
                'started_date' => $rel['since'],
                'note' => $rel['note'] ?? null,
            ]);
        }
    }

    private function seedPlague(World $world, array $plague): void
    {
        $wave = $plague['wave'];
        $row = $this->insert(PlagueWave::class, [
            'world_id' => $world->id,
            'key' => $wave['key'],
            'name' => $wave['name'],
            'status' => $wave['status'] ?? 'active',
            'started_date' => $wave['started_date'],
            'started_on' => $wave['started_date'],
            'strain_key' => $wave['strain_key'] ?? 'great-mortality',
            'strain' => $wave['strain_key'] ?? null,
            'infectiousness' => $wave['infectiousness'] ?? 50,
            'mortality' => $wave['mortality'] ?? 40,
            'incubation_ticks' => 2,
            'infectious_ticks' => 5,
            'supernatural' => false,
        ]);

        foreach ($plague['infections'] as $inf) {
            $this->insert(TerritoryPlagueState::class, [
                'world_id' => $world->id,
                'plague_wave_id' => $row->id,
                'territory_id' => $this->ids['territory:'.$inf['territory']],
                'intensity' => $inf['intensity'],
                'arrived_date' => $inf['arrived'],
                'arrived_on' => $inf['arrived'],
                'is_active' => true,
            ]);
        }
    }

    private function seedApocalypse(World $world, string $date): void
    {
        $state = ApocalypseState::query()->firstOrNew(['world_id' => $world->id]);
        if (Schema::hasColumn('apocalypse_states', 'stage')) {
            $state->stage = ApocalypseStage::GREAT_MORTALITY;
        }
        if (Schema::hasColumn('apocalypse_states', 'phase_key')) {
            $state->phase_key = ApocalypseStage::GREAT_MORTALITY;
        }
        if (Schema::hasColumn('apocalypse_states', 'signs')) {
            $state->signs = ['plague_at_caffa', 'plague_at_messina', 'plague_at_constantinople'];
        }
        if (Schema::hasColumn('apocalypse_states', 'stage_entered_date')) {
            $state->stage_entered_date = $date;
        }
        $state->save();
    }

    private function insert(string $model, array $attributes): object
    {
        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = new $model();
        $table = $instance->getTable();
        if (! isset($this->columns[$table])) {
            $this->columns[$table] = Schema::getColumnListing($table);
        }
        $filtered = [];
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->columns[$table], true)) {
                $filtered[$key] = $value;
            }
        }

        return $model::query()->create($filtered);
    }
}
