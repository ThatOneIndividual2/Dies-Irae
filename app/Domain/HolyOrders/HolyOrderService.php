<?php

namespace App\Domain\HolyOrders;

use App\Actions\Church\CreateSpiritualOffice;
use App\Domain\Apocalypse\ApocalypseContract;
use App\Domain\Church\SpiritualOfficeHoldershipMutator;
use App\Domain\Enums\AppointmentMode;
use App\Domain\Enums\HolyOrderAllegiance;
use App\Domain\Enums\HolyOrderForceStatus;
use App\Domain\Enums\HolyOrderHouseType;
use App\Domain\Enums\HolyOrderLegitimacy;
use App\Domain\Enums\HolyOrderMemberRank;
use App\Domain\Enums\HolyOrderMissionType;
use App\Domain\Enums\HolyOrderPapalRecognition;
use App\Domain\Enums\HolyOrderStatus;
use App\Domain\Enums\HolyOrderVowType;
use App\Domain\Enums\OfficeAcquisitionType;
use App\Domain\Enums\RelicAcquisition;
use App\Domain\Enums\RelicCustodianType;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\HolyOrderDissolved;
use App\Events\HolyOrderFounded;
use App\Events\HolyOrderRecognitionWithdrawn;
use App\Events\HolyOrderSuppressed;
use App\Models\Character;
use App\Models\Faith;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\HolyOrderForce;
use App\Models\HolyOrderHolding;
use App\Models\HolyOrderHouse;
use App\Models\HolyOrderMembership;
use App\Models\HolyOrderMission;
use App\Models\HolyOrderPatronage;
use App\Models\HolyOrderRecognition;
use App\Models\HolyOrderSchism;
use App\Models\HolyOrderTreasuryEntry;
use App\Models\HolyOrderVow;
use App\Models\Relic;
use App\Models\RelicCustody;
use App\Models\RelicEvent;
use App\Models\SpiritualOffice;
use App\Models\Title;
use App\Models\World;
use Carbon\CarbonInterface;

final class HolyOrderService
{
    public function __construct(
        private ForceModifierCalculator $modifiers,
        private PoliticalPositionResolver $politics,
        private SpiritualOfficeHoldershipMutator $holderships,
        private CreateSpiritualOffice $createOffice
    ) {
    }

    public function domainKey(): string
    {
        return 'holy_orders';
    }

    public function politicalPosition(HolyOrder $order): array
    {
        return $this->politics->resolve($order);
    }

    public function forceModifiers(HolyOrder $order, array $composition = [], ?string $targetKind = null): array
    {
        return $this->modifiers->calculate($order, $composition, $targetKind);
    }

    public function found(
        World $world,
        Faith $faith,
        Character $founder,
        string $key,
        string $name,
        CarbonInterface $date,
        array $attributes = []
    ): HolyOrder {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $faith->world_id, 'found holy order faith');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $founder->world_id, 'found holy order founder');

        return Transactional::run(function () use ($world, $faith, $founder, $key, $name, $date, $attributes) {
            $order = HolyOrder::query()->create([
                'world_id' => $world->id,
                'faith_id' => $faith->id,
                'religious_order_id' => $attributes['religious_order_id'] ?? null,
                'key' => $key,
                'name' => $name,
                'sanction_status' => 'unsanctioned',
                'papal_protection' => false,
                'allegiance_type' => HolyOrderAllegiance::INDEPENDENT,
                'status' => HolyOrderStatus::FOUNDING,
                'legitimacy' => HolyOrderLegitimacy::UNRECOGNIZED,
                'papal_recognition_status' => HolyOrderPapalRecognition::NONE,
                'treasury' => (int) config('holy_orders.starting_treasury', 20),
                'manpower_cap' => (int) ($attributes['manpower_cap'] ?? config('holy_orders.default_manpower_cap', 80)),
                'manpower_current' => 0,
                'corruption' => 0,
                'reputation' => (int) config('holy_orders.starting_reputation', 40),
                'morale' => (int) config('holy_orders.starting_morale', 70),
                'consecration' => (int) config('holy_orders.starting_consecration', 40),
                'corruption_resistance' => (int) config('holy_orders.starting_corruption_resistance', 50),
                'controversial' => false,
                'schism_status' => 'none',
                'founded_date' => $date->toDateString(),
            ]);

            $office = $this->createOffice->execute(
                $world,
                'gm-'.$key,
                'Grand Master of '.$name,
                SpiritualOfficeRank::GRAND_MASTER,
                [
                    'holy_order_id' => $order->id,
                    'religious_order_id' => $attributes['religious_order_id'] ?? null,
                    'appointment_mode' => AppointmentMode::INTERNAL_ELECTION,
                ]
            );

            $order->grand_master_office_id = $office->id;
            $order->save();

            $this->holderships->appoint(
                $office,
                $founder,
                $date,
                OfficeAcquisitionType::ELECTION,
                $founder
            );

            $this->recruit($order, $founder, HolyOrderMemberRank::GRAND_MASTER, $date, true);

            $this->creditTreasury($order, (int) $order->treasury, 'foundation', $founder, $date, false);

            event(new HolyOrderFounded((int) $order->id, (int) $founder->id, $date->toDateString()));

            return $order->fresh();
        });
    }

    public function createHeadquarters(
        HolyOrder $order,
        Holding $holding,
        CarbonInterface $date,
        ?Character $commander = null,
        string $name = 'Headquarters'
    ): HolyOrderHouse {
        $this->assertOperable($order, 'create headquarters');
        WorldBoundary::assertSameWorldEntities('holy order headquarters', $order, $holding);
        if ($commander) {
            WorldBoundary::assertSameWorldEntities('holy order headquarters commander', $order, $commander);
        }

        if (HolyOrderHouse::query()->where('holy_order_id', $order->id)->where('is_headquarters', true)->where('is_active', true)->exists()) {
            throw new HolyOrderException('Order already has an active headquarters.');
        }

        return Transactional::run(function () use ($order, $holding, $date, $commander, $name) {
            $house = HolyOrderHouse::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'holding_id' => $holding->id,
                'territory_id' => $holding->territory_id,
                'commander_character_id' => $commander?->id,
                'name' => $name,
                'house_type' => HolyOrderHouseType::HEADQUARTERS,
                'is_headquarters' => true,
                'is_active' => true,
                'founded_date' => $date->toDateString(),
            ]);

            $this->recordHolding($order, $holding, $date, $commander, null, 'foundation', $house);

            $order->headquarters_holding_id = $holding->id;
            if ($order->status === HolyOrderStatus::FOUNDING) {
                $order->status = HolyOrderStatus::ACTIVE;
            }
            $order->save();

            return $house->fresh();
        });
    }

    public function expandHouse(
        HolyOrder $order,
        Holding $holding,
        CarbonInterface $date,
        string $houseType = HolyOrderHouseType::COMMANDERY,
        ?Character $commander = null,
        ?string $name = null
    ): HolyOrderHouse {
        $this->assertOperable($order, 'expand house');
        WorldBoundary::assertSameWorldEntities('holy order house', $order, $holding);
        if ($commander) {
            WorldBoundary::assertSameWorldEntities('holy order house commander', $order, $commander);
        }

        if (!in_array($houseType, HolyOrderHouseType::all(), true)) {
            throw new HolyOrderException("Unknown house type: {$houseType}");
        }

        return Transactional::run(function () use ($order, $holding, $date, $houseType, $commander, $name) {
            $house = HolyOrderHouse::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'holding_id' => $holding->id,
                'territory_id' => $holding->territory_id,
                'commander_character_id' => $commander?->id,
                'name' => $name ?: ucfirst($houseType).' of '.$holding->name,
                'house_type' => $houseType,
                'is_headquarters' => false,
                'is_active' => true,
                'founded_date' => $date->toDateString(),
            ]);

            return $house->fresh();
        });
    }

    public function recruit(
        HolyOrder $order,
        Character $character,
        string $rank,
        CarbonInterface $date,
        bool $swearRule = true,
        ?HolyOrderHouse $house = null
    ): HolyOrderMembership {
        $this->assertOperable($order, 'recruit');
        WorldBoundary::assertSameWorldEntities('holy order recruit', $order, $character);
        if ($house) {
            WorldBoundary::assertSameWorldEntities('holy order recruit house', $order, $house);
        }

        if (!in_array($rank, HolyOrderMemberRank::all(), true)) {
            throw new HolyOrderException("Unknown member rank: {$rank}");
        }

        $cost = $this->manpowerCost($rank);
        if ((int) $order->manpower_current + $cost > (int) $order->manpower_cap) {
            throw new HolyOrderException('Order manpower cap would be exceeded.');
        }

        $existing = HolyOrderMembership::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();
        if ($existing) {
            throw new HolyOrderException('Character already belongs to a holy order.');
        }

        return Transactional::run(function () use ($order, $character, $rank, $date, $swearRule, $house, $cost) {
            $membership = HolyOrderMembership::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'character_id' => $character->id,
                'house_id' => $house?->id,
                'member_rank' => $rank,
                'recruited_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            if ($swearRule) {
                $this->swearVows($membership, HolyOrderVowType::militaryRule(), $date);
            }

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->manpower_current = (int) $locked->manpower_current + $cost;
            $locked->save();

            return $membership->fresh();
        });
    }

    /**
     * @param string[] $vowTypes
     */
    public function swearVows(HolyOrderMembership $membership, array $vowTypes, CarbonInterface $date): void
    {
        foreach ($vowTypes as $vowType) {
            if (!in_array($vowType, HolyOrderVowType::all(), true)) {
                throw new HolyOrderException("Unknown vow: {$vowType}");
            }

            HolyOrderVow::query()->create([
                'world_id' => $membership->world_id,
                'membership_id' => $membership->id,
                'vow_type' => $vowType,
                'integrity' => 'kept',
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        }
    }

    public function appointGrandMaster(
        HolyOrder $order,
        Character $character,
        CarbonInterface $date,
        ?Character $appointedBy = null
    ): HolyOrderMembership {
        $this->assertOperable($order, 'appoint grand master');
        WorldBoundary::assertSameWorldEntities('appoint grand master', $order, $character);

        $office = SpiritualOffice::query()->find($order->grand_master_office_id);
        if (!$office) {
            throw new HolyOrderException('Order has no grand master office.');
        }

        return Transactional::run(function () use ($order, $character, $date, $appointedBy, $office) {
            $this->holderships->appoint(
                $office,
                $character,
                $date,
                OfficeAcquisitionType::ELECTION,
                $appointedBy ?? $character
            );

            $current = HolyOrderMembership::query()
                ->where('holy_order_id', $order->id)
                ->where('character_id', $character->id)
                ->where('is_current', true)
                ->first();

            if ($current) {
                $current->member_rank = HolyOrderMemberRank::GRAND_MASTER;
                $current->save();

                return $current->fresh();
            }

            return $this->recruit($order, $character, HolyOrderMemberRank::GRAND_MASTER, $date);
        });
    }

    public function donateLand(
        HolyOrder $order,
        Holding $holding,
        Character $donor,
        CarbonInterface $date,
        ?Title $donorTitle = null,
        bool $foundCommandery = true
    ): HolyOrderHolding {
        $this->assertOperable($order, 'receive land');
        WorldBoundary::assertSameWorldEntities('holy order land donation', $order, $holding, $donor);
        if ($donorTitle) {
            WorldBoundary::assertSameWorldEntities('holy order land donor title', $order, $donorTitle);
        }

        return Transactional::run(function () use ($order, $holding, $donor, $date, $donorTitle, $foundCommandery) {
            $house = null;
            if ($foundCommandery) {
                $house = $this->expandHouse(
                    $order,
                    $holding,
                    $date,
                    HolyOrderHouseType::COMMANDERY,
                    null,
                    'Commandery of '.$holding->name
                );
            }

            $row = $this->recordHolding(
                $order,
                $holding,
                $date,
                $donor,
                $donorTitle,
                'donation',
                $house
            );

            return $row->fresh();
        });
    }

    public function donateGold(
        HolyOrder $order,
        Character $donor,
        int $amount,
        CarbonInterface $date,
        ?int $sourceRealmId = null
    ): HolyOrderTreasuryEntry {
        $this->assertOperable($order, 'receive donation');
        WorldBoundary::assertSameWorldEntities('holy order gold donation', $order, $donor);

        if ($amount <= 0) {
            throw new HolyOrderException('Donation amount must be positive.');
        }

        return $this->creditTreasury($order, $amount, 'donation', $donor, $date, true, $sourceRealmId);
    }

    public function patronize(
        HolyOrder $order,
        Character $patron,
        CarbonInterface $date,
        ?int $realmId = null,
        ?int $titleId = null
    ): HolyOrderPatronage {
        $this->assertOperable($order, 'accept patronage');
        WorldBoundary::assertSameWorldEntities('holy order patronage', $order, $patron);

        return Transactional::run(function () use ($order, $patron, $date, $realmId, $titleId) {
            $this->endCurrentRows(HolyOrderPatronage::query()->where('holy_order_id', $order->id), $date);

            $patronage = HolyOrderPatronage::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'patron_character_id' => $patron->id,
                'patron_realm_id' => $realmId,
                'patron_title_id' => $titleId,
                'status' => 'active',
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->allegiance_type = HolyOrderAllegiance::ROYAL;
            $locked->save();

            return $patronage->fresh();
        });
    }

    public function assignMission(
        HolyOrder $order,
        string $missionType,
        CarbonInterface $date,
        ?Character $assignedBy = null
    ): HolyOrderMission {
        $this->assertOperable($order, 'assign mission');
        if ($assignedBy) {
            WorldBoundary::assertSameWorldEntities('holy order mission', $order, $assignedBy);
        }

        if (!in_array($missionType, HolyOrderMissionType::all(), true)) {
            throw new HolyOrderException("Unknown mission: {$missionType}");
        }

        return Transactional::run(function () use ($order, $missionType, $date, $assignedBy) {
            $this->endCurrentRows(
                HolyOrderMission::query()
                    ->where('holy_order_id', $order->id)
                    ->where('mission_type', $missionType),
                $date
            );

            return HolyOrderMission::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'mission_type' => $missionType,
                'status' => 'active',
                'assigned_by_character_id' => $assignedBy?->id,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        });
    }

    public function deployForce(
        HolyOrder $order,
        CarbonInterface $date,
        int $knights,
        int $sergeants,
        int $chaplains,
        ?string $targetKind = null,
        ?HolyOrderHouse $house = null,
        ?Character $commander = null,
        ?int $territoryId = null
    ): HolyOrderForce {
        if (!HolyOrderStatus::canDeploy((string) $order->status)) {
            throw new HolyOrderException('Order cannot deploy in its current status.');
        }

        WorldBoundary::assertSameWorldEntities('holy order deploy', $order);
        if ($house) {
            WorldBoundary::assertSameWorldEntities('holy order deploy house', $order, $house);
        }
        if ($commander) {
            WorldBoundary::assertSameWorldEntities('holy order deploy commander', $order, $commander);
        }

        $this->assertRankAvailable($order, HolyOrderMemberRank::KNIGHT, $knights);
        $this->assertRankAvailable($order, HolyOrderMemberRank::SERGEANT, $sergeants);
        $this->assertRankAvailable($order, HolyOrderMemberRank::CHAPLAIN, $chaplains);

        $composition = [
            'knights' => $knights,
            'sergeants' => $sergeants,
            'chaplains' => $chaplains,
        ];
        $quality = $this->modifiers->calculate($order, $composition, $targetKind);

        return HolyOrderForce::query()->create([
            'world_id' => $order->world_id,
            'holy_order_id' => $order->id,
            'house_id' => $house?->id,
            'commander_character_id' => $commander?->id ?? $this->grandMasterCharacterId($order),
            'stationed_territory_id' => $territoryId ?? $house?->territory_id,
            'knights' => $knights,
            'sergeants' => $sergeants,
            'chaplains' => $chaplains,
            'quality_modifier' => $quality['quality_modifier'],
            'modifier_breakdown' => $quality['breakdown'],
            'status' => HolyOrderForceStatus::DEPLOYED,
            'target_kind' => $targetKind,
            'raised_date' => $date->toDateString(),
        ]);
    }

    public function corrupt(HolyOrder $order, int $amount): HolyOrder
    {
        if ($amount < 0) {
            throw new HolyOrderException('Corruption delta must be non-negative.');
        }

        $locked = HolyOrder::query()->whereKey($order->id)->firstOrFail();
        $locked->corruption = min(100, (int) $locked->corruption + $amount);
        $locked->morale = max(0, (int) $locked->morale - (int) floor($amount / 2));
        $locked->reputation = max(0, (int) $locked->reputation - $amount);
        $locked->consecration = max(0, (int) $locked->consecration - (int) floor($amount / 2));
        $locked->corruption_resistance = max(0, (int) $locked->corruption_resistance - (int) floor($amount / 3));

        $threshold = (int) config('holy_orders.controversial_corruption', 40);
        $fracture = (int) config('holy_orders.fracture_corruption', 70);

        if ((int) $locked->corruption >= $threshold) {
            $locked->controversial = true;
            if (in_array($locked->status, [HolyOrderStatus::FOUNDING, HolyOrderStatus::ACTIVE], true)) {
                $locked->status = HolyOrderStatus::CONTROVERSIAL;
            }
        }

        if ((int) $locked->corruption >= $fracture
            && $locked->legitimacy === HolyOrderLegitimacy::RECOGNIZED
        ) {
            $locked->legitimacy = HolyOrderLegitimacy::DISPUTED;
        }

        $locked->save();

        return $locked->fresh();
    }

    public function openSchism(
        HolyOrder $order,
        CarbonInterface $date,
        ?Character $rival = null,
        string $cause = 'internal_schism'
    ): HolyOrderSchism {
        WorldBoundary::assertSameWorldEntities('holy order schism', $order);
        if ($rival) {
            WorldBoundary::assertSameWorldEntities('holy order schism rival', $order, $rival);
        }

        if ($order->currentSchism()->exists()) {
            throw new HolyOrderException('Order already has an open schism.');
        }

        return Transactional::run(function () use ($order, $date, $rival, $cause) {
            $schism = HolyOrderSchism::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'rival_character_id' => $rival?->id,
                'cause' => $cause,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->schism_status = 'open';
            $locked->legitimacy = HolyOrderLegitimacy::SCHISMATIC;
            $locked->controversial = true;
            if (in_array($locked->status, [HolyOrderStatus::FOUNDING, HolyOrderStatus::ACTIVE], true)) {
                $locked->status = HolyOrderStatus::CONTROVERSIAL;
            }
            $locked->save();

            return $schism->fresh();
        });
    }

    public function recordPapalSanction(HolyOrder $order, Character $actor, CarbonInterface $date): HolyOrder
    {
        WorldBoundary::assertSameWorldEntities('papal sanction holy order', $order, $actor);
        $this->assertNotTerminal($order, 'receive papal sanction');

        return Transactional::run(function () use ($order, $actor, $date) {
            $this->endCurrentRows(HolyOrderRecognition::query()->where('holy_order_id', $order->id), $date);

            HolyOrderRecognition::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'granted_by_character_id' => $actor->id,
                'status' => HolyOrderPapalRecognition::RECOGNIZED,
                'bull_key' => 'sanction-'.$order->key,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->sanction_status = 'sanctioned';
            $locked->papal_protection = true;
            $locked->sanctioned_date = $date->toDateString();
            $locked->papal_recognition_status = HolyOrderPapalRecognition::RECOGNIZED;
            $locked->legitimacy = HolyOrderLegitimacy::RECOGNIZED;
            if (in_array($locked->status, [HolyOrderStatus::FOUNDING, HolyOrderStatus::ACTIVE], true)
                || $locked->status === HolyOrderStatus::CONTROVERSIAL
            ) {
                $locked->status = HolyOrderStatus::ACTIVE;
                $locked->controversial = false;
            }
            if ($locked->allegiance_type === HolyOrderAllegiance::INDEPENDENT) {
                $locked->allegiance_type = HolyOrderAllegiance::PAPAL;
            }
            $locked->save();

            return $locked->fresh();
        });
    }

    public function withdrawRecognition(HolyOrder $order, Character $actor, CarbonInterface $date): HolyOrder
    {
        WorldBoundary::assertSameWorldEntities('withdraw holy order recognition', $order, $actor);
        $this->assertNotTerminal($order, 'lose papal recognition');

        return Transactional::run(function () use ($order, $actor, $date) {
            $this->endCurrentRows(HolyOrderRecognition::query()->where('holy_order_id', $order->id), $date);

            HolyOrderRecognition::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'granted_by_character_id' => $actor->id,
                'status' => HolyOrderPapalRecognition::WITHDRAWN,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->papal_protection = false;
            $locked->papal_recognition_status = HolyOrderPapalRecognition::WITHDRAWN;
            $locked->legitimacy = HolyOrderLegitimacy::DISPUTED;
            $locked->controversial = true;
            if ($locked->allegiance_type === HolyOrderAllegiance::PAPAL) {
                $locked->allegiance_type = $locked->currentPatronage()->exists()
                    ? HolyOrderAllegiance::ROYAL
                    : HolyOrderAllegiance::INDEPENDENT;
            }
            if ($locked->status === HolyOrderStatus::ACTIVE) {
                $locked->status = HolyOrderStatus::CONTROVERSIAL;
            }
            $locked->save();

            event(new HolyOrderRecognitionWithdrawn((int) $locked->id, (int) $actor->id, $date->toDateString()));

            return $locked->fresh();
        });
    }

    public function suppress(HolyOrder $order, Character $actor, CarbonInterface $date): HolyOrder
    {
        WorldBoundary::assertSameWorldEntities('suppress holy order', $order, $actor);
        $this->assertNotTerminal($order, 'be suppressed');

        return Transactional::run(function () use ($order, $actor, $date) {
            $this->endCurrentRows(HolyOrderRecognition::query()->where('holy_order_id', $order->id), $date);

            HolyOrderRecognition::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'granted_by_character_id' => $actor->id,
                'status' => HolyOrderPapalRecognition::SUPPRESSED,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $this->disbandForces($order, $date);

            HolyOrderHouse::query()
                ->where('holy_order_id', $order->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'suppressed_date' => $date->toDateString(),
                ]);

            $this->endCurrentRows(HolyOrderMission::query()->where('holy_order_id', $order->id), $date);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->status = HolyOrderStatus::SUPPRESSED;
            $locked->legitimacy = HolyOrderLegitimacy::SUPPRESSED;
            $locked->sanction_status = 'suppressed';
            $locked->papal_protection = false;
            $locked->papal_recognition_status = HolyOrderPapalRecognition::SUPPRESSED;
            $locked->suppressed_date = $date->toDateString();
            $locked->controversial = true;
            $locked->save();

            event(new HolyOrderSuppressed((int) $locked->id, (int) $actor->id, $date->toDateString()));

            return $locked->fresh();
        });
    }

    public function excommunicateOrder(HolyOrder $order, Character $actor, CarbonInterface $date): HolyOrder
    {
        WorldBoundary::assertSameWorldEntities('excommunicate holy order', $order, $actor);
        $this->assertNotTerminal($order, 'be excommunicated');

        return Transactional::run(function () use ($order, $actor, $date) {
            $this->withdrawRecognition($order, $actor, $date);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->status = HolyOrderStatus::EXCOMMUNICATED;
            $locked->excommunicated_date = $date->toDateString();
            $locked->controversial = true;
            $locked->save();

            return $locked->fresh();
        });
    }

    public function dissolve(
        HolyOrder $order,
        Character $actor,
        CarbonInterface $date,
        ?Character $confiscator = null
    ): HolyOrder {
        WorldBoundary::assertSameWorldEntities('dissolve holy order', $order, $actor);
        $confiscator = $confiscator ?? $actor;
        WorldBoundary::assertSameWorldEntities('confiscate holy order', $order, $confiscator);

        if ($order->status === HolyOrderStatus::DISSOLVED) {
            throw new HolyOrderException('Order is already dissolved.');
        }

        return Transactional::run(function () use ($order, $actor, $date, $confiscator) {
            $this->confiscateAssets($order, $confiscator, $date);
            $this->disbandForces($order, $date);
            $this->endCurrentRows(HolyOrderMembership::query()->where('holy_order_id', $order->id), $date, 'ended_date');
            $this->endCurrentRows(HolyOrderPatronage::query()->where('holy_order_id', $order->id), $date);
            $this->endCurrentRows(HolyOrderMission::query()->where('holy_order_id', $order->id), $date);
            $this->endCurrentRows(HolyOrderRecognition::query()->where('holy_order_id', $order->id), $date);
            $this->endCurrentRows(HolyOrderSchism::query()->where('holy_order_id', $order->id), $date);

            HolyOrderHouse::query()
                ->where('holy_order_id', $order->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'suppressed_date' => $date->toDateString(),
                ]);

            $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->status = HolyOrderStatus::DISSOLVED;
            $locked->legitimacy = HolyOrderLegitimacy::DISSOLVED;
            $locked->papal_protection = false;
            $locked->dissolved_date = $date->toDateString();
            $locked->manpower_current = 0;
            $locked->treasury = 0;
            $locked->save();

            event(new HolyOrderDissolved((int) $locked->id, (int) $actor->id, $date->toDateString()));

            return $locked->fresh();
        });
    }

    public function confiscateAssets(HolyOrder $order, Character $confiscator, CarbonInterface $date): void
    {
        WorldBoundary::assertSameWorldEntities('confiscate holy order assets', $order, $confiscator);

        $holdings = HolyOrderHolding::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->get();

        foreach ($holdings as $row) {
            HolyOrderHolding::query()->whereKey($row->id)->update([
                'is_current' => null,
                'confiscated_date' => $date->toDateString(),
                'confiscated_by_character_id' => $confiscator->id,
            ]);

            if ($row->holding_id) {
                Holding::query()->whereKey($row->holding_id)->update([
                    'owner_character_id' => $confiscator->id,
                ]);
            }
        }

        if ((int) $order->treasury > 0) {
            $this->creditTreasury($order, -1 * (int) $order->treasury, 'confiscation', $confiscator, $date, true);
        }

        $custodies = RelicCustody::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->get();

        foreach ($custodies as $custody) {
            RelicCustody::query()->whereKey($custody->id)->update([
                'is_current' => null,
                'lost_date' => $date->toDateString(),
            ]);

            RelicCustody::query()->create([
                'world_id' => $order->world_id,
                'relic_id' => $custody->relic_id,
                'holding_id' => null,
                'territory_id' => $confiscator->residence_territory_id,
                'character_id' => $confiscator->id,
                'holy_order_id' => null,
                'holy_order_house_id' => null,
                'custodian_type' => RelicCustodianType::PRIVATE_CHARACTER,
                'custodian_id' => $confiscator->id,
                'acquisition' => RelicAcquisition::LOOT,
                'acquired_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            Relic::query()->whereKey($custody->relic_id)->update([
                'current_character_id' => $confiscator->id,
                'current_holding_id' => null,
                'owner_type' => RelicCustodianType::PRIVATE_CHARACTER,
                'owner_id' => $confiscator->id,
            ]);
        }
    }

    public function entrustRelic(
        HolyOrder $order,
        Relic $relic,
        CarbonInterface $date,
        ?HolyOrderHouse $house = null,
        ?Character $actor = null
    ): RelicCustody {
        $this->assertOperable($order, 'receive relic');
        WorldBoundary::assertSameWorldEntities('holy order relic', $order, $relic);
        if ($house) {
            WorldBoundary::assertSameWorldEntities('holy order relic house', $order, $house);
        }
        if ($actor) {
            WorldBoundary::assertSameWorldEntities('holy order relic actor', $order, $actor);
        }

        return Transactional::run(function () use ($order, $relic, $date, $house, $actor) {
            RelicCustody::query()
                ->where('relic_id', $relic->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => null,
                    'lost_date' => $date->toDateString(),
                ]);

            $holdingId = $house?->holding_id ?? $order->headquarters_holding_id;
            $territoryId = $house?->territory_id;

            $custody = RelicCustody::query()->create([
                'world_id' => $order->world_id,
                'relic_id' => $relic->id,
                'holding_id' => $holdingId,
                'territory_id' => $territoryId,
                'character_id' => null,
                'holy_order_id' => $order->id,
                'holy_order_house_id' => $house?->id,
                'custodian_type' => RelicCustodianType::HOLY_ORDER,
                'custodian_id' => $order->id,
                'acquisition' => RelicAcquisition::DEPOSIT,
                'acquired_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            $relic->current_holding_id = $holdingId;
            $relic->current_territory_id = $territoryId;
            $relic->current_character_id = null;
            $relic->owner_type = RelicCustodianType::HOLY_ORDER;
            $relic->owner_id = $order->id;
            $relic->save();

            RelicEvent::query()->create([
                'world_id' => $order->world_id,
                'relic_id' => $relic->id,
                'event_type' => 'entrusted_to_order',
                'actor_character_id' => $actor?->id,
                'occurred_date' => $date->toDateString(),
                'metadata' => ['holy_order_id' => $order->id],
            ]);

            return $custody->fresh();
        });
    }

    public function applyApocalypseStrain(HolyOrder $order, ApocalypseContract $apocalypse): HolyOrder
    {
        $intensity = $apocalypse->intensity((int) $order->world_id);
        if ($intensity < 40) {
            return $order;
        }

        $delta = min(20, (int) floor($intensity / 8));

        return $this->corrupt($order, max(1, $delta));
    }

    private function recordHolding(
        HolyOrder $order,
        Holding $holding,
        CarbonInterface $date,
        ?Character $donor,
        ?Title $donorTitle,
        string $acquisitionType,
        ?HolyOrderHouse $house
    ): HolyOrderHolding {
        $existing = HolyOrderHolding::query()
            ->where('holding_id', $holding->id)
            ->where('is_current', true)
            ->first();
        if ($existing) {
            throw new HolyOrderException('Holding is already a current holy-order estate.');
        }

        return HolyOrderHolding::query()->create([
            'world_id' => $order->world_id,
            'holy_order_id' => $order->id,
            'holding_id' => $holding->id,
            'house_id' => $house?->id,
            'donor_character_id' => $donor?->id,
            'donor_title_id' => $donorTitle?->id,
            'acquisition_type' => $acquisitionType,
            'acquired_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    private function creditTreasury(
        HolyOrder $order,
        int $amount,
        string $reason,
        ?Character $source,
        CarbonInterface $date,
        bool $mutateBalance,
        ?int $sourceRealmId = null
    ): HolyOrderTreasuryEntry {
        return Transactional::run(function () use ($order, $amount, $reason, $source, $date, $mutateBalance, $sourceRealmId) {
            if ($mutateBalance) {
                $locked = HolyOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $locked->treasury = (int) $locked->treasury + $amount;
                if ($locked->treasury < 0) {
                    $locked->treasury = 0;
                }
                $locked->save();
            }

            return HolyOrderTreasuryEntry::query()->create([
                'world_id' => $order->world_id,
                'holy_order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
                'source_character_id' => $source?->id,
                'source_realm_id' => $sourceRealmId,
                'occurred_date' => $date->toDateString(),
            ]);
        });
    }

    private function disbandForces(HolyOrder $order, CarbonInterface $date): void
    {
        HolyOrderForce::query()
            ->where('holy_order_id', $order->id)
            ->whereNull('disbanded_date')
            ->update([
                'status' => HolyOrderForceStatus::DISBANDED,
                'disbanded_date' => $date->toDateString(),
            ]);
    }

    private function endCurrentRows($query, CarbonInterface $date, string $endedColumn = 'ended_date'): void
    {
        $query->where('is_current', true)->update([
            'is_current' => null,
            $endedColumn => $date->toDateString(),
        ]);
    }

    private function assertOperable(HolyOrder $order, string $action): void
    {
        if (in_array($order->status, [
            HolyOrderStatus::SUPPRESSED,
            HolyOrderStatus::DISSOLVED,
            HolyOrderStatus::EXCOMMUNICATED,
        ], true)) {
            throw new HolyOrderException("Order cannot {$action} while {$order->status}.");
        }
    }

    private function assertNotTerminal(HolyOrder $order, string $action): void
    {
        if ($order->status === HolyOrderStatus::DISSOLVED) {
            throw new HolyOrderException("Order cannot {$action} after dissolution.");
        }
    }

    private function manpowerCost(string $rank): int
    {
        return match ($rank) {
            HolyOrderMemberRank::KNIGHT, HolyOrderMemberRank::GRAND_MASTER, HolyOrderMemberRank::COMMANDER => (int) config('holy_orders.knight_manpower_cost', 3),
            HolyOrderMemberRank::CHAPLAIN => (int) config('holy_orders.chaplain_manpower_cost', 1),
            default => (int) config('holy_orders.sergeant_manpower_cost', 1),
        };
    }

    private function assertRankAvailable(HolyOrder $order, string $rank, int $requested): void
    {
        if ($requested < 0) {
            throw new HolyOrderException('Force composition cannot be negative.');
        }
        if ($requested === 0) {
            return;
        }

        $ranks = [$rank];
        if ($rank === HolyOrderMemberRank::KNIGHT) {
            $ranks = [
                HolyOrderMemberRank::KNIGHT,
                HolyOrderMemberRank::COMMANDER,
                HolyOrderMemberRank::GRAND_MASTER,
            ];
        }

        $members = HolyOrderMembership::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->whereIn('member_rank', $ranks)
            ->count();

        $column = match ($rank) {
            HolyOrderMemberRank::SERGEANT => 'sergeants',
            HolyOrderMemberRank::CHAPLAIN => 'chaplains',
            default => 'knights',
        };

        $deployed = (int) HolyOrderForce::query()
            ->where('holy_order_id', $order->id)
            ->whereNull('disbanded_date')
            ->sum($column);

        if ($requested > ($members - $deployed)) {
            throw new HolyOrderException("Not enough {$rank}s available to deploy.");
        }
    }

    private function grandMasterCharacterId(HolyOrder $order): ?int
    {
        $holdership = $order->currentMaster()->first();
        if ($holdership) {
            return (int) $holdership->holder_character_id;
        }

        $member = HolyOrderMembership::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->where('member_rank', HolyOrderMemberRank::GRAND_MASTER)
            ->first();

        return $member ? (int) $member->character_id : null;
    }
}
