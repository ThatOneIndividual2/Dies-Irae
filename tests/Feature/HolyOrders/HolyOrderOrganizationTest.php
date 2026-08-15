<?php

namespace Tests\Feature\HolyOrders;

use App\Actions\Apocalypse\EnsureApocalypseState;
use App\Actions\Church\ExcommunicateHolyOrder;
use App\Actions\Church\RecognizeRelicAuthenticity;
use App\Actions\Church\SanctionHolyOrder;
use App\Actions\Church\SuppressHolyOrder;
use App\Actions\HolyOrders\AssignHolyOrderMission;
use App\Actions\HolyOrders\CorruptHolyOrder;
use App\Actions\HolyOrders\CreateHolyOrderHeadquarters;
use App\Actions\HolyOrders\DeployHolyOrderForce;
use App\Actions\HolyOrders\DissolveHolyOrder;
use App\Actions\HolyOrders\DonateGoldToHolyOrder;
use App\Actions\HolyOrders\DonateLandToHolyOrder;
use App\Actions\HolyOrders\EntrustRelicToHolyOrder;
use App\Actions\HolyOrders\FoundHolyOrder;
use App\Actions\HolyOrders\OpenHolyOrderSchism;
use App\Actions\HolyOrders\PatronizeHolyOrder;
use App\Actions\HolyOrders\RecruitHolyOrderMember;
use App\Domain\Apocalypse\ApocalypseContract;
use App\Domain\Church\ChurchContract;
use App\Domain\Church\ChurchException;
use App\Domain\Diplomacy\DiplomacyContract;
use App\Domain\Economy\EconomyContract;
use App\Domain\Enums\HolyOrderAllegiance;
use App\Domain\Enums\HolyOrderForceStatus;
use App\Domain\Enums\HolyOrderHouseType;
use App\Domain\Enums\HolyOrderLegitimacy;
use App\Domain\Enums\HolyOrderMemberRank;
use App\Domain\Enums\HolyOrderMissionType;
use App\Domain\Enums\HolyOrderPapalRecognition;
use App\Domain\Enums\HolyOrderStatus;
use App\Domain\Enums\RelicAuthenticity;
use App\Domain\Enums\RelicCustodianType;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Heresy\HeresyContract;
use App\Domain\HolyOrders\HolyOrderContract;
use App\Domain\HolyOrders\HolyOrderException;
use App\Domain\HolyOrders\HolyOrderService;
use App\Domain\Titles\TitlesContract;
use App\Domain\Warfare\Enums\BelligerentKind;
use App\Domain\Warfare\WarfareContract;
use App\Models\Army;
use App\Models\Character;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\HolyOrderForce;
use App\Models\Relic;
use App\Models\RelicCustody;
use App\Models\SpiritualOfficeHoldership;
use App\Models\TitleOwnership;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\ChurchWorldGraph;
use Tests\TestCase;

class HolyOrderOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_independent_order_is_not_a_church_army_or_secular_title(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $order = $prepared['order'];
        $position = app(HolyOrderService::class)->politicalPosition($order);

        $this->assertSame('holy_orders', $order->getTable());
        $this->assertNotSame('armies', $order->getTable());
        $this->assertNotSame('titles', $order->getTable());
        $this->assertSame(HolyOrderStatus::ACTIVE, $order->status);
        $this->assertSame(HolyOrderAllegiance::INDEPENDENT, $order->allegiance_type);
        $this->assertSame(HolyOrderPapalRecognition::NONE, $order->papal_recognition_status);
        $this->assertSame('unsanctioned', $order->sanction_status);
        $this->assertTrue($position['independent']);
        $this->assertFalse($position['serves_pope']);
        $this->assertFalse($position['serves_king']);
        $this->assertSame(SpiritualOfficeRank::GRAND_MASTER, $order->grandMasterOffice->rank);
        $this->assertSame(
            $prepared['founder']->id,
            SpiritualOfficeHoldership::query()
                ->where('spiritual_office_id', $order->grand_master_office_id)
                ->where('is_current', true)
                ->value('holder_character_id')
        );
        $this->assertSame('holy_orders', app(HolyOrderContract::class)->domainKey());
        $this->assertSame('church', app(ChurchContract::class)->domainKey());
        $this->assertSame('warfare', app(WarfareContract::class)->domainKey());
        $this->assertSame(BelligerentKind::HOLY_ORDER, BelligerentKind::HOLY_ORDER);
    }

    public function test_papally_sanctioned_order_serves_the_pope(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $date = Carbon::parse('1348-07-05');

        $order = app(SanctionHolyOrder::class)->execute($g->pope, $prepared['order'], $date);

        $this->assertSame('sanctioned', $order->sanction_status);
        $this->assertTrue((bool) $order->papal_protection);
        $this->assertSame(HolyOrderPapalRecognition::RECOGNIZED, $order->papal_recognition_status);
        $this->assertSame(HolyOrderLegitimacy::RECOGNIZED, $order->legitimacy);
        $this->assertSame(HolyOrderAllegiance::PAPAL, $order->allegiance_type);

        $position = app(HolyOrderService::class)->politicalPosition($order);
        $this->assertTrue($position['serves_pope']);
        $this->assertTrue($position['politically_obligated']);
        $this->assertFalse($position['independent']);
        $this->assertDatabaseHas('holy_order_recognitions', [
            'holy_order_id' => $order->id,
            'status' => HolyOrderPapalRecognition::RECOGNIZED,
            'is_current' => 1,
        ]);

        $this->expectException(ChurchException::class);
        app(SanctionHolyOrder::class)->execute($g->king, $order, $date);
    }

    public function test_secular_patronage_lets_an_order_serve_a_king(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $date = Carbon::parse('1348-07-08');

        $patronage = app(PatronizeHolyOrder::class)->execute(
            $prepared['order'],
            $g->king,
            $date,
            null,
            $g->kingdom->id
        );
        $gift = app(DonateGoldToHolyOrder::class)->execute($prepared['order']->fresh(), $g->king, 40, $date);

        $order = $prepared['order']->fresh();
        $this->assertSame(HolyOrderAllegiance::ROYAL, $order->allegiance_type);
        $this->assertSame($g->king->id, $patronage->patron_character_id);
        $this->assertSame($g->kingdom->id, $patronage->patron_title_id);
        $this->assertSame(40, $gift->amount);
        $this->assertGreaterThan(20, $order->treasury);

        $position = app(HolyOrderService::class)->politicalPosition($order);
        $this->assertTrue($position['serves_king']);
        $this->assertFalse($position['independent']);
        $this->assertSame('diplomacy', app(DiplomacyContract::class)->domainKey());
        $this->assertSame('economy', app(EconomyContract::class)->domainKey());
    }

    public function test_order_can_hold_land_inside_a_secular_realm_without_becoming_a_title(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $date = Carbon::parse('1348-07-09');

        $estate = app(DonateLandToHolyOrder::class)->execute(
            $prepared['order'],
            $g->castle,
            $g->king,
            $date,
            $g->kingdom
        );

        $this->assertSame($g->castle->id, $estate->holding_id);
        $this->assertSame($g->king->id, $estate->donor_character_id);
        $this->assertSame($g->kingdom->id, $estate->donor_title_id);
        $this->assertTrue((bool) $estate->is_current);
        $this->assertSame(
            $g->king->id,
            TitleOwnership::query()
                ->where('title_id', $g->kingdom->id)
                ->where('is_current', true)
                ->value('holder_character_id')
        );
        $this->assertSame(
            $g->layCount->id,
            TitleOwnership::query()
                ->where('title_id', $g->county->id)
                ->where('is_current', true)
                ->value('holder_character_id')
        );
        $this->assertSame($g->paris->id, $g->castle->fresh()->territory_id);
        $this->assertNotSame('titles', $prepared['order']->getTable());
        $this->assertSame('titles', app(TitlesContract::class)->domainKey());
        $this->assertTrue(
            $prepared['order']->houses()->where('house_type', HolyOrderHouseType::COMMANDERY)->exists()
        );
    }

    public function test_deployment_uses_order_forces_not_ordinary_armies_and_is_not_universally_superior(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g, 16);
        $order = app(SanctionHolyOrder::class)->execute(
            $g->pope,
            $prepared['order'],
            Carbon::parse('1348-07-10')
        );
        app(AssignHolyOrderMission::class)->execute(
            $order,
            HolyOrderMissionType::FIGHT_DEMONS,
            Carbon::parse('1348-07-11'),
            $g->pope
        );

        $force = app(DeployHolyOrderForce::class)->execute(
            $order->fresh(),
            Carbon::parse('1348-07-12'),
            1,
            1,
            1,
            HolyOrderMissionType::FIGHT_DEMONS,
            $prepared['headquarters'],
            $prepared['founder'],
            $g->paris->id
        );

        $this->assertSame(HolyOrderForceStatus::DEPLOYED, $force->status);
        $this->assertSame(1, $force->knights);
        $this->assertSame(1, $force->sergeants);
        $this->assertSame(1, $force->chaplains);
        $this->assertGreaterThan(1.0, (float) $force->quality_modifier);
        $this->assertLessThanOrEqual(1.45, (float) $force->quality_modifier);
        $this->assertSame(0, Army::query()->where('world_id', $g->world->id)->count());
        $this->assertNull($force->warfare_army_id);
        $this->assertArrayHasKey('vows', $force->modifier_breakdown);
        $this->assertArrayHasKey('chaplains', $force->modifier_breakdown);
        $this->assertArrayHasKey('leadership', $force->modifier_breakdown);
        $this->assertGreaterThan(0, $force->modifier_breakdown['corruption_resistance']);

        $broken = app(CorruptHolyOrder::class)->execute($order->fresh(), 80);
        $weak = app(HolyOrderService::class)->forceModifiers($broken, [
            'knights' => 1,
            'sergeants' => 1,
            'chaplains' => 0,
        ]);
        $this->assertLessThan(1.0, $weak['quality_modifier']);
        $this->assertLessThan((float) $force->quality_modifier, $weak['quality_modifier']);
        $this->assertGreaterThanOrEqual(0.45, $weak['quality_modifier']);
    }

    public function test_limited_manpower_blocks_overrecruitment_and_overdeployment(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g, 8);
        $extra = $this->person($g, 'spare-knight', 'Gauthier');

        try {
            app(DeployHolyOrderForce::class)->execute(
                $prepared['order']->fresh(),
                Carbon::parse('1348-07-13'),
                3,
                0,
                0
            );
            $this->fail('Order deployed more knights than it has.');
        } catch (HolyOrderException $e) {
            $this->assertStringContainsString('knight', $e->getMessage());
        }

        $this->expectException(HolyOrderException::class);
        app(RecruitHolyOrderMember::class)->execute(
            $prepared['order']->fresh(),
            $extra,
            HolyOrderMemberRank::KNIGHT,
            Carbon::parse('1348-07-13')
        );
    }

    public function test_corruption_makes_an_order_controversial_and_can_fracture_legitimacy(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $order = app(SanctionHolyOrder::class)->execute(
            $g->pope,
            $prepared['order'],
            Carbon::parse('1348-07-14')
        );

        $order = app(CorruptHolyOrder::class)->execute($order, 45);
        $this->assertTrue((bool) $order->controversial);
        $this->assertSame(HolyOrderStatus::CONTROVERSIAL, $order->status);

        $order = app(CorruptHolyOrder::class)->execute($order, 30);
        $this->assertSame(HolyOrderLegitimacy::DISPUTED, $order->legitimacy);
        $this->assertGreaterThanOrEqual(70, (int) $order->corruption);

        $schism = app(OpenHolyOrderSchism::class)->execute(
            $order,
            Carbon::parse('1348-07-15'),
            $prepared['knight'],
            'rival_master'
        );
        $this->assertTrue((bool) $schism->is_current);
        $this->assertSame(HolyOrderLegitimacy::SCHISMATIC, $order->fresh()->legitimacy);
        $this->assertSame('heresy', app(HeresyContract::class)->domainKey());
    }

    public function test_suppression_withdraws_papal_recognition_and_blocks_deployment(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $order = app(SanctionHolyOrder::class)->execute(
            $g->pope,
            $prepared['order'],
            Carbon::parse('1348-07-16')
        );

        $order = app(SuppressHolyOrder::class)->execute($g->pope, $order, Carbon::parse('1348-07-17'));

        $this->assertSame(HolyOrderStatus::SUPPRESSED, $order->status);
        $this->assertSame(HolyOrderPapalRecognition::SUPPRESSED, $order->papal_recognition_status);
        $this->assertFalse((bool) $order->papal_protection);
        $this->assertSame('suppressed', $order->sanction_status);
        $this->assertFalse($order->houses()->where('is_active', true)->exists());
        $this->assertSame(
            0,
            HolyOrderForce::query()
                ->where('holy_order_id', $order->id)
                ->whereNull('disbanded_date')
                ->count()
        );

        $this->expectException(HolyOrderException::class);
        app(DeployHolyOrderForce::class)->execute(
            $order,
            Carbon::parse('1348-07-18'),
            1,
            0,
            0
        );
    }

    public function test_dissolution_confiscates_assets_and_relics(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $order = app(SanctionHolyOrder::class)->execute(
            $g->pope,
            $prepared['order'],
            Carbon::parse('1348-07-19')
        );
        app(DonateLandToHolyOrder::class)->execute(
            $order,
            $g->castle,
            $g->king,
            Carbon::parse('1348-07-20'),
            $g->kingdom
        );
        app(DonateGoldToHolyOrder::class)->execute($order->fresh(), $g->king, 25, Carbon::parse('1348-07-20'));

        $relic = Relic::query()->create([
            'world_id' => $g->world->id,
            'key' => 'order-true-cross',
            'name' => 'Fragment of the True Cross',
            'authenticity' => RelicAuthenticity::RECOGNIZED,
            'current_holding_id' => $prepared['headquarters']->holding_id,
        ]);
        app(EntrustRelicToHolyOrder::class)->execute(
            $order->fresh(),
            $relic,
            Carbon::parse('1348-07-21'),
            $prepared['headquarters'],
            $g->pope
        );

        app(SuppressHolyOrder::class)->execute($g->pope, $order->fresh(), Carbon::parse('1348-07-22'));
        $dissolved = app(DissolveHolyOrder::class)->execute(
            $order->fresh(),
            $g->pope,
            Carbon::parse('1348-07-23'),
            $g->king
        );

        $this->assertSame(HolyOrderStatus::DISSOLVED, $dissolved->status);
        $this->assertSame(0, (int) $dissolved->treasury);
        $this->assertSame(0, (int) $dissolved->manpower_current);
        $this->assertFalse($dissolved->currentHoldings()->exists());
        $this->assertSame($g->king->id, $g->castle->fresh()->owner_character_id);
        $this->assertSame($g->king->id, $relic->fresh()->current_character_id);
        $this->assertNull(
            RelicCustody::query()
                ->where('relic_id', $relic->id)
                ->where('is_current', true)
                ->value('holy_order_id')
        );
        $this->assertFalse($dissolved->currentMemberships()->exists());
        $this->assertFalse($dissolved->currentPatronage()->exists());
    }

    public function test_relic_custody_belongs_to_the_order_and_feeds_force_modifiers(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g, 16);
        $order = app(SanctionHolyOrder::class)->execute(
            $g->pope,
            $prepared['order'],
            Carbon::parse('1348-07-24')
        );

        $relic = Relic::query()->create([
            'world_id' => $g->world->id,
            'key' => 'head-of-denis-order',
            'name' => 'Head of Denis',
            'authenticity' => RelicAuthenticity::UNRECOGNIZED,
            'current_holding_id' => $g->abbeyHolding->id,
        ]);
        $relic = app(RecognizeRelicAuthenticity::class)->execute(
            $g->pope,
            $relic,
            RelicAuthenticity::RECOGNIZED,
            Carbon::parse('1348-07-25')
        );
        $custody = app(EntrustRelicToHolyOrder::class)->execute(
            $order,
            $relic,
            Carbon::parse('1348-07-26'),
            $prepared['headquarters'],
            $g->pope
        );

        $this->assertSame($order->id, $custody->holy_order_id);
        $this->assertSame(RelicCustodianType::HOLY_ORDER, $custody->custodian_type);
        $this->assertSame($order->id, $relic->fresh()->owner_id);
        $this->assertSame($prepared['headquarters']->holding_id, $relic->fresh()->current_holding_id);

        $quality = app(HolyOrderService::class)->forceModifiers($order->fresh(), [
            'knights' => 1,
            'sergeants' => 1,
            'chaplains' => 1,
        ]);
        $this->assertGreaterThan(0, $quality['breakdown']['relics']);
    }

    public function test_excommunication_and_apocalypse_strain_and_heresy_mission(): void
    {
        $g = ChurchWorldGraph::seed();
        $prepared = $this->prepareIndependentOrder($g);
        $order = app(SanctionHolyOrder::class)->execute(
            $g->pope,
            $prepared['order'],
            Carbon::parse('1348-07-27')
        );
        app(AssignHolyOrderMission::class)->execute(
            $order,
            HolyOrderMissionType::FIGHT_HERETICS,
            Carbon::parse('1348-07-28'),
            $g->pope
        );
        app(AssignHolyOrderMission::class)->execute(
            $order,
            HolyOrderMissionType::DEFEND_PILGRIMAGE,
            Carbon::parse('1348-07-28'),
            $g->pope
        );

        $this->assertTrue(
            $order->missions()->where('mission_type', HolyOrderMissionType::FIGHT_HERETICS)->where('is_current', true)->exists()
        );

        $state = app(EnsureApocalypseState::class)->execute($g->world);
        $state->pressure = 80;
        $state->save();
        $strained = app(HolyOrderService::class)->applyApocalypseStrain($order->fresh(), app(ApocalypseContract::class));
        $this->assertGreaterThan(0, (int) $strained->corruption);
        $this->assertSame('apocalypse', app(ApocalypseContract::class)->domainKey());

        $banned = app(ExcommunicateHolyOrder::class)->execute(
            $g->pope,
            $strained,
            Carbon::parse('1348-07-29')
        );
        $this->assertSame(HolyOrderStatus::EXCOMMUNICATED, $banned->status);
        $this->assertSame(HolyOrderPapalRecognition::WITHDRAWN, $banned->papal_recognition_status);
        $this->assertNotNull($banned->excommunicated_date);
    }

    /**
     * @return array{order: HolyOrder, founder: Character, knight: Character, sergeant: Character, chaplain: Character, headquarters: \App\Models\HolyOrderHouse}
     */
    private function prepareIndependentOrder(ChurchWorldGraph $g, int $manpowerCap = 80): array
    {
        $founder = $this->person($g, 'hugues-'.$manpowerCap.'-'.uniqid(), 'Hugues');
        $knight = $this->person($g, 'geoffroy-'.$manpowerCap.'-'.uniqid(), 'Geoffroy');
        $sergeant = $this->person($g, 'etienne-'.$manpowerCap.'-'.uniqid(), 'Etienne');
        $chaplain = $this->person($g, 'almaric-'.$manpowerCap.'-'.uniqid(), 'Almaric');
        $date = Carbon::parse('1348-06-25');

        $order = app(FoundHolyOrder::class)->execute(
            $g->world,
            $g->faith,
            $founder,
            'hospitallers-'.$manpowerCap.'-'.substr(uniqid(), -6),
            'Knights of the Hospital',
            $date,
            ['manpower_cap' => $manpowerCap]
        );

        $hqHolding = Holding::query()->create([
            'world_id' => $g->world->id,
            'territory_id' => $g->paris->id,
            'key' => 'temple-house-'.$order->id,
            'name' => 'Temple House',
            'holding_type' => 'castle',
        ]);

        $headquarters = app(CreateHolyOrderHeadquarters::class)->execute(
            $order,
            $hqHolding,
            $date,
            $founder,
            'Mother House'
        );

        app(RecruitHolyOrderMember::class)->execute($order->fresh(), $knight, HolyOrderMemberRank::KNIGHT, $date);
        app(RecruitHolyOrderMember::class)->execute($order->fresh(), $sergeant, HolyOrderMemberRank::SERGEANT, $date);
        app(RecruitHolyOrderMember::class)->execute($order->fresh(), $chaplain, HolyOrderMemberRank::CHAPLAIN, $date);

        return [
            'order' => $order->fresh(),
            'founder' => $founder,
            'knight' => $knight,
            'sergeant' => $sergeant,
            'chaplain' => $chaplain,
            'headquarters' => $headquarters,
        ];
    }

    private function person(ChurchWorldGraph $g, string $key, string $firstName): Character
    {
        return Character::query()->create([
            'world_id' => $g->world->id,
            'key' => $key,
            'first_name' => $firstName,
            'sex' => 'male',
            'birth_date' => '1312-01-01',
            'is_alive' => true,
            'faith_id' => $g->faith->id,
            'legitimacy_status' => 'legitimate',
        ]);
    }
}
