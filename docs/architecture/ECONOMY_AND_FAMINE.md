# Economy and Famine

**Status:** Strategic kernel  
**Date:** 2026-08-13  
**Companions:** [PLAGUE_AND_POPULATION.md](PLAGUE_AND_POPULATION.md), [DIES_IRAE_DOMAIN_MAP.md](../dies_irae/DIES_IRAE_DOMAIN_MAP.md)

This is a survival economy. It must run as ordinary medieval provisioning before the veil thins, then degrade when plague, war, corruption, depopulation, and infernal blight hit the same settlements.

It is not a marketplace simulator. There are no individual buyers, stall prices, or named peasants haggling. The grain is **aggregates on places**.

Feudalism donated one idea: yield is a holding/region atom multiplied by local state (loyalty, strike, occupation). Dies Irae keeps that multiplier shape and throws away User-as-granary, cartoon wood/stone, and the hourly `TurnService` harvest.

---

## Layers

| Layer | What it stores |
|-------|----------------|
| Settlement production | Workforce, ruin, disease drag (`Settlement::foodProduction`) |
| Agriculture | Arable acres, planted crop, crop standing, land quality |
| Food stocks | Civic granary on the settlement |
| Livestock | Heads plus winter fodder |
| Workshops | Burgher shops, gold trickle at harvest |
| Market activity | 0-100 regional heat, not a stall ledger |
| Taxation | Gold from tax base, collapsed by famine unrest |
| Rents | Food skim when the parish/manor still functions |
| Church tithes | Tenth of the haul, paid into monastery stores |
| Noble obligations | Gold skim from the local purse |
| Trade routes | Capacity, blocked, haunted |
| Transport | Per-settlement haul cap |
| Shortages | Days of food, deficit basis points |
| Regional prices | Integer index derived from deficit and unrest |
| Military supply | Field foragers eat local rations |
| Monastery stores | Separate ecclesiastical granary |
| Emergency requisition | Coercive grain moves |

World isolation is mandatory. Every site and every shipment carries `world_id`.

---

## Harvest cycle

One world calendar. Month maps to a phase:

| Months | Phase |
|--------|--------|
| Mar-Apr | planting |
| May-Aug | growing |
| Sep-Oct | harvest |
| Nov-Feb | winter |

Each phase is a deterministic function of:

- weather
- workforce (minus the sick)
- land quality
- war disruption
- plague burden
- corruption
- supernatural blight
- ruin food multiplier
- abandoned acres

Planting writes `planted`. Growing writes `crop_standing`. Harvest converts standing crop into civic stores and a little livestock increase. Winter spoils grain and kills unfed herds.

Harvest output is the only honest way to refill a year. Daily `foodProduction()` is a census readout. Famine reads **stores**, not that daily figure. A county can still look green on the map while the granary is empty.

---

## Famine

Famine is not a random event table.

`FamineEngine` reads civic stores, monastery stores, and army rations. It writes:

- food deficit
- malnutrition
- starvation mortality (cohort extract, never invented souls)
- unrest and crime
- disease susceptibility
- military desertion
- tax collapse (`tax_multiplier_bp`)
- social desperation
- an extreme hook (`cannibalism`) only when stores are gone, malnutrition is high, and despair is already extreme

Stages climb from `none` to `shortage`, `hunger`, `famine`, `starvation`, `collapse` as days of food fall and consecutive hungry days rise.

When a settlement is in famine and another settlement still has food, `RefugeeFlow` moves cohorts with cause `famine`. That is the same displacement grain as plague flight.

---

## Grain movement

Rulers, monasteries, merchants, and armies move food between settlements.

| Mode | Meaning |
|------|---------|
| purchase | Gold changes hands at the regional price |
| requisition | A lord takes stores. Unrest rises. |
| donation | Gift into a house or town |
| relief | Emergency shipment |
| seizure | Army or crowd takes stores, including monastic grain |
| alms | Church distribution. Legitimacy rises. |

A blocked or haunted route cuts capacity. Haunted roads keep a third. Blocked roads move nothing.

Monasteries can store, give relief, become overwhelmed when their own days of food collapse, lose legitimacy when raided, and starve like anyone else.

---

## Apocalypse catalog

Effects are keys, not hard-coded story beats:

- `crop_blight`
- `spoiled_grain`
- `livestock_death`
- `abandoned_farmland`
- `blocked_roads`
- `haunted_routes`
- `reduced_labor`

`YieldCatalog::APOCALYPSE_EFFECTS` holds the numbers. The engine applies them. Content packs may add keys later without rewriting the tick.

---

## Persistence

Additive tables (migration `2026_08_13_002000`):

- `settlement_economies`
- `famine_states`
- `food_movements`
- `economy_trade_routes`
- `harvest_records`

The live rules sit in `EconomyEngine`. Eloquent rows are the snapshot, not a second simulator.

---

## Tests

`tests/Feature/Economy/HarvestAndFamineTest.php` (no database):

- normal harvest
- failed harvest
- plague reducing labor
- army consuming regional food
- monastery relief
- cascading famine
- famine-driven migration
- economic recovery
- blocked roads / catalogued blight

Run:

```bash
./vendor/bin/phpunit tests/Feature/Economy/HarvestAndFamineTest.php
```
