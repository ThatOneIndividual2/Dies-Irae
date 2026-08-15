# Named Infernal Entities

**Status:** Kernel and slice fixtures implemented  
**Date:** 2026-08-13  
**Owner:** `App\Domain\Hell\NamedInfernalEngine`  
**Parent:** [DEMONIC_THREAT_ENGINE.md](DEMONIC_THREAT_ENGINE.md)

Named demons are persistent antagonists. They have identities, agendas, relationships, and history.

They are not required for a demonic incursion. A county can be tempted, corrupted, or even manifested through local meters, cults, despair, and apocalypse intensity with zero named rows.

---

## Law

1. A named entity is a catalog identity, one living instance per catalog key per world.
2. Incursion lifecycle may advance with `local_manifestation_high` and no named demon present.
3. Players do not automatically know true identity, rank, motives, exact location, or vulnerabilities.
4. Physical channels (manifestations, infernal armies, localized breaches) require apocalypse intensity and a permitted phase.
5. Defeat of a body, cult, or breach is not unmaking. `DefeatKind::isPermanentDestruction()` is always false.
6. Pulse never respawns a destroyed or banished named demon. True destruction still uses `destroyNamedDemon`.
7. Grudges persist against dynasties, saints, monasteries, rulers, exorcists, and holy orders.
8. Demonic factions remain infernal polities, not realms.

---

## Entity model

Catalog rows live in `database/data/hell/taxonomy.json` under `named`. The in-memory record is `NamedDemonRecord`. Eloquent persistence is `named_demons` plus satellite tables.

| Field | Purpose |
|-------|---------|
| Unique identity | `catalog_key`, `true_name`, `instance_key` |
| Titles / epithets | `titles`, `epithets`, `public_alias` |
| Category | `named_unique` (taxonomy category) |
| Hierarchy | office in Hell (`court_officer`, `parish_infiltrator`, `host_marshal`) plus optional `liege_catalog_key` |
| Temptation / corruption themes | `themes` (greed, heresy, wrath, …) |
| Objectives | keyed aims with status open / advanced / prevented / completed |
| Known manifestations | signs the world may already rumor |
| Hidden true state | nature, seat, wound. Never copied into a public dossier |
| Servants | cult agents, corrupted nobles, corrupted clergy, possessed hosts |
| Cult relationships | `CultCell.patronNamedKey` and faction `patron_named_key` |
| Rivalries | catalog keys of opposing named entities / factions |
| Territory influence | intensity by territory id |
| Activity history | dated acts (channel, summary, payload) |

Strategy (`corrupter`, `cult_organizer`, `military_invader`) flavors pulse pressure when the entity already has a foothold. Latent named demons with no territory, servants, or influence do nothing on pulse, so unnamed incursion tests stay stable.

---

## Manifestation channels

A named demon may act through:

| Channel | Typical use |
|---------|-------------|
| dreams | private hunger, no body |
| temptation | ruler temptation and corruption |
| possession | bind a character; servant of kind possessed |
| cult agents | found or direct a cell |
| corrupted nobles | take a noble servant |
| corrupted clergy | take a clerical servant |
| manifestations | a body of smoke (physical) |
| infernal armies | promised column (physical) |
| localized breaches | remembered door (physical) |

The catalog lists which channels each identity may use. Physical channels call `canPhysicallyManifest`:

- apocalypse intensity at or above `physical_manifest_min_apocalypse`
- current phase not in `physical_manifest_forbidden_phases`
- status is not destroyed or banished

Ordinary years refuse a standing body even if meters are high.

---

## Knowledge model

`PublicDossier` is what an observer is allowed to see. It never copies `hiddenTrueState`.

By default the dossier shows a public alias (or "an unnamed pressure") and known signs. It does not show:

- true identity
- rank / hierarchy
- motives / objectives
- exact location
- vulnerabilities

Knowledge is granted per observer and facet. Sources:

- clergy
- recovered manuscripts
- confessions
- interrogation
- relics
- visions
- exorcisms
- cult documents

Play UI (`/spiritual`) renders rumors from this rule. Local inspect (`/dev/inspect/named/{world}`) shows true state and is testing/local only.

Laravel action `RevealNamedDemonKnowledge` writes a knowledge row. The kernel method `NamedInfernalEngine::revealKnowledge` is the simulation source of truth.

---

## Defeat

`NamedInfernalEngine::applyDefeat` never calls `destroy()`. Kinds:

| Kind | Effect |
|------|--------|
| banishment | status banished, no territory, return not authorized |
| sever local influence | drop territory influence; unmanifest if that was the seat |
| destroy cult network | destroy cells whose patron is this catalog key; drop cult-agent servants |
| close breach | close breaches tagged with this instance |
| defeat manifestation | status latent, no body on the map |
| battlefield defeat | same as defeat manifestation |
| imprison possessed servant | mark servant imprisoned; if bound through that host, become latent |
| prevent objective | mark that aim prevented |

A later `destroyNamedDemon` remains the explicit unmaking path, gated by lore and respawn policy as in the threat engine.

---

## Persistent grudges

`rememberGrudge` / `wouldTarget` keep intensity against:

- dynasty
- saint
- monastery
- ruler
- exorcist
- holy order

A named entity may later choose those subjects as the focus of temptation, cult work, or invasion. Grudges survive banishment of a body.

Laravel action `RememberNamedDemonGrudge` persists the same shape.

---

## Fixture demons

`EnsureNamedInfernalFixtures` is idempotent. It does not reset worlds. Incursions still work if it is never run.

| Catalog key | Strategy | Public alias | Physical gate |
|-------------|----------|--------------|---------------|
| `mammon_the_gilded` | corrupter | the gilded moth | intensity 40, not ordinary or portents |
| `the_whisper_in_the_nave` | cult organizer | a draft in the choir | intensity 22, not ordinary |
| `apollyon_the_unmaker` | military invader | the locust captain | intensity 52, not ordinary / portents / great pestilence |

On the Provence slice the Whisper is patron of `brothers_open_grave`. Three infernal factions (`court_of_the_gilded_moth`, `nave_whisperers`, `locust_host`) sit beside the existing Open Grave cult faction. They are not realms.

Command (additive, live-safe):

```bash
php artisan diesirae:ensure-named-infernal provence-1347
```

---

## Placement

```text
app/Domain/Hell/NamedInfernalEngine.php
app/Domain/Hell/State/NamedDemonRecord.php
app/Domain/Hell/State/PublicDossier.php
app/Domain/Hell/Enums/{ActChannel,KnowledgeFacet,KnowledgeSource,DefeatKind,GrudgeSubjectType,...}
app/Actions/Hell/EnsureNamedInfernalFixtures.php
app/Actions/Hell/RevealNamedDemonKnowledge.php
app/Actions/Hell/RememberNamedDemonGrudge.php
database/data/hell/taxonomy.json
database/migrations/2026_08_13_310000_create_named_infernal_entity_tables.php
tests/Unit/Hell/NamedInfernalEngineTest.php
tests/Feature/Hell/NamedInfernalEntitiesTest.php
```

---

## Tests

- unnamed corrupted land with high local manifestation becomes manifested with zero named demons
- corrupter raises temptation; cult organizer directs a cell; military invader is refused in ordinary years
- dossier hides Mammon until `true_identity` is revealed
- every defeat kind leaves status other than destroyed
- grudges persist and `wouldTarget` matches
- slice seed creates three latent fixtures; `/spiritual` shows the gilded moth, not Mammon
- inspect `/dev/inspect/named/{world}` shows true names
