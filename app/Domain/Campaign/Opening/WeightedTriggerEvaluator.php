<?php

namespace App\Domain\Campaign\Opening;

final class WeightedTriggerEvaluator
{
    public function __construct(private OpeningTriggerCatalog $catalog)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function eligible(WorldConditionSnapshot $snap, array $cooldowns, array $firedOnce, string $archetype): array
    {
        $out = [];
        foreach ($this->catalog->all() as $trigger) {
            if (!$this->passes($trigger, $snap, $cooldowns, $firedOnce, $archetype)) {
                continue;
            }
            $trigger['_weight'] = $this->weight($trigger, $snap, $archetype);
            if ($trigger['_weight'] > 0) {
                $out[] = $trigger;
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function select(WorldConditionSnapshot $snap, CampaignRng $rng, array $cooldowns, array $firedOnce, string $archetype, string $date, int $limit): array
    {
        $eligible = $this->eligible($snap, $cooldowns, $firedOnce, $archetype);
        if ($eligible === []) {
            return [];
        }

        usort($eligible, function ($a, $b) use ($rng, $date) {
            return $rng->roll('order', $date, $a['key']) <=> $rng->roll('order', $date, $b['key']);
        });

        $chosen = [];
        foreach ($eligible as $trigger) {
            if (count($chosen) >= $limit) {
                break;
            }
            if ($rng->chance((int) $trigger['_weight'], 'fire', $date, $trigger['key'])) {
                $chosen[] = $trigger;
            }
        }

        return $chosen;
    }

    public function weight(array $trigger, WorldConditionSnapshot $snap, string $archetype): int
    {
        $weight = (int) ($trigger['base_weight'] ?? 0);
        $mods = $trigger['role_weight'] ?? [];
        if (isset($mods[$archetype])) {
            $weight = (int) round($weight * (float) $mods[$archetype]);
        }
        if ($trigger['family'] === 'first_manifestation') {
            $weight += min(20, max(0, $snap->monthsElapsed - 8));
        }
        if ($trigger['family'] === 'mass_death' && $snap->localPlagueIntensity >= 2) {
            $weight += 15;
        }
        if ($snap->monthsElapsed < 3 && in_array($trigger['family'], ['strange_omen', 'unexplained_violence', 'first_manifestation'], true)) {
            $weight = (int) floor($weight * 0.35);
        }

        return max(0, $weight);
    }

    private function passes(array $trigger, WorldConditionSnapshot $snap, array $cooldowns, array $firedOnce, string $archetype): bool
    {
        $roles = $trigger['roles'] ?? [];
        if ($roles && !in_array($archetype, $roles, true)) {
            return false;
        }
        if ($snap->monthsElapsed < (int) ($trigger['min_month'] ?? 0)) {
            return false;
        }
        if ($snap->monthsElapsed > (int) ($trigger['max_month'] ?? 36)) {
            return false;
        }
        if (!empty($trigger['once']) && in_array($trigger['family'], $firedOnce, true)) {
            return false;
        }
        $cool = (int) ($cooldowns[$trigger['family']] ?? 0);
        if ($cool > 0) {
            return false;
        }
        foreach ((array) ($trigger['requires'] ?? []) as $req) {
            if (!$snap->condition($req)) {
                return false;
            }
        }

        return true;
    }
}
