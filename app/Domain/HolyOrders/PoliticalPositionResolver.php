<?php

namespace App\Domain\HolyOrders;

use App\Domain\Enums\HolyOrderAllegiance;
use App\Domain\Enums\HolyOrderPapalRecognition;
use App\Domain\Enums\HolyOrderStatus;
use App\Models\HolyOrder;

final class PoliticalPositionResolver
{
    /**
     * @return array{
     *   allegiance: string,
     *   serves_pope: bool,
     *   serves_king: bool,
     *   independent: bool,
     *   controversial: bool,
     *   holds_land_in_secular_realm: bool,
     *   politically_obligated: bool
     * }
     */
    public function resolve(HolyOrder $order): array
    {
        $servesPope = $order->papal_recognition_status === HolyOrderPapalRecognition::RECOGNIZED
            || $order->allegiance_type === HolyOrderAllegiance::PAPAL;
        $servesKing = $order->currentPatronage()->exists()
            || $order->allegiance_type === HolyOrderAllegiance::ROYAL;
        $independent = !$servesPope && !$servesKing
            && $order->allegiance_type === HolyOrderAllegiance::INDEPENDENT;

        return [
            'allegiance' => (string) $order->allegiance_type,
            'serves_pope' => $servesPope,
            'serves_king' => $servesKing,
            'independent' => $independent,
            'controversial' => (bool) $order->controversial
                || $order->status === HolyOrderStatus::CONTROVERSIAL,
            'holds_land_in_secular_realm' => $order->currentHoldings()->exists(),
            'politically_obligated' => $servesPope || $servesKing,
        ];
    }
}
