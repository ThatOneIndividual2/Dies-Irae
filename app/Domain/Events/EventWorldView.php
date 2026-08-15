<?php

namespace App\Domain\Events;

final class EventWorldView
{
    /**
     * @param  array<string, int>  $meters
     * @param  list<array<string, mixed>>  $hooks
     * @param  array<int, array<string, mixed>>  $territories
     * @param  array<int, array<string, mixed>>  $characters
     * @param  array<int, array<string, mixed>>  $dynasties
     * @param  array<int, array<string, mixed>>  $settlements
     * @param  array<int, array<string, mixed>>  $realms
     * @param  array<int, array<string, mixed>>  $sees
     * @param  array<int, array<string, mixed>>  $monasteries
     * @param  array<int, array<string, mixed>>  $armies
     * @param  array<int, array<string, mixed>>  $wars
     * @param  array<int, array<string, mixed>>  $cults
     * @param  array<int, array<string, mixed>>  $plagues
     * @param  array<int, array<string, mixed>>  $adjacencies  from_id => list of to_id
     * @param  array<string, string>  $cooldowns  "def|scope|id" => available_on
     * @param  list<array<string, mixed>>  $pendingEvents
     * @param  array<int, true>  $playerCharacterIds
     */
    public function __construct(
        public int $worldId,
        public string $date,
        public string $seed,
        public string $phaseKey,
        public int $phaseOrdinal,
        public int $pressure,
        public array $meters,
        public array $hooks,
        public array $territories,
        public array $characters,
        public array $dynasties,
        public array $settlements,
        public array $realms,
        public array $sees,
        public array $monasteries,
        public array $armies,
        public array $wars,
        public array $cults,
        public array $plagues,
        public array $adjacencies,
        public array $cooldowns,
        public array $pendingEvents,
        public array $playerCharacterIds
    ) {
    }

    public function hookIntensity(string $key, ?string $scopeType = null, ?int $scopeId = null): int
    {
        $best = 0;
        foreach ($this->hooks as $hook) {
            if (($hook['hook_key'] ?? '') !== $key) {
                continue;
            }
            $intensity = (int) ($hook['intensity'] ?? 0);
            if ($scopeType === null) {
                $best = max($best, $intensity);
                continue;
            }
            $hType = (string) ($hook['scope_type'] ?? 'world');
            $hId = $hook['scope_id'] === null ? null : (int) $hook['scope_id'];
            if ($hType === 'world') {
                $best = max($best, $intensity);
                continue;
            }
            if ($hType === $scopeType && $hId === $scopeId) {
                $best = max($best, $intensity);
            }
        }

        return $best;
    }

    public function hasHook(string $key, ?string $scopeType = null, ?int $scopeId = null): bool
    {
        return $this->hookIntensity($key, $scopeType, $scopeId) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function territory(int $id): array
    {
        return $this->territories[$id] ?? [];
    }

    public function cooldownOpen(string $definitionKey, string $scopeType, int $scopeId): bool
    {
        $keys = [
            $definitionKey.'|'.$scopeType.'|'.$scopeId,
            $definitionKey.'|world|'.$this->worldId,
            $definitionKey.'|*|*',
        ];
        foreach ($keys as $key) {
            if (! isset($this->cooldowns[$key])) {
                continue;
            }
            if ($this->cooldowns[$key] > $this->date) {
                return false;
            }
        }

        return true;
    }

    public function pendingCount(): int
    {
        $n = 0;
        foreach ($this->pendingEvents as $event) {
            if (($event['status'] ?? '') === 'awaiting_decision' || ($event['status'] ?? '') === 'scheduled') {
                $n++;
            }
        }

        return $n;
    }

    public function exclusivityBlocked(string $group, string $scopeType, int $scopeId, string $mode): bool
    {
        if ($group === '') {
            return false;
        }
        foreach ($this->pendingEvents as $event) {
            if (($event['exclusivity_group'] ?? '') !== $group) {
                continue;
            }
            $status = $event['status'] ?? '';
            if ($status !== 'awaiting_decision' && $status !== 'scheduled') {
                continue;
            }
            if ($mode === 'world') {
                return true;
            }
            if (($event['scope_type'] ?? '') === $scopeType && (int) ($event['scope_id'] ?? 0) === $scopeId) {
                return true;
            }
        }

        return false;
    }

    public function alreadyFired(string $definitionKey, string $scopeType, int $scopeId): bool
    {
        foreach ($this->pendingEvents as $event) {
            if (($event['definition_key'] ?? '') !== $definitionKey) {
                continue;
            }
            if (($event['scope_type'] ?? '') === $scopeType && (int) ($event['scope_id'] ?? 0) === $scopeId) {
                return true;
            }
        }

        return false;
    }

    public function isPlayer(int $characterId): bool
    {
        return isset($this->playerCharacterIds[$characterId]);
    }
}
