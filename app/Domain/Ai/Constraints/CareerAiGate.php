<?php

namespace App\Domain\Ai\Constraints;

use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

/**
 * Career life-path overlay. Silent unless Situation carries career posture.
 * Does not read hidden spiritual state.
 */
final class CareerAiGate
{
    public function reject(CandidateAction $action, Situation $situation): ?string
    {
        $posture = $situation->careerPosture;
        if ($posture === null || $posture === 'ordinary') {
            return null;
        }

        if ($posture === 'captive' && $action->key !== 'wait') {
            return 'imprisoned career state forbids action';
        }

        if ($posture === 'withdrawn' && in_array($action->key, [
            'raise_levy',
            'press_claim',
            'call_bannermen',
            'arrange_heir_marriage',
            'secure_succession',
        ], true)) {
            return 'hermit will not take that action';
        }

        if (!$situation->careerAllowsSecularOffice && in_array($action->key, [
            'arrange_heir_marriage',
            'secure_succession',
        ], true)) {
            return 'vows or orders forbid secular marriage politics';
        }

        if ($posture === 'exiled_claimant' && $action->key === 'call_bannermen') {
            return 'an exile cannot summon the realm';
        }

        return null;
    }
}
