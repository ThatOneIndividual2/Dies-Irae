# Demonic Threat Engine

**Status:** Kernel implemented (Package 9)  
**Date:** 2026-08-13  
**Owner:** `App\Domain\Hell`  
**Not a realm. Not a dynasty. Not a title.**

Hell is an overlay with agency. A county can be legally held and spiritually wounded at the same time. That is the point.

---

## Law

1. A demonic faction is not a `Realm`, `Dynasty`, or `Title`.
2. Incursion state lives on geography as overlay. Legal ownership is untouched by a pulse.
3. Named demons are persistent identities. Pulse never respawns them.
4. Countermeasures are multi-factor. Devotion alone is not a win button.
5. Taxonomy is data. Engine code uses keys.
6. Apocalypse phase is owned by the apocalypse domain. Hell *reads* intensity as a gate and multiplier.
7. Possession is a relation to a demon (template or named), not a health trait.
8. A corrupted human army is not a demonic host.
9. One world date. Hell emits events; Time schedules them. Hell is not a second clock.

False shortcuts this engine refuses:

- One Hell `Kingdom` painted red on the political map
- `titles.rank = demon_prince`
- Recoloring a region and setting `controller_id` to a demon User
- A piety integer that buys exorcism
- Casual named-demon respawn on tick

---

## Placement

```text
app/
  Domain/Hell/           # simulation kernel (framework-agnostic)
    Enums/
    State/               # in-memory overlay aggregate
    Ports/               # read apocalypse, church, population
  Domain/Demons/         # Laravel contract wrapping the kernel
  Actions/Hell/          # thin mutation entry points
  Models/                # Eloquent persistence for the overlay
database/
  data/hell/             # taxonomy, lifecycle, countermeasure profiles
  migrations/2026_08_13_000900_create_hell_overlay_tables.php
config/hell.php
tests/Unit/Hell/         # deterministic harness
docs/architecture/DEMONIC_THREAT_ENGINE.md
```

The kernel does not require Eloquent. `DemonsContract` is the Laravel entry. Actions hydrate a `ThreatWorld`, call the engine, and persist diffs. Tests run against the kernel with a fixed seed.

---

## Threat architecture

| Concern | Where it lives | Notes |
|---------|----------------|-------|
| Global apocalypse intensity | Input on `ThreatWorld` from apocalypse domain | Gates lifecycle; multiplies neighbor leak |
| Regional corruption | `TerritoryThreat.corruption` | 0–100 overlay; legal title unchanged |
| Local manifestations | `localManifestation` + `MANIFESTED` state | Named demons raise this each pulse |
| Possession | `PossessionLink` on a character | Stages: temptation → oppression → possession → pact |
| Cult activity | `CultCell` + territory meter | Hidden until activity is high |
| Demonic incursions | `TerritoryThreat.incursionState` | Lifecycle below |
| Corrupted armies | `CorruptedArmy` | Former human force; still has `originalRealmId` |
| Corrupted settlements | `settlementCorrupted` from `corrupted` upward | Production, morale, parish pressure |
| Portals / breaches | `Breach` | Hosts may bind to a breach |
| Infernal strongholds | Terminal incursion state | Parish life fails; neighbors are tempted |
| Named demons | `NamedDemonRecord` | Unique per world + catalog key |
| Demonic hosts | `DemonicHost` | Infernal composition, not a levy |

`DemonicFactionRecord` has themes, rivals, a patron named key, and claimed territory ids as *incursion claims*. It has no liege, no succession, no treasury.

---

## Taxonomy (data-driven)

`database/data/hell/taxonomy.json` holds:

- **categories:** lesser, tempter, possessor, commander, prince, named_unique
- **themes:** sins and corruptive tags (pride, greed, pestilence, heresy, …)
- **templates:** reusable entities the engine looks up by key
- **named:** unique catalog identities with strategy, channels, hidden true state, and `respawn_policy`

Named identities, knowledge, grudges, and non-destructive defeat live in [NAMED_INFERNAL_ENTITIES.md](NAMED_INFERNAL_ENTITIES.md). Incursions do not require a named row.
- **factions:** infernal polities keyed the same way

Adding a demon is a JSON row. The engine must not switch on individual names.

Capabilities (`tempt`, `possess`, `open_breach`, …) are tags for later content and warfare. Lifecycle and countermeasures key off category, rank weight, themes, and world state.

Unknown keys are rejected. Required categories must be present.

---

## Incursion lifecycle

Order (data: `incursion_states.json`):

```text
dormant → tempted → corrupted → manifested → breached → overrun → infernal_stronghold
```

Advance is gated by **local meters** and **apocalypse intensity**. An ordinary world can be tempted. It cannot be breached.

| Transition | Typical gate |
|------------|----------------|
| dormant → tempted | corruption / cult / despair thresholds, or neighbor overrun |
| tempted → corrupted | corruption high and (cult or despair), veil at least thinning |
| corrupted → manifested | named demon present or local manifestation, intensity mid |
| manifested → breached | open breach and high manifestation, intensity high |
| breached → overrun | host or corrupted army, morale broken |
| overrun → stronghold | open breach and a prince/named unique, intensity late |

Each state writes mechanical effects on pulse:

- events
- population (never negative)
- army corruption exposure
- clergy pressure / parish thinning
- ruler temptation
- morale and despair
- plague amplification (reads plague burden; does not own the plague wave)
- production multiplier
- travel (closed from breached upward; neighbors of overrun land cannot travel toward it)
- neighbor leak

Countermeasures can shift state downward. Backlash can shift it upward.

Legal `legalTitleId` is asserted unchanged after every pulse.

---

## Countermeasures

Kinds (`database/data/hell/countermeasures.json`):

- exorcism
- relic use
- prayer
- consecration
- pilgrimage
- military cleansing
- destroying cults
- closing breaches
- martyrdom
- holy orders

Each kind has **supporting** and **opposing** factors with weights. Score is:

```text
support / (support + oppose)
+ small deterministic noise from world seed
→ success | partial | failure | backlash
```

Factors include clergy rank and legitimacy, relic authenticity, holy-order support, accumulated prayer, pilgrimage strength, military force, martyrdom, local corruption, despair, apocalypse intensity, cult activity, named-demon rank, host strength, possession depth, plague, travel.

There is a `devotional_standing` factor. It is one weight among many. A test exists where devotion is 100 and the county is still lost.

Requirements are checked (clergy present, relic present, breach open, …) before the roll.

Outcomes may:

- shift incursion state
- lower corruption / despair / cult activity
- close a breach (and collapse hosts bound to it, unless the land is already a stronghold)
- cleanse a corrupted army
- ease or worsen possession
- mark a character as dead (martyrdom). Character death is applied by the character domain.

---

## Named demons

Spawned from catalog keys. One living instance per catalog key per world.

Statuses: latent, manifested, bound, banished, destroyed.

**Pulse never revives a destroyed or banished named demon.**

Persistent named antagonists (identity, knowledge, grudges, channels, defeat kinds that are not destruction) are specified in [NAMED_INFERNAL_ENTITIES.md](NAMED_INFERNAL_ENTITIES.md). Battlefield defeat of a manifestation is not unmaking.

Return requires `PermitNamedDemonReturn` with a non-empty lore reason. Policy then decides:

| Policy | Who may permit |
|--------|----------------|
| `never` | Nobody |
| `lore_only` | Explicit lore permission |
| `apocalypse_stage` | Apocalypse-state permission, and intensity ≥ catalog threshold |
| `ritual` | Ritual permission (cult / rite action) |

Even then, `enactAuthorizedReturn` is a second explicit step. Destruction is sticky on purpose.

---

## Hosts and armies

A **demonic host** is infernal: composition from catalog templates, optional named commander, optional bound breach. It is not raised as a feudal levy. Closing its breach collapses it unless the territory is an infernal stronghold.

A **corrupted army** was human. It keeps `originalRealmId` and a commander character id. Overlay corruption ticks with local incursion. Military cleansing can walk that corruption down. It must not be merged into `DemonicHost`.

Warfare (later) may fight both. This kernel only tracks presence, strength, and corruption.

---

## Ports to other domains

Hell reads; it does not replace:

| Port | Purpose |
|------|---------|
| `ApocalypseReading` | intensity and phase key |
| `ChurchSupportReading` | clergy, relics, holy orders for attempts |
| `PopulationOverlay` | apply hell mortality; read plague burden |

Spiritual-state (sin/virtue) and Church offices stay in their packages. Exorcism *targets* `DemonicInfluence` / `PossessionLink`. The papacy is not a hell faction.

Scheduled event types emitted for the calendar registry live in `HellEventType` and `config/hell.php`.

---

## Persistence

Migration `2026_08_13_000090_create_hell_overlay_tables.php`:

- `demonic_factions`
- `named_demons` + `named_demon_status_histories`
- `demonic_influences`
- `territory_incursions` (current overlay + history hook via `is_current`)
- `supernatural_overlays`
- `cults`
- `infernal_breaches`
- `demonic_hosts`
- `corrupted_armies`
- `countermeasure_attempts`

World isolation: `world_id` on every row. Unique living named demon per catalog key per world.

Eloquent models are thin records. Mutations go through Actions:

- `PulseHellIncursion`
- `AttemptCountermeasure`
- `CloseBreach`
- `DestroyCult`
- `RecordNamedDemonDestruction`
- `PermitNamedDemonReturn`
- `EnsureNamedInfernalFixtures`
- `RevealNamedDemonKnowledge`
- `RememberNamedDemonGrudge`

Satellite tables (objectives, servants, rivalries, territory influence, acts, grudges, knowledge): `2026_08_13_310000_create_named_infernal_entity_tables.php`.

---

## Deterministic test harness

`tests/Unit/Hell/DemonicThreatHarness.php` builds a three-county strip (west–center–east) with legal titles, rulers, and two infernal factions.

Run (standalone, no Laravel app required):

```bash
php vendor/bin/phpunit -c phpunit.xml
```

The harness covers:

- taxonomy load and a new template without code changes
- ordinary world: temptation allowed, breach forbidden
- title survives rift / stronghold
- neighbor overrun tempts adjacent land and closes travel
- devotion-only exorcism fails; combined church strength can succeed
- identical inputs yield identical scores
- martyrdom can close a breach where prayer cannot, at the cost of a life
- destroyed named demons stay dead across pulses
- `never` refuses lore return; `lore_only` returns only after permission
- host ≠ corrupted army
- closing a breach collapses a bound host
- possession deepens as a relation
- two cloned pulses fingerprint-match

RNG matches Feudalism’s hash float in `[0, 1)` so calendar integration can share seed material later.

---

## What this package does not do

- Advance apocalypse phase (apocalypse package)
- Simulate plague waves (population package)
- Store sees, sacraments, or papal acts (Church package)
- Resolve battles (warfare package)
- Paint the map (map resolvers later read this overlay)

Those systems should subscribe to hell events and read overlay state. They must not invent a Hell kingdom to make the UI simple.
