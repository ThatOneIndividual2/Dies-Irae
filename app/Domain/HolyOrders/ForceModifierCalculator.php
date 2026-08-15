<?php

namespace App\Domain\HolyOrders;

use App\Domain\Enums\HolyOrderAllegiance;
use App\Domain\Enums\HolyOrderLegitimacy;
use App\Domain\Enums\HolyOrderMemberRank;
use App\Domain\Enums\HolyOrderMissionType;
use App\Domain\Enums\HolyOrderPapalRecognition;
use App\Domain\Enums\HolyOrderStatus;
use App\Domain\Enums\HolyOrderVowType;
use App\Domain\Enums\RelicAuthenticity;
use App\Models\HolyOrder;
use App\Models\HolyOrderMembership;
use App\Models\RelicCustody;

final class ForceModifierCalculator
{
    /**
     * @param array{knights?:int,sergeants?:int,chaplains?:int} $composition
     * @return array{quality_modifier: float, breakdown: array<string, float>}
     */
    public function calculate(HolyOrder $order, array $composition = [], ?string $targetKind = null): array
    {
        $mods = config('holy_orders.modifiers');
        $breakdown = [];

        $breakdown['vows'] = $this->vowIntegrity($order) * (float) $mods['vow_integrity'];
        $breakdown['morale'] = ((int) $order->morale / 100) * (float) $mods['morale'];
        $breakdown['relics'] = $this->relicBonus($order, $mods);
        $breakdown['chaplains'] = ((int) ($composition['chaplains'] ?? 0) > 0)
            ? (float) $mods['chaplain_presence']
            : 0.0;
        $breakdown['consecration'] = ((int) $order->consecration / 100) * (float) $mods['consecration'];
        $breakdown['leadership'] = $this->hasLeadership($order) ? (float) $mods['leadership'] : 0.0;
        $breakdown['reputation'] = ((int) $order->reputation / 100) * (float) $mods['reputation'];
        $breakdown['corruption_resistance'] = $this->isDemonTarget($targetKind)
            ? ((int) $order->corruption_resistance / 100) * (float) $mods['corruption_resistance_vs_demon']
            : 0.0;

        $breakdown['corruption'] = ((int) $order->corruption / 100) * (float) $mods['corruption_penalty'];
        $breakdown['fractured_legitimacy'] = HolyOrderLegitimacy::isFractured((string) $order->legitimacy)
            ? (float) $mods['fractured_legitimacy']
            : 0.0;
        $breakdown['lost_recognition'] = HolyOrderPapalRecognition::isLost((string) $order->papal_recognition_status)
            ? (float) $mods['lost_recognition']
            : 0.0;
        $breakdown['schism'] = $this->hasOpenSchism($order) ? (float) $mods['schism'] : 0.0;
        $breakdown['excommunication'] = $order->status === HolyOrderStatus::EXCOMMUNICATED
            ? (float) $mods['excommunication']
            : 0.0;
        $breakdown['political_obligation'] = in_array($order->allegiance_type, [
            HolyOrderAllegiance::PAPAL,
            HolyOrderAllegiance::ROYAL,
        ], true) ? (float) $mods['political_obligation'] : 0.0;
        $breakdown['understrength'] = $this->isUnderstrength($order) ? (float) $mods['understrength'] : 0.0;

        $total = 1.0;
        foreach ($breakdown as $delta) {
            $total += $delta;
        }

        $total = max((float) $mods['floor'], min((float) $mods['ceiling'], $total));

        return [
            'quality_modifier' => round($total, 3),
            'breakdown' => $breakdown,
        ];
    }

    private function vowIntegrity(HolyOrder $order): float
    {
        $members = HolyOrderMembership::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->with('currentVows')
            ->get();

        if ($members->isEmpty()) {
            return 0.0;
        }

        $kept = 0;
        $required = HolyOrderVowType::militaryRule();
        foreach ($members as $member) {
            $types = $member->currentVows
                ->where('integrity', 'kept')
                ->pluck('vow_type')
                ->all();
            $missing = array_diff($required, $types);
            if ($missing === []) {
                $kept++;
            }
        }

        return $kept / $members->count();
    }

    /**
     * @param array<string, mixed> $mods
     */
    private function relicBonus(HolyOrder $order, array $mods): float
    {
        $custodies = RelicCustody::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->with('relic')
            ->get();

        if ($custodies->isEmpty()) {
            return 0.0;
        }

        foreach ($custodies as $custody) {
            if ($custody->relic && $custody->relic->authenticity === RelicAuthenticity::RECOGNIZED) {
                return (float) $mods['recognized_relic'];
            }
        }

        return (float) $mods['unrecognized_relic'];
    }

    private function hasLeadership(HolyOrder $order): bool
    {
        if ($order->grand_master_office_id) {
            $held = $order->currentMaster()->exists();
            if ($held) {
                return true;
            }
        }

        return HolyOrderMembership::query()
            ->where('holy_order_id', $order->id)
            ->where('is_current', true)
            ->where('member_rank', HolyOrderMemberRank::GRAND_MASTER)
            ->exists();
    }

    private function hasOpenSchism(HolyOrder $order): bool
    {
        if ($order->schism_status && $order->schism_status !== 'none') {
            return true;
        }

        return $order->currentSchism()->exists();
    }

    private function isDemonTarget(?string $targetKind): bool
    {
        return in_array($targetKind, [
            HolyOrderMissionType::FIGHT_DEMONS,
            'demons',
            'demon',
        ], true);
    }

    private function isUnderstrength(HolyOrder $order): bool
    {
        $cap = max(1, (int) $order->manpower_cap);

        return (int) $order->manpower_current < (int) floor($cap * 0.5);
    }
}
