<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\DemonCategory;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\State\TerritoryThreat;
use App\Domain\Hell\State\ThreatWorld;
use InvalidArgumentException;

final class IncursionLifecycle
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(array $config)
    {
        if (empty($config['order']) || empty($config['advance'])) {
            throw new InvalidArgumentException('Incursion state config requires order and advance');
        }
        $this->config = $config;
    }

    public static function load(string $path): self
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException("Invalid incursion config: {$path}");
        }

        return new self($decoded);
    }

    public function effects(string $state): array
    {
        return $this->config['effects'][$state] ?? [];
    }

    public function evaluate(ThreatWorld $world, TerritoryThreat $territory, TaxonomyCatalog $catalog): ?string
    {
        $context = $this->context($world, $territory, $catalog);

        foreach ($this->config['advance'] as $rule) {
            if (($rule['from'] ?? '') !== $territory->incursionState) {
                continue;
            }
            if ($this->matches($rule, $territory, $context, $world->apocalypseIntensity)) {
                return (string) $rule['to'];
            }
        }

        return null;
    }

    public function applyEffects(TerritoryThreat $territory): void
    {
        $fx = $this->effects($territory->incursionState);
        if ($fx === []) {
            return;
        }

        $plagueAmp = (float) ($fx['plague_amp'] ?? 0);
        $popHit = (int) ($fx['population_delta'] ?? 0);
        if ($plagueAmp > 0 && $territory->plague > 0) {
            $popHit -= (int) floor($territory->plague * $plagueAmp / 4);
        }

        $territory->population += $popHit;
        $territory->morale += (int) ($fx['morale_delta'] ?? 0);
        $territory->despair += (int) ($fx['despair_delta'] ?? 0);
        $territory->corruption += (int) ($fx['corruption_delta'] ?? 0);
        $territory->production = (float) ($fx['production_mult'] ?? $territory->production);
        $territory->travelOpen = (bool) ($fx['travel_open'] ?? $territory->travelOpen);
        $territory->clergyPressure = TerritoryThreat::meter(
            $territory->clergyPressure + (int) ($fx['clergy_pressure'] ?? 0)
        );
        $territory->rulerTemptation = TerritoryThreat::meter(
            $territory->rulerTemptation + (int) ($fx['ruler_temptation'] ?? 0)
        );
        $territory->settlementCorrupted = (bool) ($fx['settlement_corrupted'] ?? $territory->settlementCorrupted);

        if ($territory->clergyPressure >= 80) {
            $territory->clergyPresence = max(0, $territory->clergyPresence - 1);
        }

        $territory->clamp();
    }

    public function neighborLeak(string $state): int
    {
        return (int) (($this->effects($state)['neighbor_leak'] ?? 0));
    }

    public function armyCorruption(string $state): int
    {
        return (int) (($this->effects($state)['army_corruption'] ?? 0));
    }

    /**
     * @return array<string, bool|int>
     */
    public function context(ThreatWorld $world, TerritoryThreat $territory, TaxonomyCatalog $catalog): array
    {
        $named = $world->namedDemonsIn($territory->id);
        $prince = false;
        foreach ($named as $demon) {
            $row = $catalog->entity($demon->catalogKey);
            if (in_array($row['category'], [DemonCategory::PRINCE, DemonCategory::NAMED_UNIQUE], true)) {
                $prince = true;
            }
        }

        $armies = $world->livingArmiesIn($territory->id);
        $armyCorrupted = false;
        foreach ($armies as $army) {
            if ($army->corruption >= 40) {
                $armyCorrupted = true;
                break;
            }
        }

        return [
            'named_demon_present' => $named !== [],
            'local_manifestation_high' => $territory->localManifestation >= 35,
            'breach_open' => $world->openBreachIn($territory->id) !== null,
            'host_present' => $world->livingHostsIn($territory->id) !== [],
            'army_corrupted' => $armyCorrupted,
            'prince_or_named_present' => $prince,
        ];
    }

    private function matches(array $rule, TerritoryThreat $territory, array $context, int $apocalypse): bool
    {
        if ($apocalypse < (int) ($rule['min_apocalypse'] ?? 0)) {
            return false;
        }

        if (isset($rule['morale_at_most']) && $territory->morale > (int) $rule['morale_at_most']) {
            return false;
        }

        foreach ($rule['all_flags'] ?? [] as $flag) {
            if (empty($context[$flag])) {
                return false;
            }
        }

        $all = $rule['all_thresholds'] ?? [];
        foreach ($all as $meter => $min) {
            if ($this->meter($territory, $meter) < (int) $min) {
                return false;
            }
        }

        $anyFlags = $rule['any_flags'] ?? [];
        $anyThresholds = $rule['any_thresholds'] ?? [];

        if ($anyFlags === [] && $anyThresholds === []) {
            return true;
        }

        foreach ($anyFlags as $flag) {
            if (!empty($context[$flag])) {
                return true;
            }
        }

        foreach ($anyThresholds as $meter => $min) {
            $value = $this->meter($territory, $meter);
            if ($meter === 'morale') {
                if ($value <= (int) $min) {
                    return true;
                }
                continue;
            }
            if ($value >= (int) $min) {
                return true;
            }
        }

        return $anyFlags === [] && $anyThresholds === [];
    }

    private function meter(TerritoryThreat $territory, string $name): int
    {
        return match ($name) {
            'corruption' => $territory->corruption,
            'cult_activity' => $territory->cultActivity,
            'despair' => $territory->despair,
            'local_manifestation' => $territory->localManifestation,
            'morale' => $territory->morale,
            'plague' => $territory->plague,
            default => 0,
        };
    }

    public function canReach(string $state, int $apocalypse): bool
    {
        foreach ($this->config['advance'] as $rule) {
            if (($rule['to'] ?? '') === $state) {
                return $apocalypse >= (int) ($rule['min_apocalypse'] ?? 0);
            }
        }

        return $state === IncursionState::DORMANT || $state === IncursionState::TEMPTED;
    }
}
