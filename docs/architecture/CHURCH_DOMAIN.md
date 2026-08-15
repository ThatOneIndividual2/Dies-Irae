# Church Domain

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Peer documents:** `docs/dies_irae/DIES_IRAE_DOMAIN_MAP.md`, `docs/architecture/PAPACY_AND_CONCLAVE.md`

The Catholic Church is a first-class spiritual authority. It is not a realm skin, a title rank, a piety integer, or a map color.

A see is not a duchy. The papacy is not a kingdom. A prince-bishop is one character with two offices.

---

## Independent axes on a person

A character may hold all of the following at once. They are stored in different tables and mutated by different contracts.

| Axis | Owner | Table |
|------|--------|--------|
| Personhood | Characters | `characters` |
| Dynastic membership | Dynasties | `characters.dynasty_id` / `dynasties` |
| Secular office | Titles | `titles` + `title_ownerships` |
| Territorial property | Geography | `holdings.owner_character_id` |
| Holy orders / vows | Church | `clergy_statuses` |
| Religious office | Church | `spiritual_offices` + `spiritual_office_holderships` |
| Excommunication | Church | `excommunication_states` |
| Heresy | Church | `heresy_presences` |

Losing a county does not vacate a see. Removing a bishop does not extinguish a dynasty. Excommunicating a king does not revoke his crown. Those effects, if wanted later, are cross-domain reactions, not collapsed columns.

Allowed combinations:

- Landed lord, no clerical status
- Priest, monk, bishop, or legate with no secular title
- Both (prince-bishop, royal chaplain, warrior-monk)
- Neither (landless kin, refugee, later a possessed peasant)

---

## Hierarchy

Diocesan chain (parent_see_id):

```text
Pope (papal see)
  -> archbishop (archdiocese / metropolitan)
    -> bishop (diocese)
      -> parish priest (parish)
```

Deacons are offices (and a holy-orders grade) under a parish or diocese.

Monastic and military-religious life sits **beside** that chain, not inside `titles`:

- `religious_orders` (Benedictine, etc.)
- `monasteries` (linked to a holding, not identical to `holding_type`)
- `holy_orders` (Templars-style corporations)
- Abbot / prior / grand master are `spiritual_offices` pointed at monastery or holy order, not at a county rank

Exempt abbeys (`see_type = exempt_abbey` or `monasteries.is_exempt`) need not answer the local bishop.

Cardinals are spiritual offices. They are not a secular rank and not required to be the ordinary of a see.

---

## What is not a title

`TitleRank` is only barony, county, duchy, kingdom, empire.

`SpiritualOfficeRank` is deacon, priest, abbot, prior, bishop, archbishop, cardinal, pope, legate, grand_master, master.

Creating a title with rank `pope` or `bishop` is rejected. Creating a spiritual office with rank `kingdom` is rejected.

The papacy is one row in `papacies` per world, pointing at a papal see and a papal spiritual office. It is not `titles.rank = empire` named "Papacy".

---

## Appointment policy

Appointment rules live in `config/church.php` and in optional `investiture_rights` rows. They are not hardcoded 11th-century controversy.

Default modes:

| Office | Default mode |
|--------|----------------|
| Pope | conclave |
| Cardinal, archbishop, bishop, legate | papal |
| Priest, deacon | local |
| Abbot, prior | abbatial_election |
| Grand master / master | internal_election |

A current `investiture_rights` row on a see overrides the office default (`papal`, `local`, `lay_investiture`, `concurrent`, `disputed`).

Supported states, without freezing a single historical procedure:

- Papal provision
- Local / lay nomination
- Disputed twin appointments (`clergy_appointments.status = disputed`, office remains vacant until resolved)
- Investiture conflict (secular nominator vs papal mode)
- Vacancy (no current holdership; papacy status `vacant`)
- Conclave process (assembly, ballots, deadlock, compromise, acceptance, enthronement)
- Anti-pope / schismatic claimant (`papal_claims`; a second papal-ranked office may exist; it does not replace `papacies.papal_office_id`)

Holdership history copies the title pattern: append-only rows, unique `(spiritual_office_id, is_current)`.

---

## Clergy legitimacy vs dynastic legitimacy

`characters.legitimacy_status` is blood (legitimate, bastard). It is not holy orders.

Clerical legitimacy is:

- `clergy_statuses.regularity` (regular / irregular)
- `spiritual_office_holderships.legitimacy` (recognized / disputed / schismatic / irregular)
- Current excommunication

An irregular or excommunicated holder can still be the recorded occupant of an office. They cannot exercise Church powers or sacraments until restored.

---

## Sacramental authority

Derived from orders grade (`clergy_statuses.orders_grade`) plus optional `sacramental_authorities` grants and `extraordinary_spiritual_authorizations`.

Default grades (policy):

- Deacon: baptism, matrimony
- Priest: those plus eucharist, penance, unction
- Bishop: those plus confirmation and orders

A king has no sacramental authority by virtue of a crown.

---

## Church powers

`App\Domain\Church\ChurchAuthority` (Actions in `app/Actions/Church`) can:

- Excommunicate / lift excommunication
- Place / lift interdict (see or territory, not a realm row)
- Appoint / remove clergy
- Recognize saints
- Recognize relic authenticity
- Sanction holy orders
- Declare heresy
- Legitimize exorcists
- Authorize extraordinary spiritual actions (exorcism, emergency orders, etc.)

Who may do what is `config/church.php` `powers` plus jurisdiction. The pope is universal. A bishop is scoped to his see (and child sees). A king is not in that list.

`DetermineTopLiege` walks `vassal_relationships` only. It never walks `parent_see_id`. The pope is a character's top liege only if that character is his secular vassal.

---

## Succession port

`ChurchEligibilityGate` implements `SuccessionEligibilityGate`. Titles ask the Church whether a person may inherit. The title system does not store theology.

Policy (`church.secular_succession`):

- Block major orders (deacon+)
- Block regular vows
- Block current excommunication

A prince-bishop who already holds a county is a dual-office fact, not an inheritance of the see through primogeniture.

---

## Core tables

Kernel (package 7 minimum, expanded):

- `faiths`
- `clergy_statuses`
- `church_provinces`
- `sees`, `see_territories`
- `spiritual_offices`, `spiritual_office_holderships`
- `papacies`, `papal_claims`
- `monasteries`, `religious_orders`, `holy_orders`

Acts and status:

- `clergy_appointments`
- `investiture_rights`
- `excommunication_states`
- `interdict_states`
- `heresies`, `heresy_presences`
- `sacramental_authorities`
- `saints`, `relics`, `relic_custodies`
- `exorcist_legitimacies`
- `extraordinary_spiritual_authorizations`

---

## False shortcuts (forbidden)

- `titles.rank = bishop` or `pope`
- Papal states as the papacy
- `characters.faith = 'Catholicism'` as the Church
- `holding_type = temple` as a monastery system
- A holy order modeled as a dynasty or a realm
- `ClaimType::RELIGIOUS` as spiritual authority
- A single piety integer that buys excommunication
- Top-liege walks that return the Pope because he is spiritually supreme

---

## Tests

`tests/Feature/Church/DualAuthorityIndependenceTest.php` proves the four person-states, independent loss of land vs office, title-rank firewall, unique papacy, liege walk, monastery ≠ holding, holy order ≠ dynasty.

`tests/Feature/Church/ChurchPowersAndAppointmentsTest.php` proves excommunication of a king, interdict on a see, policy-driven appointment and investiture override, disputed vacancy, anti-pope architecture, sacraments, and papal acts (saints, relics, orders, heresy, exorcists, extraordinary authorization).

Papal succession, conclave rounds, secular pressure, antipopes, and apocalypse fallbacks live in `docs/architecture/PAPACY_AND_CONCLAVE.md` and `tests/Feature/Papacy/PapacyAndConclaveTest.php`.
