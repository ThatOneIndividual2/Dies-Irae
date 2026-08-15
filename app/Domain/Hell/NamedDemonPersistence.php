<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\RespawnPolicy;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\ThreatWorld;
use InvalidArgumentException;

/**
 * Named demons are persistent identities. Pulse never respawns them.
 */
final class NamedDemonPersistence
{
    public function __construct(private TaxonomyCatalog $catalog)
    {
    }

    public function spawnLatent(ThreatWorld $world, string $instanceId, string $catalogKey, ?string $factionKey = null): NamedDemonRecord
    {
        $row = $this->catalog->named($catalogKey);
        foreach ($world->namedDemons as $existing) {
            if ($existing->catalogKey === $catalogKey && $existing->status !== NamedDemonStatus::DESTROYED) {
                throw new InvalidArgumentException("Named demon {$catalogKey} already exists in this world");
            }
        }

        $record = new NamedDemonRecord($instanceId, $catalogKey);
        $record->status = NamedDemonStatus::LATENT;
        $record->respawnPolicy = $this->catalog->respawnPolicy($catalogKey);
        $record->factionKey = $factionKey;
        $record->returnMinApocalypse = (int) ($row['return_min_apocalypse'] ?? 100);
        $this->copyCatalogIdentity($record, $row);
        $world->namedDemons[$instanceId] = $record;

        return $record;
    }

    public function manifest(ThreatWorld $world, string $demonId, string $territoryId): NamedDemonRecord
    {
        $demon = $this->require($world, $demonId);
        if ($demon->status === NamedDemonStatus::DESTROYED) {
            throw new InvalidArgumentException("Destroyed named demon {$demonId} cannot manifest");
        }
        $demon->status = NamedDemonStatus::MANIFESTED;
        $demon->territoryId = $territoryId;

        return $demon;
    }

    public function bindToHost(ThreatWorld $world, string $demonId, string $characterId): NamedDemonRecord
    {
        $demon = $this->require($world, $demonId);
        if ($demon->status === NamedDemonStatus::DESTROYED) {
            throw new InvalidArgumentException("Destroyed named demon {$demonId} cannot bind");
        }
        $demon->status = NamedDemonStatus::BOUND;
        $demon->hostCharacterId = $characterId;

        return $demon;
    }

    public function destroy(ThreatWorld $world, string $demonId, string $method): NamedDemonRecord
    {
        $demon = $this->require($world, $demonId);
        $demon->status = NamedDemonStatus::DESTROYED;
        $demon->destroyedOn = $world->date;
        $demon->territoryId = null;
        $demon->hostCharacterId = null;
        $demon->returnAuthorized = false;
        $demon->returnLoreReason = 'destroyed_by_'.$method;

        foreach ($world->possessions as $link) {
            if ($link->namedDemonId === $demonId) {
                $link->namedDemonId = null;
            }
        }

        return $demon;
    }

    /**
     * Explicit lore/state permission. Pulse must never call this.
     */
    public function permitReturn(
        ThreatWorld $world,
        string $demonId,
        string $loreReason,
        string $permissionKind
    ): NamedDemonRecord {
        $demon = $this->require($world, $demonId);
        if ($demon->status !== NamedDemonStatus::DESTROYED && $demon->status !== NamedDemonStatus::BANISHED) {
            throw new InvalidArgumentException("Return permission applies to destroyed or banished demons");
        }
        if (trim($loreReason) === '') {
            throw new InvalidArgumentException("Lore reason is required to permit a named demon return");
        }

        $policy = $demon->respawnPolicy;
        $allowed = match ($policy) {
            RespawnPolicy::NEVER => false,
            RespawnPolicy::LORE_ONLY => $permissionKind === 'lore',
            RespawnPolicy::APOCALYPSE_STAGE => $permissionKind === 'apocalypse_state'
                && $world->apocalypseIntensity >= $demon->returnMinApocalypse,
            RespawnPolicy::RITUAL => $permissionKind === 'ritual',
            default => false,
        };

        if (!$allowed) {
            throw new InvalidArgumentException("Respawn policy {$policy} refuses permission {$permissionKind}");
        }

        $demon->returnAuthorized = true;
        $demon->returnLoreReason = $loreReason;

        return $demon;
    }

    public function enactAuthorizedReturn(ThreatWorld $world, string $demonId, string $territoryId): NamedDemonRecord
    {
        $demon = $this->require($world, $demonId);
        if (!$demon->returnAuthorized) {
            throw new InvalidArgumentException("Named demon {$demonId} is not authorized to return");
        }

        $demon->status = NamedDemonStatus::MANIFESTED;
        $demon->territoryId = $territoryId;
        $demon->destroyedOn = null;
        $demon->returnAuthorized = false;

        return $demon;
    }

    /**
     * Safety: a pulse must leave destroyed demons destroyed.
     */
    public function refuseCasualRespawn(ThreatWorld $world): void
    {
        foreach ($world->namedDemons as $demon) {
            if ($demon->status === NamedDemonStatus::DESTROYED && $demon->territoryId !== null) {
                throw new \LogicException("Destroyed named demon {$demon->id} cannot occupy a territory");
            }
        }
    }

    private function require(ThreatWorld $world, string $demonId): NamedDemonRecord
    {
        if (!isset($world->namedDemons[$demonId])) {
            throw new InvalidArgumentException("Unknown named demon instance: {$demonId}");
        }

        return $world->namedDemons[$demonId];
    }

    private function copyCatalogIdentity(NamedDemonRecord $record, array $row): void
    {
        $record->trueName = (string) ($row['true_name'] ?? $row['name'] ?? $record->catalogKey);
        $record->publicAlias = (string) ($row['public_alias'] ?? $row['name'] ?? $record->catalogKey);
        $record->epithets = array_values($row['epithets'] ?? []);
        $record->titles = array_values($row['titles'] ?? []);
        $record->category = (string) ($row['category'] ?? $record->category);
        $record->strategy = (string) ($row['strategy'] ?? '');
        $record->hierarchy = (string) ($row['hierarchy'] ?? '');
        $record->liegeCatalogKey = $row['liege_catalog_key'] ?? null;
        $record->themes = array_values($row['themes'] ?? []);
        $record->channels = array_values($row['channels'] ?? []);
        $record->knownManifestations = array_values($row['known_manifestations'] ?? []);
        $record->hiddenTrueState = is_array($row['hidden_true_state'] ?? null) ? $row['hidden_true_state'] : [];
        $record->vulnerabilities = array_values($row['vulnerabilities'] ?? []);
        $record->physicalManifestMinApocalypse = (int) ($row['physical_manifest_min_apocalypse'] ?? 40);
        $record->physicalManifestForbiddenPhases = array_values($row['physical_manifest_forbidden_phases'] ?? ['ordinary']);
        if ($record->objectives === []) {
            foreach ($row['objectives'] ?? [] as $objective) {
                $record->objectives[] = [
                    'key' => (string) $objective['key'],
                    'aim' => (string) ($objective['aim'] ?? $objective['key']),
                    'status' => 'open',
                ];
            }
        }
        if ($record->rivalries === []) {
            foreach ($row['rivals'] ?? [] as $rival) {
                $record->rivalries[] = ['catalogKey' => (string) $rival, 'reason' => 'catalog'];
            }
        }
    }
}
