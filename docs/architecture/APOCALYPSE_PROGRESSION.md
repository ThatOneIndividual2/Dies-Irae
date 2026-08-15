# Apocalypse Progression

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Owner:** Catastrophe / Time  
**Companion:** [dies_irae domain map](../dies_irae/DIES_IRAE_DOMAIN_MAP.md) (when present)

The campaign begins as a recognizable medieval order. It deteriorates because of what happens in the world, not because a clock expired. Resistance can slow the wound and can make a place locally holier than the globe. It cannot farm the age backwards.

---

## What this is

`ApocalypseState` is a first-class per-world row. It is not a `game_month`, not a score, and not a map recolor.

One world has one current phase, a set of meters, irreversible milestone floors, and a chronicle. Other domains (plague, church, hell, war) **report signals**. They do not write phase keys themselves.

---

## State

Table `apocalypse_states` (unique `world_id`):

| Field | Role |
|-------|------|
| `phase_key` / `phase_ordinal` | Current age. Ordinal only increases. |
| `phase_entered_on` | World date of last advance. |
| `global_corruption` | Spiritual rot in the age. |
| `plague_severity` | Demographic catastrophe intensity. |
| `demonic_manifestation` | How visible hell has become. |
| `institutional_collapse` | Offices, sees, and law failing. |
| `famine_pressure` | Hunger as a climate. |
| `despair` | Loss of will. Not loyalty. |
| `church_cohesion` | Health of the Church. Starts high. |
| `political_fragmentation` | Crowns and vassalage losing grip. |
| `pressure` | Cached composite wound score. |
| `meter_floors` / `meter_ceilings` | Ratchets from phases and milestones. |
| `drift_accumulators` | Fractional ambient drift between integer meter steps. |
| `broken_assumptions` | Rules the rest of the sim may no longer take for granted. |
| `tick_count` / `last_ticked_on` | Scheduled pulse, not the driver. |

Meters are 0–100 integers. `church_cohesion` is a healthy meter (higher is better). The rest are wounds.

Local state lives on `territory_spiritual_weather`. A monastery can become a sanctuary without pretending the globe is still 1347 in June.

---

## Phases are data

Engine code uses keys. Names and thresholds live in `database/data/apocalypse/phases.json`.

Suggested sequence (content, not hardcoded branches):

1. `ordinary` — Ordinary World  
2. `portents` — Portents  
3. `great_pestilence` — Great Pestilence  
4. `spiritual_fracture` — Spiritual Fracture  
5. `manifestations` — Manifestations  
6. `open_incursions` — Open Incursions  
7. `collapse_of_kingdoms` — Collapse of Kingdoms  
8. `infernal_dominion` — Infernal Dominion  
9. `final_resistance` — Final Resistance  

Each phase may declare:

- `advance_to` / `advance_when` (pressure, meters, signal counts, required milestones, `any_of` groups)
- `meter_floors` applied on entry
- `church_cohesion_ceiling`
- `resistance_efficiency` (1.0 down toward ~0.14)
- `sanctuary_allowance` (how far local weather may undershoot global floors)
- `ambient_drift` (small, fractional, never enough for late phases)
- `unlocked_event_types`
- `broken_assumptions` (`safe_travel`, `guaranteed_harvest`, `guaranteed_papal_succession`, `restorable_age`, …)

`PhaseAdvanceEvaluator` refuses any next phase whose ordinal is not greater than the current one.

---

## Escalation is world-shaped

Signals are append-only (`apocalypse_signals`). Catalog: `database/data/apocalypse/signals.json`.

Escalation examples:

- `mass_sin`, `heresy`, `desecration`
- `plague_mortality`, `famine_deaths`
- `destroyed_monastery`, `murdered_clergy`, `broken_sacred_site`
- `successful_cult`, `failed_exorcism`
- `realm_collapse`

Resistance examples:

- `restored_monastery`, `relic_recovery`, `saint`, `pilgrimage`
- `holy_victory`, `repentance`, `cult_destroyed`, `breach_closed`

Other domains should call `ApocalypseReporter` (`RecordApocalypseSignal`):

```php
app(ApocalypseReporter::class)->record($world, 'failed_exorcism', 12, [
    'source_type' => 'exorcism_attempt',
    'source_id' => $attempt->id,
    'territory_id' => $territory->id,
    'idempotency_key' => 'exorcism:'.$attempt->id.':fail',
]);
```

Magnitude 1–100. Meter deltas are `weight * magnitude`, then resistance is scaled by the current phase’s `resistance_efficiency`. Escalation is not scaled down that way.

Signals apply immediately (the world can feel a failed rite the day it happens). The weekly tick is for ambient drift, phase re-check, and scheduling the next pulse.

Time alone does **not** carry an ordinary world into pestilence or hell. Ordinary ambient drift is empty. Later phases need plague signals, cults, fallen kingdoms, and milestones that time cannot mint.

---

## Irreversible milestones

Catalog: `database/data/apocalypse/milestones.json`.  
Table: `apocalypse_milestone_records` (unique per world + key).

Examples:

- `veil_thinned`
- `first_plague_wave`
- `first_manifestation`
- `first_incursion`
- `papacy_wounded`
- `first_kingdom_fallen`
- `infernal_dominion_declared`
- `age_cannot_return`

On reach:

1. The row is written once. Re-evaluation is a no-op.
2. Floors merge by **max** (stricter). Ceilings merge by **min**.
3. Meters are re-clamped. Resistance cannot later undercut those floors.
4. A chronicle entry is written.
5. `ApocalypseMilestoneReached` fires after commit.

There is no delete action. Debug inspect cannot erase a milestone. Farming saints after the first plague wave can ease despair toward its floor. It cannot restore the ordinary age.

---

## Tick

Event type: `apocalypse_stage_check`  
Interval: `config('apocalypse.tick_interval_days')` (default 7 world days)

Pipeline (same as the rest of Dies Irae time):

1. `ScheduleWorldEvent` (idempotency `apocalypse_tick:{worldId}:{date}`)
2. `ClaimDueWorldEvents`
3. `ApocalypseStageCheckHandler` → `ProcessApocalypseTick`
4. `MarkWorldEventProcessed`

`ProcessApocalypseTick` is skipped if `last_ticked_on` is already the current world date, unless `--force` (local/testing).

Scheduler: `diesirae:process-world-events` hourly, `withoutOverlapping()`. This is **not** a second clock. It consumes due events against `worlds.current_date`. Calendar advancement belongs to the Time package.

---

## Pressure

Cached on the state row. Weights in `config/apocalypse.php`:

```
0.18 corruption + 0.16 plague + 0.16 manifestation
+ 0.12 institutional collapse + 0.10 famine + 0.12 despair
+ 0.10 fragmentation + 0.06 (100 - church cohesion)
```

Pressure is a symptom used by phase rules. It is not a currency.

---

## Local weather vs global age

`territory_spiritual_weather` can be healthier than the globe by up to the phase `sanctuary_allowance`. That allowance shrinks as the age darkens. A restored house of prayer in infernal dominion is still a house of prayer. It is not 1346.

Legal title over a wounded county remains a geography/politics problem. Apocalypse does not steal the title row.

---

## Code map

| Piece | Path |
|-------|------|
| Catalog | `app/Domain/Apocalypse/ApocalypseCatalog.php` |
| Meters / pressure / rules | `app/Domain/Apocalypse/*` |
| Reporter | `app/Contracts/ApocalypseReporter.php` |
| Mutations | `app/Actions/Apocalypse/*` |
| Handler | `app/Domain/Simulation/Handlers/ApocalypseStageCheckHandler.php` |
| Content | `database/data/apocalypse/{phases,signals,milestones}.json` |
| Inspect | `/dev/inspect/apocalypse/{world}` (local/testing) |

Commands (status is read-only; the rest are local/testing):

- `diesirae:apocalypse-status {world?}`
- `diesirae:apocalypse-tick {world?} [--force]`
- `diesirae:apocalypse-signal {world} {signal} {magnitude}`
- `diesirae:process-world-events {world?}`

---

## Invariants

1. One `apocalypse_states` row per world.  
2. Phase ordinal never decreases.  
3. Milestone keys are unique and durable.  
4. Meters never leave 0–100 and never undercut floors or overshoot ceilings.  
5. Cross-world territory ids are rejected.  
6. Signal idempotency is per world.  
7. No public HTTP mutators. Inspect is local/testing and 404 otherwise.  
8. Hell is not modeled as a `Realm`. This package only tracks the age and local weather.

---

## What this package does not do

It does not simulate plague spread, exorcism rites, or demonic factions. Those systems raise signals. They may read `phase_key`, `broken_assumptions`, and local weather.

It does not reset production worlds. Creating a world starts at `ordinary`. It does not seed maximum chaos.
