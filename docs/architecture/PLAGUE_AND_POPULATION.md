# Plague and Population

**Status:** Package 8 kernel  
**Date:** 2026-08-13  
**Companions:** [DIES_IRAE_DOMAIN_MAP.md](../dies_irae/DIES_IRAE_DOMAIN_MAP.md), [RECONSTRUCTION_PLAN.md](../dies_irae/RECONSTRUCTION_PLAN.md)

This is a strategic collapse engine. It is not a medical simulator. It does not model rats, fleas, R0 curves, or named peasants. It models **places**, **estates**, and **routes**.

Named character death remains Package 6. A plague wave can empty a town while every duke still lives. The reverse is also allowed: a count can die of plague while his county still has souls.

---

## Grain

| Thing | Grain |
|-------|--------|
| People | Aggregate **cohorts** by estate (nobles, clergy, burghers, peasants, unfree) |
| Place | **Settlement** (city, town, village, monastery, port, camp), optionally attached to a territory or holding |
| Disease | **Plague wave** + per-host **focus** (settlement, army, caravan, pilgrim column) |
| Movement | **Displacement** records that move cohort counts, never NPCs |
| Time | The world calendar. One tick is one plague day. Same clock as harvest and liturgy. |

Territory-level `territory_plague_states` (migration 000700) is a **map rollup**: intensity on a county for coloring and overlay. Settlement census (`settlements`, `settlement_populations`, migration 000710) is the **demographic authority**. A county can show plague on the map while its villages still have distinct prevalence, quarantine, and ruin.

Do not add a `regions.population` integer and call it a system. Seed from geography files. Then simulate.

World isolation is mandatory. Every row and every in-memory aggregate carries `world_id`. Cross-world links are rejected.

---

## Settlement census

Every meaningful settlement exposes:

| Field | Source |
|-------|--------|
| Total population | Sum of cohorts |
| Class composition | nobles / clergy / burghers / peasants / unfree |
| Clergy population | `cohorts.clergy` |
| Military-age population | Derived from class rates |
| Workforce | Derived from class rates |
| Food demand | Derived (nobles eat more, unfree eat less) |
| Food production | workforce × yield × ruin × disease drag |
| Disease burden | (infectious + incubating) / souls, basis points |
| Morale | 0–100, falls with death and hunger |
| Despair | 0–100, rises with death, unburied, absent clergy |
| Migration pressure | despair + disease + hunger + ruin push |
| Tax base / levy base | Raw estates × ruin multipliers |
| Corpse pressure | unburied / graveyard capacity |
| Ruin state | See below |
| Viability | Souls, workforce, and ruin together |

Readout: `Settlement::census()` / `SettlementCensus::of()`.

Class rates are strategic, not actuarial:

- Military age is highest among nobles and burghers, lowest among clergy.
- Workforce is highest among peasants and the unfree. Nobles barely work.
- Black Death kills clergy harder because they tend the dying. Infernal miasma does not care about last rites.

---

## Plague

A **strain** is a `PlagueProfile`. Catalog keys:

- `black_death` — The Great Mortality. High clergy weight, some noble shelter, modest reinfection.
- `pneumonic_death` — Faster, meaner, less corpse-driven.
- `infernal_miasma` — Supernatural. Amplifies from the dead and from local hell overlay. Nobles are not safe.

Variables on every strain:

| Variable | Role |
|----------|------|
| Infectiousness | How many new incubating cases an infectious pool seeds per tick, after contact and containment |
| Mortality | Share of resolved cases that die |
| Incubation | Ticks in delayed batches before a cohort becomes infectious |
| Infectious duration | Ticks before a cohort resolves (death or recovery) |
| Settlement prevalence | Infectious + incubating over souls |
| Geographic spread | `adjacent` links |
| Army spread | Mobile `plague_hosts` of type `army`, plus `army` links |
| Trade spread | `trade` links (ports, fairs, shipping) |
| Pilgrimage spread | `pilgrimage` links (shrines, jubilees, relic roads) |
| Quarantine | `none` / `watch` / `closed_gates` / `cordon` |
| Corpse handling | `consecrated` / `mass_grave` / `burned` / `abandoned` / `desecrated` |
| Clergy care | `absent` / `none` / `parish` / `heroic` |
| Supernatural amplification | Strain multiplier × local overlay (hell, desecration, rift) |

Incubation is a **batch queue**, not an individual timer. That keeps the model aggregate and still makes “we closed the gates after the first death” too late.

### Spread math (strategic)

Exported seed along a link:

```text
infectious × link intensity × vector base × (1 - quarantine block) × infectiousness
```

Vector bases (basis points):

| Vector | Base | Quarantine |
|--------|------|------------|
| Adjacent | 3500 | Partial. People still flee. |
| Trade | 5500 | Closed gates and cordons cut this hard. |
| Pilgrimage | 7000 | Dense lodgings and rites. Interdict / closed shrines cut it. |
| Army | 9000 | Civic quarantine barely matters. Camps mix. |

An occupied town mixes with the army in both directions. Marching `MoveArmyHost` plants a seed in the destination the day the host arrives.

Supernatural amplification is not a global “everyone dies more” buff. It is a strain flag plus a **local** multiplier on the settlement or host. A rifted county can burn while the next valley still has a natural plague.

### What this is not

- Not SEIR differentials.
- Not a per-peasant infection flag.
- Not `mortality_base_by_age` raised worldwide.
- Not food set to zero on a User row.

---

## Death

`ApplyMassMortality` is the only way large numbers of people leave a census by dying. It always:

1. Pulls deaths from cohorts with class weights (never below zero).
2. Trims infection pools so they cannot exceed remaining souls.
3. Pushes corpses onto the graveyard / unburied pile.
4. Moves morale, despair, and corruption.
5. Recomputes ruin.
6. Emits mechanical consequences.

Consequence ports (other packages listen; this package does not impersonate them):

| Effect | How |
|--------|-----|
| Workforce | Recalculated from remaining cohorts |
| Taxation | `taxBase()` after ruin multipliers. Abandoned / ruined / overrun collect nothing. |
| Armies | Levy base drop → `ArmyAttritionPort`. Field armies are separate hosts that also take plague deaths. |
| Succession | Noble deaths and ruin crisis → `SuccessionPressurePort`. Title law still runs in the secular package. |
| Clergy vacancies | Clergy cohort deaths → `ClergyVacancyPort`. Sees are not titles. |
| Noble extinction | Local noble cohort hits 0 → `NobleExtinctionPort`. Dynasty extinction is still a character/dynasty fact. |
| Settlement viability | `isViable()` and ruin |
| Food production | Workforce × ruin × disease |
| Rebellion | Despair + missing nobles + ruin rebellion multiplier |
| Migration | `migrationPressure()`; auto-flee above a threshold |
| Graveyards / corpse pressure | Unburied vs capacity; handling changes burial rate and infection bonus |
| Despair | Deaths, unburied, absent clergy |
| Corruption | Desecration, abandoned dead, ruin drift, supernatural overlay |

`CharacterDeathPort` is **null by default**. Package 8 acceptance: a wave can depopulate a territory without any character dying. When Church and Characters exist, a real port may schedule named deaths in the infected land without making character mortality stand in for the Black Death.

---

## Ruin states

Settlements progress through, and sometimes branch across:

| State | Mechanical meaning |
|-------|--------------------|
| **functioning** | Full tax, levy, food, parish, recruitment. Ordinary medieval order. |
| **strained** | Tax/levy/food cut. Despair floor. Migration starts. Mild army supply problems. Disease or lost labor put you here. |
| **depopulated** | Labor collapse. Weak tax and levy. Succession crisis flag. Parish still exists. Cannot really garrison. |
| **abandoned** | Below viability floor. **No tax, no levy, no food, no parish, no recruitment.** Legal title may still exist. People have left. |
| **ruined** | Abandoned long enough to decay. Same economic death, higher corruption drift and army attrition. Refugees do not auto-restore it; resettlement must be explicit. |
| **corrupted** | Spiritual wound. Parish does not function. Tithe stops. Recruitment stops. Remaining people are a rebellion and cult risk. Can sit on land that is still legally held. |
| **overrun** | Hell or equivalent occupation. Nothing ordinary works. Refugees are turned away. Armies take severe attrition. A duke can still “own” the wound. |

Demographic track: functioning → strained → depopulated → abandoned → ruined.

Spiritual/hell branch: high despair + corruption can move a strained or worse place to **corrupted**. Hell occupation forces **overrun**. Lifting occupation leaves **ruined**, not functioning.

Empty and still marked functioning is a validation failure.

---

## Refugees

`RefugeeFlow` / `DisplaceCohorts` move **cohort counts**.

- Source loses souls. Destination gains souls (if it accepts refugees).
- Flight weights: peasants and the unfree leave first. Nobles linger unless the place is already collapsed.
- The column carries a share of incubating and infectious cases.
- A small road-loss slice dies in transit and lands on the destination graveyard. Souls are conserved: source + dest + unburied-on-road + in-transit + turned-away.
- Overrun and ruined destinations can refuse entry (`turned_away`).
- Monasteries score higher as refuge targets on the link graph.

Auto-flee fires when migration pressure is high and a linked accepting settlement exists. Quarantine cordons reduce outflow; they do not make people content.

---

## Engine and actions

In-memory authority (tests and calendar handlers):

`App\Domain\Catastrophe\CatastropheEngine`

Actions (no god-service):

| Action | Role |
|--------|------|
| `SeedSettlementPopulation` | Found a settlement with cohorts |
| `SeedPlagueWave` | Choose strain, infect origin |
| `TickPlague` | One or more plague days |
| `SpreadPlagueAlongNetwork` | Network pulse only |
| `SetQuarantine` / `SetCorpseHandling` / `SetClergyCare` | Institutional levers |
| `MoveArmyHost` | Army as a plague host |
| `DisplaceCohorts` | Refugee column |
| `ResolveRuinState` | Explicit ruin recompute |

Scheduled event type reserved on the world calendar: `territory_plague_tick` (`TerritoryPlagueTickHandler`). There is no second apocalypse clock.

Persistence (when the Laravel app and `worlds` table exist):

- `settlements`, `settlement_populations` (census authority)
- `plague_waves` (strain parameters added onto the 000700 wave row)
- `settlement_plague_states`, `plague_hosts`
- `settlement_links`, `displacements`
- `ruin_state_histories`, `mass_mortality_events`
- `territory_plague_states` (map rollup only)

Migration: `2026_08_13_000710_create_settlement_population_and_plague_tables.php` (after 000700).

Eloquent models are storage. The simulation does not live on `User` and does not read Feudalism `regions.population`.

---

## Tests

PHPUnit, no live database required for the kernel.

| File | Proves |
|------|--------|
| `PlagueAdjacentSpreadTest` | Neighbor towns infect across `adjacent` |
| `PlagueArmySpreadTest` | Occupation mix + march seed; cordon does not stop soldiers |
| `PlagueTradeSpreadTest` | Port-to-port `trade`; closed gates and cordon reduce it |
| `PlaguePilgrimageSpreadTest` | Shrine roads; pilgrimage hits harder than adjacency at equal traffic |
| `MassMortalityEffectsTest` | Workforce, tax, levy, food, clergy vacancies, succession, noble extinction, corpses, despair, corruption, supernatural kill, **no named character required** |
| `RuinStateTest` | Every state changes tax/levy/parish; hell overruns |
| `RefugeeFlowTest` | Cohort flows, plague carriage, conservation, overrun refusal, never negative |
| `PopulationCohortsTest` | Extract conserves souls; negative classes refused |

`PopulationBoundsValidator` rejects negative population, class mismatch, cross-world rows, and empty towns still marked functioning.

---

## Forbidden shortcuts

1. A global mortality buff named “Black Death”.
2. Zeroing food or gold on `users`.
3. Treating character deaths as the county dying.
4. `population = 0` while the town still collects tax.
5. Modeling each peasant as an NPC.
6. Storing the papacy or a see as a plague host type.
7. Pointing this schema at `feudalism_game`.
