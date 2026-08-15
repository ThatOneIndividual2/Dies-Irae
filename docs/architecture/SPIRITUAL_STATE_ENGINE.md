# Spiritual State Engine

**Status:** First-class Dies Irae domain  
**Date:** 2026-08-13  
**Peers:** Church (offices, faculties, papal acts), Hell (factions, incursions), Population (settlement despair)

This engine records the moral and sacramental life of persons and the spiritual weather of places. It is not a piety wallet. It is not a good/evil slider.

Provider: `App\Providers\SpiritualServiceProvider` (registered in `config/app.php`).  
Inspect routes live in `routes/web.php` under `local.inspect` (local/testing only).

---

## Law

1. Theological virtues and capital vices are independent axes. Never average them into one score.
2. Canonical standing is public Church law. Interior disposition is not.
3. Sacraments change game state. They are not flavor buttons.
4. Corruption is polymorphic by subject kind. A monastery and an army do not share one effect table.
5. Church issues censures and faculties. This engine stores effects on persons and places.
6. World isolation on every row. Mutations go through Actions. Events fire after commit.
7. Production worlds are not reset by this package. Migrations are additive.

---

## What is not this domain

| Concern | Owner |
|---------|--------|
| Pope, sees, appointments, faculties | Church |
| Civil marriage / dynasty | Characters |
| Settlement population despair census | Population |
| Demonic factions as polities | Hell |
| Title ranks named bishop/pope | Forbidden |

Port: `ClericalAuthorityPort` is the only Church dependency.

- Production binds `ChurchClericalAuthority` (reads clergy status, sacramental grants, exorcist legitimacy).
- Tests bind `InMemoryClericalAuthority` so Church tables are not required.

Church sacrament kinds map as: confession = penance, anointing = unction, holy orders = orders.

---

## Character spiritual anatomy

Interior snapshot (`character_spiritual_states`, one current row):

| Axis | Role |
|------|------|
| Faith, Hope, Charity | Theological virtues |
| Pride, Greed, Lust, Envy, Gluttony, Wrath, Sloth | Capital vices |
| Despair | Distinct from low Hope. A despairing believer can still have Faith. |
| Repentance disposition | none / stirring / contrite / performing_penance / relapsed |
| Personal corruption intensity | Cached from the character's corruption record |

Canonical snapshot (`character_canonical_states`):

| Fact | Visibility |
|------|------------|
| Baptism, confirmation, orders grade, matrimonial bond | Clergy + often public |
| Eucharist standing | Clergy; inferable when barred from the rail |
| Excommunication / interdict effect | Public |
| Grave matter unconfessed | Private until scandal or confession |

Related records, not scores:

- Temptation (pressing inclination, usually private)
- Demonic influence (relation + stage; whisper private, possession partly inferable)
- Scandal (what the world knows)
- Penance (assigned work)
- Sacrament records (append-only history)

---

## Visibility

| Class | Who sees it | Examples |
|-------|-------------|----------|
| Private | Subject (and admin) | Exact vice scores, temptation, whisper-stage influence, unconfessed grave matter |
| Inferable | Others, as bands not numbers | Marked wrath in conduct; melancholy; public possession signs |
| Event | Only after a recorded scandal or public rite | Adultery known because the court saw it |
| Clergy | Faculties + jurisdiction | Canonical standing; exorcist may sense oppression; confessor sees confessed matter |
| Sealed | Confessor only, and unusable politically | Confession content |
| Admin | Local inspect / testing | Everything |

`SpiritualVisibilityPolicy` + `SpiritualVisibilityService` produce views. Player UI must call a view, never dump the snapshot.

The confession seal throws if sealed knowledge is requested in a `political` observer context.

---

## Sacraments

Each conferral writes `sacrament_records` (minister, subject, place, date, validity) and applies consequences in the same transaction.

| Sacrament | Mechanical consequence |
|-----------|------------------------|
| Baptism | Opens other sacraments; creates spiritual + canonical rows; weakens low-stage demonic foothold |
| Confession | Requires faculties. Clears grave-unconfessed. Restores communion if not censured. Assigns penance. Sealed knowledge for the confessor. |
| Eucharist | Requires baptism + communion standing. Lifts Faith/Hope/Charity modestly. Invalid if barred. |
| Confirmation | Once (valid). Raises demonic resistance. |
| Matrimony | Sacramental bond, distinct from civil marriage. Blocks a second valid bond. |
| Holy Orders | Sets orders grade on the person. Does not create a secular title or a see. |
| Anointing of the Sick | Lowers despair, raises Hope; records last anointing. |

Validity: `valid`, `valid_illicit`, `invalid`, `doubtful`. Invalid rites still leave a record.

---

## Virtue and sin hooks

Any domain calls `RecordSpiritualAct` with a `SpiritualActType`. The catalog maps an act onto several axes, optional scandal, optional corruption, optional temptation resolution.

Acts in v1: murder, betrayal, mercy, almsgiving, adultery, sacrilege, oath_breaking, charity, fasting, pilgrimage, confession, desecration, cowardice, martyrdom.

Murder and martyrdom must never produce the same signature.

---

## Corruption

`corruption_states` is a morph ledger (`subject_type`, `subject_id`), not a reused character table.

Supported subjects: character, household, settlement, monastery, army, territory, institution.

Each kind has its own `CorruptionEffectPolicy` (spread hints, cleansing requirements, gameplay modifiers). Applying corruption to an army never overwrites a territory row. Cleansing is explicit (`CleanseCorruption`).

Catastrophe may already own `corruption_states` rows. This engine adds `kind`, `is_current`, source morph, visible signs, and an event ledger. It does not drop the catastrophe table.

Hell owns `demonic_influences`. This engine writes stage/intensity onto those rows (`is_active`, `started_on`) and does not invent a second possession table.

---

## Admin inspect

Local/testing only:

- `GET /dev/inspect/spiritual/characters/{character}`
- `GET /dev/inspect/spiritual/subjects/{type}/{id}`

World inspect lists a spiritual link per character. Backed by `SpiritualInspectionService`. No public mutation routes. Laravel policy `SpiritualStatePolicy` gates inspect to local/testing.

---

## File map

- Actions: `app/Actions/Spiritual/`
- Domain: `app/Domain/Spiritual/`
- Events: `app/Events/Spiritual/`
- Laravel policy: `app/Policies/SpiritualStatePolicy.php`
- Migration: `database/migrations/2026_08_13_100000_create_spiritual_state_tables.php`
- Config: `config/spiritual.php`
- Inspect: `app/Http/Controllers/Dev/SpiritualInspectController.php`
- Tests: `tests/Unit/Spiritual/`, `tests/Feature/Spiritual/`
