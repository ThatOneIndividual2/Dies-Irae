<?php

namespace App\Actions\Campaign;

use App\Actions\Control\AssignCharacterControl;
use App\Actions\Hell\SetApocalypseStage;
use App\Actions\Realms\AddVassal;
use App\Actions\Time\ScheduleWorldEvent;
use App\Actions\Titles\CreateTitle;
use App\Actions\Titles\TitleOwnershipMutator;
use App\Actions\World\CreateWorld;
use App\Domain\Campaign\CampaignPack;
use App\Domain\Campaign\Europa1347Context;
use App\Domain\Campaign\PlayerArchetype;
use App\Domain\Enums\AcquisitionType;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\HoldingType;
use App\Domain\Enums\OverlayState;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\SeeKind;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Support\Transactional;
use App\Models\Army;
use App\Models\CampaignGoalProgress;
use App\Models\CampaignState;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\ChurchRelation;
use App\Models\ClergyStatus;
use App\Models\CorruptionState;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\DemonicThreat;
use App\Models\Dynasty;
use App\Models\DynastyHouse;
use App\Models\Faith;
use App\Models\HarvestState;
use App\Models\Heresy;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\HolyOrderHouse;
use App\Models\HolyOrderMembership;
use App\Models\HolyOrderRecognition;
use App\Models\LocalWar;
use App\Models\Monastery;
use App\Models\Papacy;
use App\Models\PlagueWave;
use App\Models\Realm;
use App\Models\Region;
use App\Models\ReligiousOrder;
use App\Models\See;
use App\Models\SeeTerritory;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\TitleClaim;
use App\Models\TradeRoute;
use App\Models\User;
use App\Models\World;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

final class SeedEuropa1347
{
    public function __construct(
        private CreateWorld $createWorld,
        private CreateTitle $createTitle,
        private TitleOwnershipMutator $titles,
        private AddVassal $addVassal,
        private AssignCharacterControl $assignControl,
        private SetApocalypseStage $apocalypse,
        private ScheduleWorldEvent $schedule
    ) {
    }

    public function execute(string $archetype = PlayerArchetype::KING, ?string $email = null, ?string $password = null, ?int $seed = null, ?string $slug = null): Europa1347Context
    {
        PlayerArchetype::assertValid($archetype);
        $pack = CampaignPack::europa1347();
        $cfg = config('campaign.1347');

        return Transactional::run(function () use ($archetype, $email, $password, $seed, $slug, $pack, $cfg) {
            $world = $this->createWorld->execute([
                'slug' => $slug ?? $cfg['world_slug'],
                'name' => $cfg['world_name'],
                'start_date' => $cfg['start_date'],
                'simulation_seed' => (string) ($seed ?? $cfg['simulation_seed']),
            ]);
            $date = Carbon::parse($world->current_date);
            $geo = $pack->get('geography');
            $pol = $pack->get('polities');
            $church = $pack->get('church');
            $cat = $pack->get('catastrophe');
            $goals = $pack->get('goals');
            $archetypes = $pack->get('archetypes');

            $faith = Faith::query()->create([
                'world_id' => $world->id,
                'key' => $church['faith']['key'],
                'name' => $church['faith']['name'],
                'rite' => $church['faith']['rite'],
                'is_primary' => true,
            ]);

            $regions = collect();
            foreach ($geo['regions'] as $row) {
                $regions[$row['key']] = Region::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'map_key' => $row['key'],
                ]);
            }

            $territories = collect();
            foreach ($geo['territories'] as $row) {
                $levy = (int) floor($row['population'] * (float) config('game.levy_ratio'));
                $territories[$row['key']] = Territory::query()->create([
                    'world_id' => $world->id,
                    'region_id' => $regions[$row['region']]->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'terrain_type' => $row['terrain'],
                    'population' => $row['population'],
                    'levy_available' => $levy,
                    'food_stores' => $row['food'],
                    'map_box' => $row['map'],
                    'ruin_state' => 'intact',
                    'supernatural_state' => 'ordinary',
                ]);
            }

            foreach ($geo['adjacencies'] as [$a, $b]) {
                foreach ([[$a, $b], [$b, $a]] as [$from, $to]) {
                    TerritoryAdjacency::query()->create([
                        'world_id' => $world->id,
                        'from_territory_id' => $territories[$from]->id,
                        'to_territory_id' => $territories[$to]->id,
                    ]);
                }
            }

            $holdings = collect();
            foreach ($geo['holdings'] as $row) {
                $type = $row['type'] === 'monastery' ? HoldingType::MONASTERY : $row['type'];
                $holdings[$row['key']] = Holding::query()->create([
                    'world_id' => $world->id,
                    'territory_id' => $territories[$row['territory']]->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'holding_type' => $type,
                    'base_levy' => $row['levy'],
                    'base_tax' => $row['tax'],
                ]);
            }

            $dynasties = collect();
            $houses = collect();
            foreach ($pol['dynasties'] as $row) {
                $dynasties[$row['key']] = Dynasty::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'motto' => $row['motto'] ?? null,
                    'founded_date' => $row['founded'] ?? '1200-01-01',
                ]);
                $houses[$row['key']] = DynastyHouse::query()->create([
                    'world_id' => $world->id,
                    'dynasty_id' => $dynasties[$row['key']]->id,
                    'key' => $row['key'].'_main',
                    'name' => $row['name'],
                ]);
            }

            $characters = collect();
            foreach ($pol['characters'] as $row) {
                $dynastyId = isset($row['dynasty']) ? $dynasties[$row['dynasty']]->id : null;
                $houseId = isset($row['dynasty']) ? $houses[$row['dynasty']]->id : null;
                $characters[$row['key']] = Character::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'first_name' => $row['first_name'],
                    'epithet' => $row['epithet'] ?? null,
                    'sex' => $row['sex'],
                    'birth_date' => $row['birth'],
                    'is_alive' => true,
                    'dynasty_id' => $dynastyId,
                    'house_id' => $houseId,
                    'faith_id' => $faith->id,
                    'residence_territory_id' => $territories[$row['home']]->id,
                    'culture' => $row['culture'] ?? null,
                    'martial' => $row['martial'] ?? 8,
                    'diplomacy' => $row['diplomacy'] ?? 8,
                    'stewardship' => $row['stewardship'] ?? 8,
                    'learning' => $row['learning'] ?? 8,
                    'prestige' => $row['prestige'] ?? 5,
                    'treasury' => $row['treasury'] ?? 5,
                ]);
            }
            foreach ($pol['characters'] as $row) {
                if (!empty($row['father']) && isset($characters[$row['father']])) {
                    $characters[$row['key']]->father_id = $characters[$row['father']]->id;
                    $characters[$row['key']]->save();
                }
            }
            foreach ($dynasties as $key => $dynasty) {
                $founder = $characters->first(fn (Character $c) => (int) $c->dynasty_id === (int) $dynasty->id);
                if ($founder) {
                    $dynasty->founder_character_id = $founder->id;
                    $dynasty->save();
                    $houses[$key]->head_character_id = $founder->id;
                    $houses[$key]->save();
                }
            }

            $titles = collect();
            foreach ($pol['titles'] as $row) {
                $titles[$row['key']] = $this->createTitle->execute($world, $row['name'], $row['rank'], [
                    'key' => $row['key'],
                    'adjective' => $row['adjective'] ?? null,
                    'capital_territory_id' => $territories[$row['capital']]->id,
                    'parent_title_id' => isset($row['parent']) && isset($titles[$row['parent']]) ? $titles[$row['parent']]->id : null,
                    'is_titular' => (bool) ($row['titular'] ?? false),
                ]);
                $this->titles->grant($titles[$row['key']], $characters[$row['holder']], AcquisitionType::CREATION, $date);
            }

            foreach ($pol['vassals'] as $bond) {
                $this->addVassal->execute($characters[$bond['liege']], $characters[$bond['vassal']], $date, $bond['tax'], $bond['levy']);
            }

            $realms = collect();
            foreach ($pol['realms'] as $row) {
                $realms[$row['key']] = Realm::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'primary_title_id' => $titles[$row['title']]->id,
                    'top_liege_character_id' => $characters[$row['liege']]->id,
                    'treasury' => $row['treasury'] ?? 0,
                ]);
            }

            foreach ($pol['territory_owners'] as $tKey => $cKey) {
                $territories[$tKey]->owner_character_id = $characters[$cKey]->id;
                $territories[$tKey]->controller_character_id = $characters[$cKey]->id;
                $territories[$tKey]->save();
            }

            foreach ($pol['claims'] as $claim) {
                TitleClaim::query()->create([
                    'world_id' => $world->id,
                    'title_id' => $titles[$claim['title']]->id,
                    'claimant_character_id' => $characters[$claim['claimant']]->id,
                    'claim_type' => $claim['type'],
                    'strength' => $claim['strength'],
                    'is_active' => true,
                    'created_date' => $date->toDateString(),
                ]);
            }

            $orders = collect();
            foreach ($church['religious_orders'] as $row) {
                $orders[$row['key']] = ReligiousOrder::query()->create([
                    'world_id' => $world->id,
                    'faith_id' => $faith->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'kind' => $row['key'] === 'hospitallers' ? 'military' : 'monastic',
                    'rule_key' => $row['rule'],
                    'sanction_status' => 'sanctioned',
                    'sanctioned_date' => '1099-07-15',
                ]);
            }

            $provinces = collect();
            foreach ($church['provinces'] as $row) {
                $provinces[$row['key']] = ChurchProvince::query()->create([
                    'world_id' => $world->id,
                    'faith_id' => $faith->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                ]);
            }

            $sees = collect();
            foreach ($church['sees'] as $row) {
                $sees[$row['key']] = See::query()->create([
                    'world_id' => $world->id,
                    'church_province_id' => $provinces[$row['province']]->id,
                    'parent_see_id' => isset($row['parent']) && isset($sees[$row['parent']]) ? $sees[$row['parent']]->id : null,
                    'territory_id' => $territories[$row['territory']]->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'see_type' => $row['type'],
                    'is_active' => true,
                ]);
            }
            foreach ($church['provinces'] as $row) {
                if (!empty($row['metropolitan']) && isset($sees[$row['metropolitan']])) {
                    $provinces[$row['key']]->metropolitan_see_id = $sees[$row['metropolitan']]->id;
                    $provinces[$row['key']]->save();
                }
            }
            foreach ($church['see_territories'] as $seeKey => $tKeys) {
                foreach ($tKeys as $tKey) {
                    SeeTerritory::query()->create([
                        'world_id' => $world->id,
                        'see_id' => $sees[$seeKey]->id,
                        'territory_id' => $territories[$tKey]->id,
                    ]);
                }
            }

            $holy = $church['holy_orders'][0];
            $hospitallers = HolyOrder::query()->create([
                'world_id' => $world->id,
                'faith_id' => $faith->id,
                'religious_order_id' => $orders['hospitallers']->id,
                'key' => $holy['key'],
                'name' => $holy['name'],
                'sanction_status' => $holy['sanction_status'],
                'papal_protection' => $holy['papal_protection'],
                'status' => $holy['status'],
                'legitimacy' => $holy['legitimacy'],
                'papal_recognition_status' => $holy['papal_recognition_status'],
                'headquarters_holding_id' => $holdings[$holy['headquarters']]->id,
                'treasury' => $holy['treasury'],
                'manpower_cap' => $holy['manpower_cap'],
                'manpower_current' => $holy['manpower_current'],
                'founded_date' => $holy['founded_date'],
            ]);

            $offices = collect();
            foreach ($church['offices'] as $row) {
                $offices[$row['key']] = SpiritualOffice::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'rank' => $row['rank'],
                    'see_id' => isset($row['see']) ? $sees[$row['see']]->id : null,
                    'church_province_id' => isset($row['see']) ? $sees[$row['see']]->church_province_id : null,
                    'holy_order_id' => ($row['holy_order'] ?? null) === 'knights_hospitaller' ? $hospitallers->id : null,
                    'is_active' => true,
                    'is_papal_apex' => (bool) ($row['papal_apex'] ?? false),
                ]);
                if (!empty($row['see'])) {
                    $sees[$row['see']]->ordinary_office_id = $offices[$row['key']]->id;
                    $sees[$row['see']]->save();
                }
                SpiritualOfficeHoldership::query()->create([
                    'world_id' => $world->id,
                    'spiritual_office_id' => $offices[$row['key']]->id,
                    'holder_character_id' => $characters[$row['holder']]->id,
                    'acquired_date' => $date->toDateString(),
                    'is_current' => true,
                    'acquisition_type' => 'appointment',
                    'legitimacy' => 'recognized',
                ]);
                $status = $row['rank'] === SpiritualOfficeRank::POPE ? SpiritualOfficeRank::POPE : $row['rank'];
                ClergyStatus::query()->create([
                    'world_id' => $world->id,
                    'character_id' => $characters[$row['holder']]->id,
                    'status' => $status,
                    'started_date' => $date->toDateString(),
                    'is_current' => true,
                ]);
            }
            $hospitallers->grand_master_office_id = $offices['grand_master_hospital']->id;
            $hospitallers->save();

            $papacy = Papacy::query()->create([
                'world_id' => $world->id,
                'faith_id' => $faith->id,
                'papal_see_id' => $sees['see_avignon']->id,
                'papal_office_id' => $offices['papacy']->id,
                'status' => PapacyStatus::OCCUPIED,
            ]);

            $house = HolyOrderHouse::query()->create([
                'world_id' => $world->id,
                'holy_order_id' => $hospitallers->id,
                'holding_id' => $holdings[$holy['headquarters']]->id,
                'territory_id' => $territories[$holy['territory']]->id,
                'commander_character_id' => $characters[$holy['master']]->id,
                'name' => 'Convent of Rhodes',
                'house_type' => 'headquarters',
                'is_headquarters' => true,
                'is_active' => true,
                'founded_date' => $holy['founded_date'],
            ]);
            HolyOrderMembership::query()->create([
                'world_id' => $world->id,
                'holy_order_id' => $hospitallers->id,
                'character_id' => $characters[$holy['master']]->id,
                'house_id' => $house->id,
                'member_rank' => 'grand_master',
                'recruited_date' => '1320-01-01',
                'is_current' => true,
            ]);
            HolyOrderRecognition::query()->create([
                'world_id' => $world->id,
                'holy_order_id' => $hospitallers->id,
                'granted_by_character_id' => $characters['clement_vi']->id,
                'status' => 'recognized',
                'bull_key' => 'hospital_confirmation',
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            foreach ($church['monasteries'] as $row) {
                Monastery::query()->create([
                    'world_id' => $world->id,
                    'holding_id' => $holdings[$row['holding']]->id,
                    'faith_id' => $faith->id,
                    'territory_id' => $territories[$row['territory']]->id,
                    'see_id' => $sees[$row['see']]->id,
                    'religious_order_id' => $orders['benedictines']->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'rule' => $row['rule'],
                    'religious_population' => $row['population'],
                    'stores' => $row['stores'],
                ]);
            }

            foreach ($characters as $character) {
                ChurchRelation::query()->create([
                    'world_id' => $world->id,
                    'character_id' => $character->id,
                    'see_id' => $sees['see_avignon']->id,
                    'standing' => 8,
                ]);
            }

            foreach ($territories as $territory) {
                SupernaturalOverlay::query()->create([
                    'world_id' => $world->id,
                    'territory_id' => $territory->id,
                    'kind' => OverlayState::ORDINARY,
                    'changed_on' => $date->toDateString(),
                    'is_current' => true,
                ]);
                HarvestState::query()->create([
                    'world_id' => $world->id,
                    'territory_id' => $territory->id,
                    'harvest_year' => 1347,
                    'status' => 'sound',
                    'yield_index' => 72,
                    'recorded_on' => $date->toDateString(),
                ]);
            }

            foreach ($cat['trade_routes'] as $row) {
                TradeRoute::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'from_territory_id' => $territories[$row['from']]->id,
                    'to_territory_id' => $territories[$row['to']]->id,
                    'goods' => $row['goods'],
                    'volume' => $row['volume'],
                    'is_active' => true,
                ]);
            }

            $wave = PlagueWave::query()->create([
                'world_id' => $world->id,
                'key' => $cat['plague']['wave_key'],
                'name' => $cat['plague']['name'],
                'strain' => $cat['plague']['strain'],
                'started_date' => $date->toDateString(),
                'status' => 'active',
            ]);
            TerritoryPlagueState::query()->create([
                'world_id' => $world->id,
                'plague_wave_id' => $wave->id,
                'territory_id' => $territories[$cat['plague']['entry']]->id,
                'intensity' => $cat['plague']['starting_intensity'],
                'arrived_date' => $date->toDateString(),
                'is_active' => true,
            ]);

            $faction = DemonicFaction::query()->create([
                'world_id' => $world->id,
                'key' => $cat['factions'][0]['key'],
                'name' => $cat['factions'][0]['name'],
                'agenda' => $cat['factions'][0]['agenda'],
                'status' => $cat['factions'][0]['status'],
            ]);
            foreach ($cat['cults'] as $row) {
                Cult::query()->create([
                    'world_id' => $world->id,
                    'faction_id' => $faction->id,
                    'territory_id' => $territories[$row['territory']]->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'revealed' => $row['revealed'],
                    'strength' => $row['strength'],
                    'activity' => $row['activity'],
                ]);
            }
            foreach ($cat['threats'] as $row) {
                DemonicThreat::query()->create([
                    'world_id' => $world->id,
                    'demonic_faction_id' => $faction->id,
                    'territory_id' => $territories[$row['territory']]->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'intensity' => $row['intensity'],
                ]);
            }
            foreach ($cat['corruption'] as $row) {
                CorruptionState::query()->create([
                    'world_id' => $world->id,
                    'subject_type' => 'territory',
                    'subject_id' => $territories[$row['territory']]->id,
                    'intensity' => $row['intensity'],
                    'source' => $row['source'],
                    'started_date' => $date->toDateString(),
                    'recorded_on' => $date->toDateString(),
                ]);
            }
            foreach ($cat['heresies'] as $row) {
                Heresy::query()->create([
                    'world_id' => $world->id,
                    'faith_id' => $faith->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                ]);
            }

            foreach ($cat['local_wars'] as $row) {
                LocalWar::query()->create([
                    'world_id' => $world->id,
                    'key' => $row['key'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'attacker_realm_id' => $realms[$row['attacker_realm']]->id,
                    'defender_realm_id' => $realms[$row['defender_realm']]->id,
                    'theater_territory_id' => $territories[$row['theater']]->id,
                    'started_date' => '1337-05-24',
                ]);
            }
            foreach ($cat['armies'] as $row) {
                Army::query()->create([
                    'world_id' => $world->id,
                    'name' => $row['name'],
                    'kind' => $row['kind'] === 'hostile' ? ArmyKind::HOSTILE : ArmyKind::LEVY,
                    'commander_character_id' => $characters[$row['owner']]->id,
                    'owner_character_id' => $characters[$row['owner']]->id,
                    'territory_id' => $territories[$row['territory']]->id,
                    'strength' => $row['strength'],
                    'status' => 'idle',
                    'is_active' => true,
                ]);
            }

            $this->apocalypse->execute($world, ApocalypseStage::ORDINARY_ORDER, ['campaign_start']);

            $playerKey = $archetypes[$archetype]['character'];
            $player = $characters[$playerKey];
            $demo = config('campaign.demo_users.'.$archetype);
            $user = User::query()->create([
                'name' => $player->displayName(),
                'email' => $email ?? $demo['email'],
                'password' => Hash::make($password ?? $demo['password']),
                'world_id' => $world->id,
            ]);
            $this->assignControl->execute($user, $player, $date);

            $openingUntil = $date->copy()->addMonths((int) $cfg['opening_max_months']);
            $campaign = CampaignState::query()->create([
                'world_id' => $world->id,
                'scenario_key' => 'europa-1347',
                'player_archetype' => $archetype,
                'player_character_id' => $player->id,
                'opening_until' => $openingUntil->toDateString(),
                'pulse_count' => 0,
                'flags' => ['mass_death_fired' => false, 'manifestation_fired' => false],
                'fired_families' => [],
                'cooldowns' => [],
                'revealed_mechanics' => [],
            ]);

            foreach ($goals['goals'] as $goal) {
                CampaignGoalProgress::query()->create([
                    'world_id' => $world->id,
                    'character_id' => $player->id,
                    'goal_key' => $goal['key'],
                    'status' => 'available',
                    'progress' => 0,
                ]);
            }

            $firstPulse = $date->copy()->addDays((int) $cfg['pulse_interval_days']);
            $this->schedule->execute(
                $world,
                'campaign_pulse',
                $firstPulse,
                ['scenario' => 'europa-1347'],
                'campaign_pulse:'.$world->id.':'.$firstPulse->toDateString()
            );

            return new Europa1347Context(
                $world->fresh(),
                $campaign->fresh(),
                $user->fresh(),
                $player->fresh(),
                $archetype,
                $characters->map->fresh(),
                $territories->map->fresh(),
                $titles->map->fresh()
            );
        });
    }
}
