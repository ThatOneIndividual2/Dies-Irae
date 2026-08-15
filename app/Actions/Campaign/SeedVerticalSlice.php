<?php

namespace App\Actions\Campaign;

use App\Actions\Control\AssignCharacterControl;
use App\Actions\Hell\EnsureNamedInfernalFixtures;
use App\Actions\Hell\SetApocalypseStage;
use App\Actions\Realms\AddVassal;
use App\Actions\Titles\CreateTitle;
use App\Actions\Titles\TitleOwnershipMutator;
use App\Actions\World\CreateWorld;
use App\Domain\Campaign\BeatKey;
use App\Domain\Campaign\CampaignCatalog;
use App\Domain\Campaign\SliceContext;
use App\Domain\Enums\AcquisitionType;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\GameEventStatus;
use App\Domain\Enums\HoldingType;
use App\Domain\Enums\OverlayState;
use App\Domain\Enums\SpiritualOfficeKey;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Domain\Support\Transactional;
use App\Models\Army;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\ChurchRelation;
use App\Models\ClergyStatus;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\Dynasty;
use App\Models\DynastyHouse;
use App\Models\Faith;
use App\Models\GameEvent;
use App\Models\Heresy;
use App\Models\Holding;
use App\Models\Monastery;
use App\Models\Papacy;
use App\Models\Realm;
use App\Models\Region;
use App\Models\ScheduledWorldEvent;
use App\Models\See;
use App\Models\SeeTerritory;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\User;
use App\Models\World;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

final class SeedVerticalSlice
{
    public function __construct(
        private CreateWorld $createWorld,
        private CreateTitle $createTitle,
        private TitleOwnershipMutator $titles,
        private AddVassal $addVassal,
        private AssignCharacterControl $assignControl,
        private SetApocalypseStage $apocalypse,
        private EnsureNamedInfernalFixtures $namedFixtures
    ) {
    }

    public function execute(?string $userEmail = null, ?string $password = null): SliceContext
    {
        return Transactional::run(function () use ($userEmail, $password) {
            $world = $this->createWorld->execute([
                'slug' => 'provence-1347',
                'name' => 'Provence, 1347',
                'start_date' => '1347-11-01',
                'simulation_seed' => 13471101,
            ]);
            $date = Carbon::parse($world->current_date ?? $world->game_date);

            $faith = Faith::query()->create([
                'world_id' => $world->id,
                'key' => 'latin_christianity',
                'name' => 'Latin Christianity',
                'rite' => 'roman',
                'is_primary' => true,
            ]);

            $region = Region::query()->create([
                'world_id' => $world->id,
                'key' => 'provence',
                'name' => 'Provence',
                'map_key' => 'provence',
            ]);

            $land = [
                'salon' => ['Salon-de-Provence', 4200, 80, ['x' => 180, 'y' => 90, 'w' => 110, 'h' => 70]],
                'pelissanne' => ['Pélissanne', 900, 30, ['x' => 310, 'y' => 90, 'w' => 90, 'h' => 60]],
                'aix' => ['Aix', 6100, 40, ['x' => 260, 'y' => 180, 'w' => 110, 'h' => 70]],
                'martigues' => ['Martigues', 2400, 20, ['x' => 40, 'y' => 90, 'w' => 110, 'h' => 70]],
                'st_michel' => ['Saint-Michel', 280, 5, ['x' => 160, 'y' => 180, 'w' => 80, 'h' => 60]],
                'miramas' => ['Miramas', 1600, 90, ['x' => 400, 'y' => 180, 'w' => 100, 'h' => 70]],
            ];

            $territories = collect();
            foreach ($land as $key => $row) {
                $levy = (int) floor($row[1] * (float) config('game.levy_ratio'));
                $territories[$key] = Territory::query()->create([
                    'world_id' => $world->id,
                    'region_id' => $region->id,
                    'key' => $key,
                    'name' => $row[0],
                    'terrain_type' => $key === 'martigues' ? 'coast' : 'plain',
                    'population' => $row[1],
                    'levy_available' => $levy,
                    'food_stores' => $row[2],
                    'map_box' => $row[3],
                    'ruin_state' => 'intact',
                    'supernatural_state' => 'ordinary',
                ]);
            }

            $edges = [
                ['salon', 'pelissanne'], ['salon', 'aix'], ['salon', 'st_michel'], ['salon', 'martigues'],
                ['aix', 'miramas'], ['aix', 'st_michel'],
            ];
            foreach ($edges as [$a, $b]) {
                foreach ([[$a, $b], [$b, $a]] as [$from, $to]) {
                    TerritoryAdjacency::query()->create([
                        'world_id' => $world->id,
                        'from_territory_id' => $territories[$from]->id,
                        'to_territory_id' => $territories[$to]->id,
                    ]);
                }
            }

            $this->holding($world, $territories['salon'], 'salon_castle', 'Castle of Salon', HoldingType::CASTLE, 40, 12);
            $this->holding($world, $territories['salon'], 'salon_town', 'Town of Salon', HoldingType::TOWN, 20, 18);
            $this->holding($world, $territories['pelissanne'], 'pelissanne_village', 'Village of Pélissanne', HoldingType::VILLAGE, 12, 6);
            $this->holding($world, $territories['aix'], 'aix_town', 'Town of Aix', HoldingType::TOWN, 30, 22);
            $this->holding($world, $territories['martigues'], 'martigues_port', 'Port of Martigues', HoldingType::TOWN, 16, 10);
            $abbeyHolding = $this->holding($world, $territories['st_michel'], 'st_michel_abbey', 'Abbey of Saint-Michel', HoldingType::MONASTIC, 4, 3);
            $this->holding($world, $territories['miramas'], 'miramas_castle', 'Castle of Miramas', HoldingType::CASTLE, 22, 8);

            $dynasty = Dynasty::query()->create([
                'world_id' => $world->id,
                'key' => 'adhemar',
                'name' => 'Adhémar',
                'motto' => 'Hold the road and the rite',
                'founded_date' => '1280-01-01',
            ]);
            $house = DynastyHouse::query()->create([
                'world_id' => $world->id,
                'dynasty_id' => $dynasty->id,
                'key' => 'adhemar_salon',
                'name' => 'Adhémar of Salon',
            ]);

            $ruler = $this->person($world, $faith, $territories['salon'], [
                'key' => 'raimond_adhemar',
                'first_name' => 'Raimond',
                'epithet' => 'Adhémar',
                'dynasty_id' => $dynasty->id,
                'house_id' => $house->id,
                'birth_date' => '1312-03-14',
                'martial' => 11,
                'diplomacy' => 10,
                'stewardship' => 12,
                'learning' => 8,
                'prestige' => 40,
                'treasury' => 80,
            ]);
            $dynasty->founder_character_id = $ruler->id;
            $dynasty->save();
            $house->head_character_id = $ruler->id;
            $house->save();

            $vassal = $this->person($world, $faith, $territories['pelissanne'], [
                'key' => 'gui_pelissanne',
                'first_name' => 'Gui',
                'epithet' => 'de Pélissanne',
                'birth_date' => '1318-06-02',
                'martial' => 9,
                'treasury' => 12,
            ]);
            $enemy = $this->person($world, $faith, $territories['miramas'], [
                'key' => 'foulques_miramas',
                'first_name' => 'Foulques',
                'epithet' => 'de Miramas',
                'birth_date' => '1309-11-20',
                'martial' => 13,
                'treasury' => 25,
            ]);
            $bishop = $this->person($world, $faith, $territories['aix'], [
                'key' => 'jacques_aix',
                'first_name' => 'Jacques',
                'epithet' => 'of Aix',
                'birth_date' => '1298-01-09',
                'learning' => 14,
                'treasury' => 30,
            ]);
            $abbot = $this->person($world, $faith, $territories['st_michel'], [
                'key' => 'etienne_stmichael',
                'first_name' => 'Étienne',
                'epithet' => 'of Saint-Michel',
                'birth_date' => '1301-04-22',
                'learning' => 13,
            ]);
            $priest = $this->person($world, $faith, $territories['salon'], [
                'key' => 'bertrand_salon',
                'first_name' => 'Bertrand',
                'epithet' => 'of Salon',
                'birth_date' => '1315-08-01',
                'learning' => 10,
            ]);
            $pope = $this->person($world, $faith, $territories['aix'], [
                'key' => 'clement_vi',
                'first_name' => 'Clement',
                'epithet' => 'VI',
                'birth_date' => '1291-05-23',
                'learning' => 15,
                'diplomacy' => 14,
            ]);

            $county = $this->createTitle->execute($world, 'County of Salon', TitleRank::COUNTY, [
                'key' => 'county_salon',
                'capital_territory_id' => $territories['salon']->id,
            ]);
            $barony = $this->createTitle->execute($world, 'Barony of Pélissanne', TitleRank::BARONY, [
                'key' => 'barony_pelissanne',
                'parent_title_id' => $county->id,
                'capital_territory_id' => $territories['pelissanne']->id,
            ]);
            $enemyTitle = $this->createTitle->execute($world, 'Barony of Miramas', TitleRank::BARONY, [
                'key' => 'barony_miramas',
                'capital_territory_id' => $territories['miramas']->id,
            ]);

            $this->titles->grant($county, $ruler, AcquisitionType::CREATION, $date);
            $this->titles->grant($barony, $vassal, AcquisitionType::CREATION, $date);
            $this->titles->grant($enemyTitle, $enemy, AcquisitionType::CREATION, $date);
            $this->addVassal->execute($ruler, $vassal, $date, 12, 25);

            $realm = Realm::query()->create([
                'world_id' => $world->id,
                'key' => 'realm_salon',
                'name' => 'County of Salon',
                'primary_title_id' => $county->id,
                'top_liege_character_id' => $ruler->id,
                'treasury' => 60,
            ]);

            $province = ChurchProvince::query()->create([
                'world_id' => $world->id,
                'faith_id' => $faith->id,
                'key' => 'provence_ecclesia',
                'name' => 'Province of Aix',
            ]);

            $see = See::query()->create([
                'world_id' => $world->id,
                'church_province_id' => $province->id,
                'territory_id' => $territories['aix']->id,
                'key' => 'diocese_aix',
                'name' => 'Diocese of Aix',
                'see_type' => 'diocese',
            ]);
            $papalSee = See::query()->create([
                'world_id' => $world->id,
                'church_province_id' => $province->id,
                'territory_id' => $territories['aix']->id,
                'key' => 'see_avignon',
                'name' => 'Avignon (papal see)',
                'see_type' => 'patriarchate',
            ]);
            foreach ($territories as $territory) {
                SeeTerritory::query()->create([
                    'world_id' => $world->id,
                    'see_id' => $see->id,
                    'territory_id' => $territory->id,
                ]);
            }

            $bishopOffice = $this->office($world, $see, $province, 'bishop_aix', 'Bishop of Aix', SpiritualOfficeKey::BISHOP);
            $abbotOffice = $this->office($world, $see, $province, 'abbot_st_michel', 'Abbot of Saint-Michel', SpiritualOfficeKey::ABBOT);
            $priestOffice = $this->office($world, $see, $province, 'parish_salon', 'Parish priest of Salon', SpiritualOfficeRank::PRIEST);
            $papalOffice = $this->office($world, $papalSee, $province, 'papacy', 'Bishop of Rome (Avignon)', SpiritualOfficeKey::POPE);

            $this->seatOffice($bishopOffice, $bishop, $date, SpiritualOfficeKey::BISHOP);
            $this->seatOffice($abbotOffice, $abbot, $date, SpiritualOfficeKey::ABBOT);
            $this->seatOffice($priestOffice, $priest, $date, SpiritualOfficeRank::PRIEST);
            $this->seatOffice($papalOffice, $pope, $date, SpiritualOfficeKey::POPE);

            $papacy = Papacy::query()->create([
                'world_id' => $world->id,
                'faith_id' => $faith->id,
                'papal_see_id' => $papalSee->id,
                'papal_office_id' => $papalOffice->id,
                'status' => 'occupied',
            ]);

            $monastery = Monastery::query()->create([
                'world_id' => $world->id,
                'holding_id' => $abbeyHolding->id,
                'faith_id' => $faith->id,
                'territory_id' => $territories['st_michel']->id,
                'see_id' => $see->id,
                'key' => 'abbey_st_michel',
                'name' => 'Abbey of Saint-Michel',
                'rule' => 'benedictine',
                'religious_population' => 48,
                'stores' => 24,
            ]);

            ChurchRelation::query()->create([
                'world_id' => $world->id,
                'character_id' => $ruler->id,
                'see_id' => $see->id,
                'standing' => 10,
            ]);

            $faction = DemonicFaction::query()->create([
                'world_id' => $world->id,
                'key' => 'open_grave',
                'name' => 'Court of the Open Grave',
                'agenda' => 'Thin the veil through plague-graves and stolen rites',
                'status' => 'dormant',
            ]);
            Heresy::query()->create([
                'world_id' => $world->id,
                'faith_id' => $faith->id,
                'key' => 'flagellants',
                'name' => 'Flagellant preaching',
            ]);
            Cult::query()->create([
                'world_id' => $world->id,
                'faction_id' => $faction->id,
                'territory_id' => $territories['aix']->id,
                'key' => 'brothers_open_grave',
                'name' => 'Brothers of the Open Grave',
                'revealed' => false,
                'strength' => 3,
            ]);

            foreach ($territories as $territory) {
                SupernaturalOverlay::query()->create([
                    'world_id' => $world->id,
                    'territory_id' => $territory->id,
                    'kind' => OverlayState::ORDINARY,
                    'changed_on' => $date->toDateString(),
                    'is_current' => true,
                ]);
            }

            $this->apocalypse->execute($world, ApocalypseStage::ORDINARY_ORDER, ['campaign_start']);
            $this->namedFixtures->execute($world);

            Army::query()->create([
                'world_id' => $world->id,
                'name' => 'Host of Miramas',
                'kind' => ArmyKind::HOSTILE,
                'commander_character_id' => $enemy->id,
                'owner_character_id' => $enemy->id,
                'territory_id' => $territories['miramas']->id,
                'strength' => 90,
                'status' => 'idle',
                'is_active' => true,
            ]);

            $email = $userEmail ?? config('game.demo_user.email');
            $user = User::query()->create([
                'name' => 'Raimond Adhémar',
                'email' => $email,
                'password' => Hash::make($password ?? config('game.demo_user.password')),
                'world_id' => $world->id,
            ]);
            $this->assignControl->execute($user, $ruler, $date);

            foreach (BeatKey::sequence() as $key) {
                $def = CampaignCatalog::definition($key);
                $due = Carbon::parse($world->start_date)->addDays(BeatKey::dayOffset($key))->toDateString();
                GameEvent::query()->create([
                    'world_id' => $world->id,
                    'event_key' => $key,
                    'title' => $def['title'],
                    'body' => $def['body'],
                    'status' => GameEventStatus::SCHEDULED,
                    'due_on' => $due,
                    'options' => $def['options'],
                    'payload' => [],
                ]);
                ScheduledWorldEvent::query()->create([
                    'world_id' => $world->id,
                    'process_at' => $due,
                    'event_type' => 'campaign_beat',
                    'status' => 'pending',
                    'payload' => ['event_key' => $key],
                    'idempotency_key' => 'beat:'.$key,
                ]);
            }

            return new SliceContext(
                $world->fresh(),
                $user->fresh(),
                $faith->fresh(),
                $dynasty->fresh(),
                $ruler->fresh(),
                $vassal->fresh(),
                $enemy->fresh(),
                $bishop->fresh(),
                $abbot->fresh(),
                $priest->fresh(),
                $pope->fresh(),
                $county->fresh(),
                $barony->fresh(),
                $realm->fresh(),
                $see->fresh(),
                $monastery->fresh(),
                $papacy->fresh(),
                $territories->map->fresh()
            );
        });
    }

    private function holding(World $world, Territory $territory, string $key, string $name, string $type, int $levy, int $tax): Holding
    {
        return Holding::query()->create([
            'world_id' => $world->id,
            'territory_id' => $territory->id,
            'key' => $key,
            'name' => $name,
            'holding_type' => $type,
            'base_levy' => $levy,
            'base_tax' => $tax,
        ]);
    }

    private function person(World $world, Faith $faith, Territory $home, array $attrs): Character
    {
        return Character::query()->create(array_merge([
            'world_id' => $world->id,
            'faith_id' => $faith->id,
            'residence_territory_id' => $home->id,
            'sex' => 'male',
            'is_alive' => true,
            'martial' => 8,
            'diplomacy' => 8,
            'stewardship' => 8,
            'learning' => 8,
            'prestige' => 5,
            'treasury' => 5,
        ], $attrs));
    }

    private function office(World $world, See $see, ChurchProvince $province, string $key, string $name, string $rank): SpiritualOffice
    {
        return SpiritualOffice::query()->create([
            'world_id' => $world->id,
            'key' => $key,
            'name' => $name,
            'rank' => $rank,
            'see_id' => $see->id,
            'church_province_id' => $province->id,
            'is_active' => true,
        ]);
    }

    private function seatOffice(SpiritualOffice $office, Character $holder, Carbon $date, string $status): void
    {
        SpiritualOfficeHoldership::query()->create([
            'world_id' => $office->world_id,
            'spiritual_office_id' => $office->id,
            'holder_character_id' => $holder->id,
            'acquired_date' => $date->toDateString(),
            'is_current' => true,
        ]);
        ClergyStatus::query()->create([
            'world_id' => $holder->world_id,
            'character_id' => $holder->id,
            'status' => $status,
            'started_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }
}
