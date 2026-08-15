<?php

namespace App\Domain\Hell\State;

use App\Domain\Hell\Enums\HostKind;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\Enums\NamedDemonStatus;

/**
 * In-memory hell overlay for one world. Legal titles, realms, and sees are not stored here.
 */
final class ThreatWorld
{
    public int $worldId = 1;
    public string $seed = 'dies-irae-test';
    public string $date = '1348-01-01';
    public int $apocalypseIntensity = 0;
    public string $apocalypsePhaseKey = 'ordinary';

    /** @var array<string, TerritoryThreat> */
    public array $territories = [];

    /** @var array<string, list<string>> */
    public array $adjacencies = [];

    /** @var array<string, NamedDemonRecord> */
    public array $namedDemons = [];

    /** @var array<string, CultCell> */
    public array $cults = [];

    /** @var array<string, Breach> */
    public array $breaches = [];

    /** @var array<string, DemonicHost> */
    public array $hosts = [];

    /** @var array<string, CorruptedArmy> */
    public array $armies = [];

    /** @var array<string, PossessionLink> */
    public array $possessions = [];

    /** @var array<string, DemonicFactionRecord> */
    public array $factions = [];

    public function territory(string $id): TerritoryThreat
    {
        if (!isset($this->territories[$id])) {
            throw new \InvalidArgumentException("Unknown territory: {$id}");
        }

        return $this->territories[$id];
    }

    public function addTerritory(TerritoryThreat $territory): void
    {
        $this->territories[$territory->id] = $territory;
    }

    public function neighbors(string $territoryId): array
    {
        return $this->adjacencies[$territoryId] ?? [];
    }

    public function link(string $a, string $b): void
    {
        $this->adjacencies[$a] = $this->adjacencies[$a] ?? [];
        $this->adjacencies[$b] = $this->adjacencies[$b] ?? [];
        if (!in_array($b, $this->adjacencies[$a], true)) {
            $this->adjacencies[$a][] = $b;
        }
        if (!in_array($a, $this->adjacencies[$b], true)) {
            $this->adjacencies[$b][] = $a;
        }
    }

    /** @return list<NamedDemonRecord> */
    public function namedDemonsIn(string $territoryId): array
    {
        $out = [];
        foreach ($this->namedDemons as $demon) {
            if ($demon->territoryId === $territoryId && NamedDemonStatus::isPresentOnMap($demon->status)) {
                $out[] = $demon;
            }
        }

        return $out;
    }

    public function openBreachIn(string $territoryId): ?Breach
    {
        foreach ($this->breaches as $breach) {
            if ($breach->territoryId === $territoryId && $breach->open) {
                return $breach;
            }
        }

        return null;
    }

    /** @return list<DemonicHost> */
    public function livingHostsIn(string $territoryId): array
    {
        $out = [];
        foreach ($this->hosts as $host) {
            if ($host->territoryId === $territoryId && !$host->collapsed && $host->kind === HostKind::DEMONIC_HOST) {
                $out[] = $host;
            }
        }

        return $out;
    }

    /** @return list<CorruptedArmy> */
    public function livingArmiesIn(string $territoryId): array
    {
        $out = [];
        foreach ($this->armies as $army) {
            if ($army->territoryId === $territoryId && !$army->cleansed) {
                $out[] = $army;
            }
        }

        return $out;
    }

    public function cultActivityIn(string $territoryId): int
    {
        $sum = 0;
        foreach ($this->cults as $cult) {
            if ($cult->territoryId === $territoryId && !$cult->destroyed) {
                $sum += $cult->activity;
            }
        }

        return min(100, $sum);
    }

    public function hostStrengthIn(string $territoryId): int
    {
        $sum = 0;
        foreach ($this->livingHostsIn($territoryId) as $host) {
            $sum += $host->strength;
        }

        return $sum;
    }

    public function highestNamedRankIn(string $territoryId, callable $rankOf): int
    {
        $best = 0;
        foreach ($this->namedDemonsIn($territoryId) as $demon) {
            $best = max($best, (int) $rankOf($demon->catalogKey));
        }

        return $best;
    }

    public function hasPrinceOrNamedIn(string $territoryId, callable $isPrince): bool
    {
        foreach ($this->namedDemonsIn($territoryId) as $demon) {
            if ($isPrince($demon->catalogKey)) {
                return true;
            }
        }

        return false;
    }

    public function territoryIdsSorted(): array
    {
        $ids = array_keys($this->territories);
        sort($ids, SORT_STRING);

        return $ids;
    }

    public function fingerprint(): string
    {
        $payload = $this->toArray();
        $this->ksortRecursive($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    public function toArray(): array
    {
        return [
            'worldId' => $this->worldId,
            'seed' => $this->seed,
            'date' => $this->date,
            'apocalypseIntensity' => $this->apocalypseIntensity,
            'apocalypsePhaseKey' => $this->apocalypsePhaseKey,
            'territories' => array_map(fn (TerritoryThreat $t) => $t->toArray(), $this->territories),
            'adjacencies' => $this->adjacencies,
            'namedDemons' => array_map(fn (NamedDemonRecord $n) => $n->toArray(), $this->namedDemons),
            'cults' => array_map(fn (CultCell $c) => $c->toArray(), $this->cults),
            'breaches' => array_map(fn (Breach $b) => $b->toArray(), $this->breaches),
            'hosts' => array_map(fn (DemonicHost $h) => $h->toArray(), $this->hosts),
            'armies' => array_map(fn (CorruptedArmy $a) => $a->toArray(), $this->armies),
            'possessions' => array_map(fn (PossessionLink $p) => $p->toArray(), $this->possessions),
            'factions' => array_map(fn (DemonicFactionRecord $f) => $f->toArray(), $this->factions),
        ];
    }

    public static function fromArray(array $row): self
    {
        $w = new self();
        $w->worldId = (int) ($row['worldId'] ?? 1);
        $w->seed = (string) ($row['seed'] ?? 'dies-irae-test');
        $w->date = (string) ($row['date'] ?? '1348-01-01');
        $w->apocalypseIntensity = (int) ($row['apocalypseIntensity'] ?? 0);
        $w->apocalypsePhaseKey = (string) ($row['apocalypsePhaseKey'] ?? 'ordinary');
        $w->adjacencies = $row['adjacencies'] ?? [];

        foreach ($row['territories'] ?? [] as $t) {
            $entity = TerritoryThreat::fromArray($t);
            $w->territories[$entity->id] = $entity;
        }
        foreach ($row['namedDemons'] ?? [] as $n) {
            $entity = NamedDemonRecord::fromArray($n);
            $w->namedDemons[$entity->id] = $entity;
        }
        foreach ($row['cults'] ?? [] as $c) {
            $entity = CultCell::fromArray($c);
            $w->cults[$entity->id] = $entity;
        }
        foreach ($row['breaches'] ?? [] as $b) {
            $entity = Breach::fromArray($b);
            $w->breaches[$entity->id] = $entity;
        }
        foreach ($row['hosts'] ?? [] as $h) {
            $entity = DemonicHost::fromArray($h);
            $w->hosts[$entity->id] = $entity;
        }
        foreach ($row['armies'] ?? [] as $a) {
            $entity = CorruptedArmy::fromArray($a);
            $w->armies[$entity->id] = $entity;
        }
        foreach ($row['possessions'] ?? [] as $p) {
            $entity = PossessionLink::fromArray($p);
            $w->possessions[$entity->id] = $entity;
        }
        foreach ($row['factions'] ?? [] as $f) {
            $entity = DemonicFactionRecord::fromArray($f);
            $w->factions[$entity->key] = $entity;
        }

        return $w;
    }

    public function duplicate(): self
    {
        return self::fromArray($this->toArray());
    }

    public function assertNotARealm(): void
    {
        foreach ($this->factions as $faction) {
            $vars = $faction->toArray();
            if (isset($vars['realmId']) || isset($vars['liegeId']) || isset($vars['dynastyId'])) {
                throw new \LogicException('Demonic faction must not carry realm, liege, or dynasty identity');
            }
        }
    }

    public function legalTitles(): array
    {
        $out = [];
        foreach ($this->territories as $t) {
            $out[$t->id] = $t->legalTitleId;
        }

        return $out;
    }

    public function clampAll(): void
    {
        foreach ($this->territories as $t) {
            $t->cultActivity = max($t->cultActivity, $this->cultActivityIn($t->id));
            $t->clamp();
            if ($t->incursionState === IncursionState::INFERNAL_STRONGHOLD && $t->strongholdFactionKey === null) {
                $t->strongholdFactionKey = $this->inferFaction($t->id);
            }
        }
    }

    private function inferFaction(string $territoryId): ?string
    {
        foreach ($this->namedDemonsIn($territoryId) as $demon) {
            if ($demon->factionKey) {
                return $demon->factionKey;
            }
        }
        foreach ($this->livingHostsIn($territoryId) as $host) {
            return $host->factionKey;
        }

        return null;
    }

    private function ksortRecursive(array &$arr): void
    {
        ksort($arr);
        foreach ($arr as &$v) {
            if (is_array($v)) {
                $this->ksortRecursive($v);
            }
        }
    }
}
