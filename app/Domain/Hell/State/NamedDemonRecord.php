<?php

namespace App\Domain\Hell\State;

use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\ObjectiveStatus;
use App\Domain\Hell\Enums\RespawnPolicy;

final class NamedDemonRecord
{
    public string $id;
    public string $catalogKey;
    public string $status = NamedDemonStatus::LATENT;
    public string $respawnPolicy = RespawnPolicy::LORE_ONLY;
    public ?string $territoryId = null;
    public ?string $hostCharacterId = null;
    public ?string $factionKey = null;
    public ?string $destroyedOn = null;
    public bool $returnAuthorized = false;
    public ?string $returnLoreReason = null;
    public int $returnMinApocalypse = 100;

    public string $trueName = '';
    public string $publicAlias = '';
    public array $epithets = [];
    public array $titles = [];
    public string $category = 'named_unique';
    public string $strategy = '';
    public string $hierarchy = '';
    public ?string $liegeCatalogKey = null;
    public array $themes = [];
    public array $channels = [];
    public array $knownManifestations = [];
    public array $hiddenTrueState = [];
    public array $vulnerabilities = [];
    public int $physicalManifestMinApocalypse = 40;
    /** @var list<string> */
    public array $physicalManifestForbiddenPhases = ['ordinary'];
    /** @var list<array{key:string,aim:string,status:string}> */
    public array $objectives = [];
    /** @var list<array{id:string,kind:string,subjectId:string,imprisoned:bool}> */
    public array $servants = [];
    /** @var list<array{catalogKey:string,reason:string}> */
    public array $rivalries = [];
    /** @var array<string, int> territoryId => intensity */
    public array $territoryInfluence = [];
    /** @var list<array{on:string,channel:string,summary:string,payload:array}> */
    public array $acts = [];
    /** @var list<array{subjectType:string,subjectId:string,reason:string,intensity:int}> */
    public array $grudges = [];
    /** @var array<string, list<array{facet:string,source:string,learnedOn:string}>> observerId => revelations */
    public array $knowledge = [];

    public function __construct(string $id, string $catalogKey)
    {
        $this->id = $id;
        $this->catalogKey = $catalogKey;
    }

    public static function fromArray(array $row): self
    {
        $n = new self((string) $row['id'], (string) $row['catalogKey']);
        foreach (get_object_vars($n) as $field => $default) {
            if ($field === 'id' || $field === 'catalogKey') {
                continue;
            }
            if (array_key_exists($field, $row)) {
                $n->{$field} = $row[$field];
            }
        }

        return $n;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function objective(string $key): ?array
    {
        foreach ($this->objectives as $row) {
            if (($row['key'] ?? '') === $key) {
                return $row;
            }
        }

        return null;
    }

    public function setObjectiveStatus(string $key, string $status): void
    {
        foreach ($this->objectives as $i => $row) {
            if (($row['key'] ?? '') === $key) {
                $this->objectives[$i]['status'] = $status;

                return;
            }
        }
    }

    public function knows(string $observerId, string $facet): bool
    {
        foreach ($this->knowledge[$observerId] ?? [] as $row) {
            if (($row['facet'] ?? '') === $facet) {
                return true;
            }
        }

        return false;
    }

    public function openObjectives(): array
    {
        return array_values(array_filter(
            $this->objectives,
            fn (array $row) => ($row['status'] ?? ObjectiveStatus::OPEN) === ObjectiveStatus::OPEN
                || ($row['status'] ?? '') === ObjectiveStatus::ADVANCED
        ));
    }
}
