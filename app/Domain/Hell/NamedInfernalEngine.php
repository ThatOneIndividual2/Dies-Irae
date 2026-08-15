<?php

namespace App\Domain\Hell;

use App\Domain\Hell\Enums\ActChannel;
use App\Domain\Hell\Enums\DefeatKind;
use App\Domain\Hell\Enums\GrudgeSubjectType;
use App\Domain\Hell\Enums\KnowledgeFacet;
use App\Domain\Hell\Enums\KnowledgeSource;
use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\NamedDemonStrategy;
use App\Domain\Hell\Enums\ObjectiveStatus;
use App\Domain\Hell\Enums\PossessionStage;
use App\Domain\Hell\Enums\ServantKind;
use App\Domain\Hell\State\CultCell;
use App\Domain\Hell\State\NamedDemonRecord;
use App\Domain\Hell\State\PossessionLink;
use App\Domain\Hell\State\PublicDossier;
use App\Domain\Hell\State\ThreatWorld;
use InvalidArgumentException;

/**
 * Persistent named antagonists. Incursions do not require one of these.
 */
final class NamedInfernalEngine
{
    public function __construct(
        private TaxonomyCatalog $catalog,
        private NamedDemonPersistence $named
    ) {
    }

    public function spawnFromCatalog(ThreatWorld $world, string $instanceId, string $catalogKey, ?string $factionKey = null): NamedDemonRecord
    {
        $record = $this->named->spawnLatent($world, $instanceId, $catalogKey, $factionKey);
        $this->hydrateIdentity($record);

        return $record;
    }

    public function hydrateIdentity(NamedDemonRecord $record): void
    {
        $row = $this->catalog->named($record->catalogKey);
        $record->trueName = (string) ($row['true_name'] ?? $row['name'] ?? $record->catalogKey);
        $record->publicAlias = (string) ($row['public_alias'] ?? $row['name'] ?? $record->catalogKey);
        $record->epithets = array_values($row['epithets'] ?? []);
        $record->titles = array_values($row['titles'] ?? []);
        $record->category = (string) $row['category'];
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
                    'status' => ObjectiveStatus::OPEN,
                ];
            }
        }
        if ($record->rivalries === []) {
            foreach ($row['rivals'] ?? [] as $rival) {
                $record->rivalries[] = ['catalogKey' => (string) $rival, 'reason' => 'catalog'];
            }
        }
    }

    public function canPhysicallyManifest(ThreatWorld $world, NamedDemonRecord $demon): bool
    {
        if (in_array($demon->status, [NamedDemonStatus::DESTROYED, NamedDemonStatus::BANISHED], true)) {
            return false;
        }
        if ($world->apocalypseIntensity < $demon->physicalManifestMinApocalypse) {
            return false;
        }
        if (in_array($world->apocalypsePhaseKey, $demon->physicalManifestForbiddenPhases, true)) {
            return false;
        }

        return true;
    }

    public function actThrough(
        ThreatWorld $world,
        string $demonId,
        string $channel,
        array $target
    ): NamedDemonRecord {
        $demon = $this->require($world, $demonId);
        if (!in_array($channel, ActChannel::all(), true)) {
            throw new InvalidArgumentException("Unknown act channel {$channel}");
        }
        if ($demon->channels !== [] && !in_array($channel, $demon->channels, true)) {
            throw new InvalidArgumentException("{$demon->catalogKey} does not act through {$channel}");
        }
        if (in_array($demon->status, [NamedDemonStatus::DESTROYED, NamedDemonStatus::BANISHED], true)) {
            throw new InvalidArgumentException("Inactive named demon cannot act");
        }
        if (ActChannel::isPhysical($channel) && !$this->canPhysicallyManifest($world, $demon)) {
            throw new InvalidArgumentException("Physical manifestation is forbidden in this age");
        }

        $summary = $this->applyChannel($world, $demon, $channel, $target);
        $demon->acts[] = [
            'on' => $world->date,
            'channel' => $channel,
            'summary' => $summary,
            'payload' => $target,
        ];

        return $demon;
    }

    public function attemptPhysicalManifestation(ThreatWorld $world, string $demonId, string $territoryId): NamedDemonRecord
    {
        $demon = $this->require($world, $demonId);
        if (!$this->canPhysicallyManifest($world, $demon)) {
            throw new InvalidArgumentException("Physical manifestation is forbidden in this age");
        }

        return $this->named->manifest($world, $demonId, $territoryId);
    }

    public function revealKnowledge(
        ThreatWorld $world,
        string $demonId,
        string $observerId,
        string $facet,
        string $source
    ): NamedDemonRecord {
        $demon = $this->require($world, $demonId);
        if (!in_array($facet, KnowledgeFacet::all(), true)) {
            throw new InvalidArgumentException("Unknown knowledge facet {$facet}");
        }
        if (!in_array($source, KnowledgeSource::all(), true)) {
            throw new InvalidArgumentException("Unknown knowledge source {$source}");
        }
        if ($demon->knows($observerId, $facet)) {
            return $demon;
        }
        $demon->knowledge[$observerId] = $demon->knowledge[$observerId] ?? [];
        $demon->knowledge[$observerId][] = [
            'facet' => $facet,
            'source' => $source,
            'learnedOn' => $world->date,
        ];

        return $demon;
    }

    public function dossier(ThreatWorld $world, string $demonId, string $observerId): PublicDossier
    {
        $demon = $this->require($world, $demonId);
        $file = new PublicDossier();
        $file->demonId = $demon->id;
        $file->shownName = $demon->publicAlias !== '' ? $demon->publicAlias : 'an unnamed pressure';
        $file->knownManifestations = $demon->knownManifestations;
        $file->statusHeard = 'a rumor without a face';

        if ($demon->knows($observerId, KnowledgeFacet::TRUE_IDENTITY)) {
            $file->trueIdentityKnown = true;
            $file->shownName = $demon->trueName;
        }
        if ($demon->knows($observerId, KnowledgeFacet::RANK)) {
            $file->rank = $demon->hierarchy !== '' ? $demon->hierarchy : $demon->category;
        }
        if ($demon->knows($observerId, KnowledgeFacet::MOTIVES)) {
            $aims = array_map(fn (array $row) => $row['aim'] ?? $row['key'], $demon->objectives);
            $file->motives = implode('; ', $aims);
        }
        if ($demon->knows($observerId, KnowledgeFacet::LOCATION)) {
            $file->location = $demon->territoryId;
        }
        if ($demon->knows($observerId, KnowledgeFacet::VULNERABILITIES)) {
            $file->vulnerabilities = $demon->vulnerabilities;
        }
        if ($file->trueIdentityKnown || $demon->knows($observerId, KnowledgeFacet::RANK)) {
            $file->statusHeard = $demon->status;
        }

        return $file;
    }

    public function applyDefeat(ThreatWorld $world, string $demonId, string $kind, array $context = []): NamedDemonRecord
    {
        $demon = $this->require($world, $demonId);
        if (!in_array($kind, DefeatKind::all(), true)) {
            throw new InvalidArgumentException("Unknown defeat kind {$kind}");
        }
        if ($demon->status === NamedDemonStatus::DESTROYED) {
            throw new InvalidArgumentException("Destroyed named demon cannot be defeated again");
        }

        match ($kind) {
            DefeatKind::BANISHMENT => $this->banish($world, $demon),
            DefeatKind::SEVER_LOCAL_INFLUENCE => $this->severInfluence($demon, $context['territoryId'] ?? $demon->territoryId),
            DefeatKind::DESTROY_CULT_NETWORK => $this->destroyCults($world, $demon),
            DefeatKind::CLOSE_BREACH => $this->closeLinkedBreaches($world, $demon),
            DefeatKind::DEFEAT_MANIFESTATION, DefeatKind::BATTLEFIELD_DEFEAT => $this->unmanifest($demon),
            DefeatKind::IMPRISON_SERVANT => $this->imprisonServant($demon, (string) ($context['servantId'] ?? '')),
            DefeatKind::PREVENT_OBJECTIVE => $this->preventObjective($demon, (string) ($context['objectiveKey'] ?? '')),
            default => null,
        };

        $demon->acts[] = [
            'on' => $world->date,
            'channel' => $kind,
            'summary' => 'Opposed and driven back, not unmade.',
            'payload' => $context,
        ];

        return $demon;
    }

    public function rememberGrudge(
        ThreatWorld $world,
        string $demonId,
        string $subjectType,
        string $subjectId,
        string $reason,
        int $intensity = 1
    ): NamedDemonRecord {
        $demon = $this->require($world, $demonId);
        if (!in_array($subjectType, GrudgeSubjectType::all(), true)) {
            throw new InvalidArgumentException("Unknown grudge subject {$subjectType}");
        }
        foreach ($demon->grudges as $i => $row) {
            if ($row['subjectType'] === $subjectType && $row['subjectId'] === $subjectId) {
                $demon->grudges[$i]['intensity'] = min(100, (int) $row['intensity'] + $intensity);
                $demon->grudges[$i]['reason'] = $reason;

                return $demon;
            }
        }
        $demon->grudges[] = [
            'subjectType' => $subjectType,
            'subjectId' => $subjectId,
            'reason' => $reason,
            'intensity' => max(1, min(100, $intensity)),
        ];

        return $demon;
    }

    public function wouldTarget(NamedDemonRecord $demon, string $subjectType, string $subjectId): bool
    {
        foreach ($demon->grudges as $row) {
            if ($row['subjectType'] === $subjectType && $row['subjectId'] === $subjectId && (int) $row['intensity'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strategy flavor on pulse. Latent named demons with no foothold do nothing.
     * Unnamed incursions never enter this method.
     */
    public function pulseNamed(ThreatWorld $world): void
    {
        foreach ($world->namedDemons as $demon) {
            if (in_array($demon->status, [NamedDemonStatus::DESTROYED, NamedDemonStatus::BANISHED], true)) {
                continue;
            }
            if ($demon->territoryInfluence === [] && $demon->servants === [] && $demon->territoryId === null) {
                continue;
            }
            $focus = $demon->territoryId ?? array_key_first($demon->territoryInfluence);
            if ($focus === null || !isset($world->territories[$focus])) {
                continue;
            }
            $t = $world->territory($focus);
            match ($demon->strategy) {
                NamedDemonStrategy::CORRUPTER => $t->rulerTemptation = min(100, $t->rulerTemptation + 4),
                NamedDemonStrategy::CULT_ORGANIZER => $t->cultActivity = min(100, $t->cultActivity + 5),
                NamedDemonStrategy::MILITARY_INVADER => $t->localManifestation = min(100, $t->localManifestation + 4),
                default => null,
            };
        }
    }

    private function applyChannel(ThreatWorld $world, NamedDemonRecord $demon, string $channel, array $target): string
    {
        $territoryId = $target['territoryId'] ?? $demon->territoryId;
        if (is_string($territoryId) && isset($world->territories[$territoryId])) {
            $demon->territoryInfluence[$territoryId] = min(
                100,
                ($demon->territoryInfluence[$territoryId] ?? 0) + 8
            );
        }

        return match ($channel) {
            ActChannel::DREAMS, ActChannel::TEMPTATION => $this->tempt($world, $demon, $target),
            ActChannel::POSSESSION => $this->possess($world, $demon, $target),
            ActChannel::CULT_AGENTS => $this->directCult($world, $demon, $target),
            ActChannel::CORRUPTED_NOBLES => $this->takeServant($demon, ServantKind::CORRUPTED_NOBLE, $target),
            ActChannel::CORRUPTED_CLERGY => $this->takeServant($demon, ServantKind::CORRUPTED_CLERGY, $target),
            ActChannel::MANIFESTATIONS => $this->named->manifest($world, $demon->id, (string) $territoryId) ? 'A body of smoke stood in the place.' : '',
            ActChannel::INFERNAL_ARMIES => $this->markHostPressure($world, $demon, (string) $territoryId),
            ActChannel::LOCALIZED_BREACHES => $this->markBreachPressure($world, $demon, (string) $territoryId),
            default => 'The named pressure moved.',
        };
    }

    private function tempt(ThreatWorld $world, NamedDemonRecord $demon, array $target): string
    {
        $territoryId = $target['territoryId'] ?? null;
        if (is_string($territoryId) && isset($world->territories[$territoryId])) {
            $t = $world->territory($territoryId);
            $t->rulerTemptation = min(100, $t->rulerTemptation + 10);
            $t->corruption = min(100, $t->corruption + 3);
        }

        return 'A private hunger was fed.';
    }

    private function possess(ThreatWorld $world, NamedDemonRecord $demon, array $target): string
    {
        $characterId = (string) ($target['characterId'] ?? '');
        if ($characterId === '') {
            throw new InvalidArgumentException('Possession requires a character');
        }
        $link = new PossessionLink('pos-'.$demon->id.'-'.$characterId, $characterId);
        $link->namedDemonId = $demon->id;
        $link->sourceCatalogKey = $demon->catalogKey;
        $link->factionKey = $demon->factionKey;
        $link->stage = PossessionStage::OPPRESSION;
        $link->intensity = 40;
        $world->possessions[$link->id] = $link;
        $this->named->bindToHost($world, $demon->id, $characterId);
        $this->takeServant($demon, ServantKind::POSSESSED, ['subjectId' => $characterId]);

        return 'A will was borrowed.';
    }

    private function directCult(ThreatWorld $world, NamedDemonRecord $demon, array $target): string
    {
        $territoryId = (string) ($target['territoryId'] ?? '');
        $cultId = (string) ($target['cultId'] ?? 'cult-'.$demon->id.'-'.$territoryId);
        if (!isset($world->cults[$cultId])) {
            $cult = new CultCell($cultId, $territoryId, $demon->factionKey ?? 'nave_whisperers');
            $cult->patronNamedKey = $demon->catalogKey;
            $cult->activity = 20;
            $world->cults[$cultId] = $cult;
        } else {
            $world->cults[$cultId]->patronNamedKey = $demon->catalogKey;
            $world->cults[$cultId]->activity = min(100, $world->cults[$cultId]->activity + 12);
        }
        $this->takeServant($demon, ServantKind::CULT_AGENT, ['subjectId' => $cultId]);
        if (isset($world->territories[$territoryId])) {
            $world->territory($territoryId)->cultActivity = $world->cultActivityIn($territoryId);
        }

        return 'A cell took instruction.';
    }

    private function takeServant(NamedDemonRecord $demon, string $kind, array $target): string
    {
        $subjectId = (string) ($target['subjectId'] ?? $target['characterId'] ?? '');
        if ($subjectId === '') {
            throw new InvalidArgumentException('Servant requires a subject');
        }
        foreach ($demon->servants as $row) {
            if ($row['subjectId'] === $subjectId) {
                return 'A known servant was pressed again.';
            }
        }
        $demon->servants[] = [
            'id' => 'srv-'.$demon->id.'-'.$subjectId,
            'kind' => $kind,
            'subjectId' => $subjectId,
            'imprisoned' => false,
        ];

        return 'A servant was taken.';
    }

    private function markHostPressure(ThreatWorld $world, NamedDemonRecord $demon, string $territoryId): string
    {
        $this->named->manifest($world, $demon->id, $territoryId);
        if (isset($world->territories[$territoryId])) {
            $world->territory($territoryId)->localManifestation = min(
                100,
                $world->territory($territoryId)->localManifestation + 12
            );
        }

        return 'An infernal column was promised.';
    }

    private function markBreachPressure(ThreatWorld $world, NamedDemonRecord $demon, string $territoryId): string
    {
        $this->named->manifest($world, $demon->id, $territoryId);
        if (isset($world->territories[$territoryId])) {
            $world->territory($territoryId)->localManifestation = min(
                100,
                $world->territory($territoryId)->localManifestation + 18
            );
        }

        return 'The ground remembered a door.';
    }

    private function banish(ThreatWorld $world, NamedDemonRecord $demon): void
    {
        $demon->status = NamedDemonStatus::BANISHED;
        $demon->territoryId = null;
        $demon->hostCharacterId = null;
        $demon->destroyedOn = $world->date;
        $demon->returnAuthorized = false;
    }

    private function unmanifest(NamedDemonRecord $demon): void
    {
        $demon->status = NamedDemonStatus::LATENT;
        $demon->territoryId = null;
        $demon->hostCharacterId = null;
    }

    private function severInfluence(NamedDemonRecord $demon, ?string $territoryId): void
    {
        if ($territoryId === null) {
            $demon->territoryInfluence = [];
        } else {
            unset($demon->territoryInfluence[$territoryId]);
        }
        if ($demon->territoryId === $territoryId) {
            $this->unmanifest($demon);
        }
    }

    private function destroyCults(ThreatWorld $world, NamedDemonRecord $demon): void
    {
        foreach ($world->cults as $cult) {
            if ($cult->patronNamedKey === $demon->catalogKey) {
                $cult->destroyed = true;
                $cult->activity = 0;
            }
        }
        $demon->servants = array_values(array_filter(
            $demon->servants,
            fn (array $row) => ($row['kind'] ?? '') !== ServantKind::CULT_AGENT
        ));
    }

    private function closeLinkedBreaches(ThreatWorld $world, NamedDemonRecord $demon): void
    {
        foreach ($world->breaches as $breach) {
            if ($breach->namedDemonId === $demon->id && $breach->open) {
                $breach->open = false;
                $breach->closedOn = $world->date;
            }
        }
    }

    private function imprisonServant(NamedDemonRecord $demon, string $servantId): void
    {
        if ($servantId === '') {
            throw new InvalidArgumentException('Imprisoning a servant requires servantId');
        }
        $found = false;
        foreach ($demon->servants as $i => $row) {
            if ($row['id'] === $servantId || $row['subjectId'] === $servantId) {
                $demon->servants[$i]['imprisoned'] = true;
                $found = true;
                if (($row['kind'] ?? '') === ServantKind::POSSESSED) {
                    $demon->hostCharacterId = null;
                    if ($demon->status === NamedDemonStatus::BOUND) {
                        $demon->status = NamedDemonStatus::LATENT;
                    }
                }
            }
        }
        if (!$found) {
            throw new InvalidArgumentException("Unknown servant {$servantId}");
        }
    }

    private function preventObjective(NamedDemonRecord $demon, string $objectiveKey): void
    {
        if ($objectiveKey === '') {
            throw new InvalidArgumentException('Preventing an objective requires objectiveKey');
        }
        if ($demon->objective($objectiveKey) === null) {
            throw new InvalidArgumentException("Unknown objective {$objectiveKey}");
        }
        $demon->setObjectiveStatus($objectiveKey, ObjectiveStatus::PREVENTED);
    }

    private function require(ThreatWorld $world, string $demonId): NamedDemonRecord
    {
        if (!isset($world->namedDemons[$demonId])) {
            throw new InvalidArgumentException("Unknown named demon instance: {$demonId}");
        }

        return $world->namedDemons[$demonId];
    }
}
