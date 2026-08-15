# Saints, Relics, Pilgrimage, and Miracles

**Status:** Implemented as a first-class Dies Irae domain  
**Date:** 2026-08-13  
**Peers:** Church (faculties, papal acts), Spiritual State Engine (acts, visibility), Catastrophe (plague vectors)

These systems are rare and consequential. A miracle is not a spell. A relic is not a magic item slot. A saint is not a piety unlock.

Provider: `App\Providers\SacredServiceProvider`.  
Church still stamps recognition. This domain owns evidence, cult, custody, traffic, and competing interpretations.

---

## Law

1. Historical saints may be registered as cultus and later canonized by Church faculty.
2. A deceased character needs death, an opened cause, an evidence trail, and a local cult before formal sainthood.
3. Relic **claimed** authenticity, **recognized** authenticity, and **true nature** are three facts. Church can be wrong.
4. Custody is not ownership. Theft moves the body of the relic, not the legal claim.
5. Pilgrimage traffic is an economic, spiritual, political, and epidemiological fact.
6. Miracle claims require a precipitating event. Recognition does not prove causation. There is no `CastMiracle` action.
7. Mutations go through Actions. Events fire after commit. Production worlds are not reset. Migrations are additive.

---

## Saints

| Kind | How it enters |
|------|----------------|
| Historical | `RegisterHistoricalSaint`. No character row. Feast, patronage, shrine allowed immediately. |
| Deceased character | `OpenSaintCause` (must be dead) -> evidence -> local cultus -> `RecognizeSaintCause` or Church `RecognizeSaint`. |

Evidence types: martyrdom, reputation of holiness, miracle claim, relic, incorrupt body, local cult, written vita. Weight and distinct-type thresholds live in `config/sacred.php`.

Related rows: `saint_evidences`, `saint_patronages`, `saint_feasts`, `saint_shrines`, `saint_cults`. Reputation is a public cult measure, not an interior vice score.

Church `RecognizeSaint` without a character still canonizes a historical cult (existing Church tests). Passing a character into that papal act now requires the cause and evidence trail.

---

## Relics

Persistent assets on `relics` plus append-only `relic_events`.

| Field | Meaning | Visibility |
|-------|---------|------------|
| category | bodily, associated object, sacred object, local veneration | public |
| claimed_authenticity / claimed_provenance | what custodians say | public |
| authenticity | Church judgement | public |
| true_nature | authentic / forged / doubtful | admin inspect only |
| owner_type / owner_id | legal claim | public |
| current custody | who holds it now | public |
| condition | intact, damaged, desecrated, destroyed | public |

Custodians: monastery, cathedral, bishop, ruler, holy order, settlement institution, private character.

Acquisition: grant, gift, translation, find, deposit, theft, loot. Theft records sacrilege and leaves ownership in place.

A recognized forgery stays a forgery. Disputed relics keep competing provenance rows.

---

## Pilgrimage

`pilgrimage_routes` with ordered `pilgrimage_stops`. Completing a journey:

- records `SpiritualActType::PILGRIMAGE` (Faith, Hope, despair, sloth)
- raises character prestige
- writes `pilgrimage_traffic` (income, prestige, cult growth, legitimacy delta)
- grows local cult at a saint destination
- opens a plague `pilgrimage` vector via `PlagueTravelPort`

Catastrophe still owns plague math. This domain only announces traffic. Tests bind `RecordingPlagueTravelPort` and apply the links to `CatastropheEngine`. Production writes `settlement_links` when settlement keys exist.

---

## Miracles

Event-driven claims on `miracles`. Categories: healing, protection, incorrupt body, apparition, battlefield, unexplained deliverance, plague cessation, relic-associated.

A claim does **not**:

- raise character health
- end a plague wave
- rewrite a battle
- set `causation` to divine

`causation` is always `unknown` as a mechanic. Witnesses and `miracle_interpretations` compete (`divine`, `natural`, `demonic`, `fraud`, `unknown`) until Church recognition marks one official reading. Minority readings remain. Rarity is capped per place per year.

---

## Admin inspect

Local/testing only:

- `GET /dev/inspect/sacred/saints/{saint}`
- `GET /dev/inspect/sacred/relics/{relic}`
- `GET /dev/inspect/sacred/routes/{route}`
- `GET /dev/inspect/sacred/miracles/{miracle}`

Relic inspect is the only player-facing-adjacent surface that may show `true_nature`.

---

## File map

- Actions: `app/Actions/Sacred/`
- Domain: `app/Domain/Sacred/`
- Events: `app/Events/Sacred/`
- Migration: `database/migrations/2026_08_13_110000_create_saints_relics_miracles_tables.php`
- Config: `config/sacred.php`
- Tests: `tests/Unit/Sacred/`, `tests/Feature/Sacred/`
