<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\CountermeasureKind;
use App\Domain\Hell\Enums\CountermeasureResult;
use App\Domain\Hell\Enums\HellEventType;
use App\Domain\Hell\Enums\HostKind;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\Enums\PossessionStage;
use App\Domain\Hell\State\ThreatEvent;
use App\Domain\Hell\State\ThreatWorld;
use InvalidArgumentException;

final class CountermeasureResolver
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(array $config, private DeterministicRng $rng)
    {
        $this->config = $config;
    }

    public static function load(string $path, DeterministicRng $rng): self
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException("Invalid countermeasure config: {$path}");
        }

        return new self($decoded, $rng);
    }

    public function resolve(ThreatWorld $world, CountermeasureAttempt $attempt, TaxonomyCatalog $catalog): CountermeasureOutcome
    {
        if (!in_array($attempt->kind, CountermeasureKind::all(), true)) {
            throw new InvalidArgumentException("Unknown countermeasure: {$attempt->kind}");
        }

        $profile = $this->config['kinds'][$attempt->kind] ?? null;
        if (!is_array($profile)) {
            throw new InvalidArgumentException("No profile for {$attempt->kind}");
        }

        $this->assertRequirements($profile, $attempt);

        $score = $this->score($world, $attempt, $profile);
        $result = $this->band($score, (bool) ($profile['backlash'] ?? false));

        $territory = $world->territory($attempt->territoryId);
        $from = $territory->incursionState;
        $outcome = new CountermeasureOutcome($attempt->kind, $result, $score, $attempt->territoryId);
        $outcome->fromState = $from;
        $outcome->applied['factors'] = $attempt->factors;

        if ($result === CountermeasureResult::FAILURE) {
            $outcome->toState = $from;
            $outcome->events[] = $this->event($attempt, $result, $score);

            return $outcome;
        }

        if ($result === CountermeasureResult::BACKLASH) {
            $territory->incursionState = IncursionState::shift($from, 1);
            $territory->corruption = min(100, $territory->corruption + 12);
            $territory->despair = min(100, $territory->despair + 8);
            $territory->localManifestation = min(100, $territory->localManifestation + 10);
            $this->worsenPossession($world, $attempt, 1);
            $outcome->toState = $territory->incursionState;
            $outcome->applied['backlash'] = true;
            $outcome->events[] = $this->event($attempt, $result, $score);

            return $outcome;
        }

        $success = $result === CountermeasureResult::SUCCESS;
        $this->applyProfile($world, $attempt, $profile, $success, $catalog);
        $outcome->toState = $territory->incursionState;
        $outcome->breachClosed = !empty($outcome->applied['breach_closed']) || ($success && !empty($profile['closes_breach_on_success']) && $world->openBreachIn($territory->id) === null);
        $outcome->characterDied = $success && !empty($profile['costs_character_life']);
        $outcome->events[] = $this->event($attempt, $result, $score);
        $outcome->applied = array_merge($outcome->applied, [
            'from' => $from,
            'to' => $territory->incursionState,
        ]);

        return $outcome;
    }

    private function score(ThreatWorld $world, CountermeasureAttempt $attempt, array $profile): float
    {
        $support = 0.0;
        $oppose = 0.0;

        foreach ($profile['supporting'] ?? [] as $factor => $weight) {
            $support += $this->normalized($attempt->factors[$factor] ?? 0) * (float) $weight;
        }
        foreach ($profile['opposing'] ?? [] as $factor => $weight) {
            $oppose += $this->normalized($attempt->factors[$factor] ?? 0) * (float) $weight;
        }

        $base = $support / ($support + $oppose + 0.0001);
        $noiseAmp = (float) ($this->config['noise'] ?? 0.03);
        $noise = ($this->rng->float(
            $world->seed,
            $world->worldId,
            'hell_countermeasure',
            $world->date,
            $attempt->kind.'|'.$attempt->territoryId.'|'.($attempt->actorCharacterId ?? '')
        ) - 0.5) * 2 * $noiseAmp;

        return max(0.0, min(1.0, $base + $noise));
    }

    private function band(float $score, bool $allowBacklash): string
    {
        $bands = $this->config['bands'] ?? [];
        $success = (float) ($bands['success'] ?? 0.72);
        $partial = (float) ($bands['partial'] ?? 0.48);
        $failure = (float) ($bands['failure'] ?? 0.28);

        if ($score >= $success) {
            return CountermeasureResult::SUCCESS;
        }
        if ($score >= $partial) {
            return CountermeasureResult::PARTIAL;
        }
        if ($score >= $failure || !$allowBacklash) {
            return CountermeasureResult::FAILURE;
        }

        return CountermeasureResult::BACKLASH;
    }

    private function applyProfile(
        ThreatWorld $world,
        CountermeasureAttempt $attempt,
        array $profile,
        bool $success,
        TaxonomyCatalog $catalog
    ): void {
        $territory = $world->territory($attempt->territoryId);
        $suffix = $success ? '_success' : '_partial';

        $shift = (int) ($profile['state_shift'.$suffix] ?? $profile['state_shift_partial'] ?? 0);
        if ($shift !== 0) {
            $territory->incursionState = IncursionState::shift($territory->incursionState, $shift);
        }

        $territory->corruption += (int) ($profile['corruption_delta'.$suffix] ?? 0);
        $territory->despair += (int) ($profile['despair_delta'.$suffix] ?? 0);
        $territory->morale += (int) ($profile['morale_delta'.$suffix] ?? 0);

        if (isset($profile['cult_delta'.$suffix])) {
            $delta = (int) $profile['cult_delta'.$suffix];
            $this->adjustCults($world, $attempt, $delta);
            $territory->cultActivity = $world->cultActivityIn($territory->id);
        }

        if ($success && !empty($profile['closes_breach_on_success'])) {
            $this->closeBreach($world, $attempt);
        }

        if ($success && !empty($profile['army_cleanse_success'])) {
            $this->cleanseArmies($world, $attempt);
        }

        $posShift = (int) ($profile['possession_shift'.$suffix] ?? 0);
        if ($posShift !== 0) {
            $this->worsenPossession($world, $attempt, $posShift);
        }

        if ($success && $attempt->kind === CountermeasureKind::MILITARY_CLEANSING) {
            foreach ($world->livingHostsIn($territory->id) as $host) {
                $host->strength = max(0, $host->strength - 8);
                if ($host->strength === 0) {
                    $host->collapsed = true;
                }
            }
        }

        if ($territory->incursionState === IncursionState::DORMANT) {
            $territory->settlementCorrupted = false;
            $territory->strongholdFactionKey = null;
        }

        unset($catalog);
        $territory->clamp();
    }

    private function adjustCults(ThreatWorld $world, CountermeasureAttempt $attempt, int $delta): void
    {
        foreach ($world->cults as $cult) {
            if ($cult->destroyed || $cult->territoryId !== $attempt->territoryId) {
                continue;
            }
            if ($attempt->targetCultId && $cult->id !== $attempt->targetCultId) {
                continue;
            }
            $cult->activity = max(0, min(100, $cult->activity + $delta));
            if ($cult->activity === 0) {
                $cult->destroyed = true;
            }
        }
    }

    private function closeBreach(ThreatWorld $world, CountermeasureAttempt $attempt): void
    {
        $breach = $attempt->targetBreachId
            ? ($world->breaches[$attempt->targetBreachId] ?? null)
            : $world->openBreachIn($attempt->territoryId);

        if ($breach === null || !$breach->open) {
            return;
        }

        $breach->open = false;
        $breach->closedOn = $world->date;
        $world->territory($attempt->territoryId)->breachId = null;

        foreach ($world->hosts as $host) {
            if ($host->boundBreachId === $breach->id && $host->kind === HostKind::DEMONIC_HOST) {
                $stronghold = $world->territory($host->territoryId)->incursionState === IncursionState::INFERNAL_STRONGHOLD;
                if (!$stronghold) {
                    $host->collapsed = true;
                }
            }
        }
    }

    private function cleanseArmies(ThreatWorld $world, CountermeasureAttempt $attempt): void
    {
        foreach ($world->armies as $army) {
            if ($army->cleansed || $army->territoryId !== $attempt->territoryId) {
                continue;
            }
            if ($attempt->targetArmyId && $army->id !== $attempt->targetArmyId) {
                continue;
            }
            $army->corruption = max(0, $army->corruption - 50);
            if ($army->corruption === 0) {
                $army->cleansed = true;
            }
        }
    }

    private function worsenPossession(ThreatWorld $world, CountermeasureAttempt $attempt, int $delta): void
    {
        foreach ($world->possessions as $link) {
            if ($attempt->targetCharacterId && $link->characterId !== $attempt->targetCharacterId) {
                continue;
            }
            if (!$attempt->targetCharacterId && $link->characterId !== $attempt->actorCharacterId) {
                continue;
            }
            $link->stage = PossessionStage::shift($link->stage, $delta);
            $link->intensity = max(0, min(100, $link->intensity + ($delta * 12)));
        }
    }

    private function assertRequirements(array $profile, CountermeasureAttempt $attempt): void
    {
        foreach ($profile['requires'] ?? [] as $req) {
            $ok = match ($req) {
                'clergy_present' => $this->truthy($attempt->factors['clergy_present'] ?? $attempt->factors['clergy_presence'] ?? 0),
                'relic_present' => $this->truthy($attempt->factors['relic_present'] ?? $attempt->factors['relic_authenticity'] ?? 0),
                'travel_open' => $this->truthy($attempt->factors['travel_open'] ?? true) && !$this->truthy($attempt->factors['travel_closed'] ?? false),
                'military_present' => $this->truthy($attempt->factors['military_present'] ?? $attempt->factors['military_force'] ?? 0),
                'breach_open' => $this->truthy($attempt->factors['breach_open'] ?? false),
                'martyr_character' => $this->truthy($attempt->factors['martyrdom_offered'] ?? false) || $attempt->actorCharacterId !== null,
                'holy_order_present' => $this->truthy($attempt->factors['holy_order_present'] ?? $attempt->factors['holy_order_support'] ?? 0),
                default => true,
            };
            if (!$ok) {
                throw new InvalidArgumentException("Countermeasure {$attempt->kind} missing requirement {$req}");
            }
        }
    }

    private function normalized($value): float
    {
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }
        $n = (float) $value;
        if ($n < 0) {
            return 0.0;
        }
        if ($n <= 1.0) {
            return $n;
        }

        return max(0.0, min(1.0, $n / 100.0));
    }

    private function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return (float) $value > 0;
    }

    private function event(CountermeasureAttempt $attempt, string $result, float $score): ThreatEvent
    {
        return new ThreatEvent(
            HellEventType::COUNTERMEASURE_RESOLVED,
            $attempt->territoryId,
            "{$attempt->kind} resolved as {$result}.",
            ['score' => $score, 'kind' => $attempt->kind, 'result' => $result]
        );
    }
}
