<?php

namespace App\Actions\Succession;

use App\Domain\Succession\HeirOrder;
use App\Domain\Succession\SuccessionEligibilityGate;
use App\Models\Character;
use App\Models\Title;

final class PreviewSuccession
{
    public function __construct(
        private HeirOrder $order,
        private SuccessionEligibilityGate $gate
    ) {
    }

    /**
     * @return array{law_key:string,outcome:string,heir:?Character,skipped:list<array{id:int,name:string,reason:string}>}
     */
    public function execute(Title $title, Character $holder): array
    {
        $lawKey = $title->successionLaw?->key ?: HeirOrder::LAW_KEY;
        $skipped = [];
        $heir = null;

        foreach ($this->order->candidates($holder) as $candidate) {
            $reason = $this->gate->ineligibilityReason($candidate, $title);
            if ($reason !== null) {
                $skipped[] = [
                    'id' => (int) $candidate->id,
                    'name' => $candidate->displayName(),
                    'reason' => $reason,
                ];
                continue;
            }
            $heir = $candidate;
            break;
        }

        return [
            'law_key' => $lawKey,
            'outcome' => $heir ? 'heir' : 'vacant',
            'heir' => $heir,
            'skipped' => $skipped,
        ];
    }
}
