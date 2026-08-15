# Dies Irae Architecture

**Status:** Kernel scaffold  
**Date:** 2026-08-13  
**App root:** project root (intended `/var/www/html/Dies Irae`)  
**Stack:** PHP 8.0+, Laravel 9.52, MySQL `dies_irae_game` / `dies_irae_game_testing`  
**Donor:** Feudalism foundation contracts (read-only). No runtime dependency.

---

## What this is

Dies Irae is its own Laravel application. It re-implements Feudalism's proven secular contracts (world isolation, title ownership history, realm-as-cache, scheduled events) and adds Church, catastrophe, and hell as peer domains.

It is not a Feudalism module. It does not query `feudalism_game`.

---

## Isolation

| Concern | Dies Irae | Forbidden |
|---------|-----------|-----------|
| Composer package | `dies-irae/game` | `feudalism/game` |
| PHP namespace | `App\` in this tree only | Importing Feudalism classes |
| Database | `dies_irae_game` | `feudalism_game`, `got_game` |
| Test database | `dies_irae_game_testing` | `feudalism_game_testing` |
| Config | `config/game.php` | `config/feudalism.php` |
| Commands | `diesirae:*` | `feudalism:*` |

`WorldIntegrityValidator` fails if the connected database is a sibling game.

---

## Design rules

1. User ≠ Character  
2. Dynasty ≠ Realm  
3. Title ≠ Territory ≠ Holding  
4. Realm is a cache from titles and vassalage  
5. Political authority ≠ spiritual authority  
6. A see is not a duchy. The papacy is not a realm.  
7. A prince-bishop is a character with two offices, not one hybrid rank  
8. Mutations live in `app/Actions/{Domain}`  
9. Pure rules live in `app/Domain/{Domain}`  
10. One world date  
11. History over mutable FKs (`is_current` uniqueness)  
12. Content in `database/data/`  

---

## Directory map

```text
app/
  Actions/{Domain}/          # transactional writes
  Domain/{Domain}/           # contracts + services
  Domain/Enums/
  Domain/Support/            # WorldBoundary, Transactional, AfterCommit
  Models/
  Validation/                # integrity harness
  Providers/Domain/          # one provider per domain
  Http/Controllers/Dev/      # inspect only
  Console/Commands/
config/game.php
database/
  data/europa/fixture.json
  migrations/
  seeders/FixtureWorldSeeder.php
docs/
  architecture/DIES_IRAE_ARCHITECTURE.md
  dies_irae/                 # reuse audit set
tests/Feature/{Foundation,Validation,...}
```

Domains with contracts and service providers:

World, Geography, Characters, Dynasties, Titles, Realms, Succession, Diplomacy, Warfare, Economy, Population, Church, Faith, SinVirtue, Corruption, Plague, Heresy, Demons, Apocalypse, Events, NPC, Time, Validation.

Diplomacy, NPC, Heresy, and SinVirtue are reserved modules. They have interfaces and providers. They do not have a live gameplay loop yet.

Economy and Famine are live strategic kernels. See [ECONOMY_AND_FAMINE.md](ECONOMY_AND_FAMINE.md).

Character careers are a live kernel under Characters. See [CHARACTER_CAREERS.md](CHARACTER_CAREERS.md).

---

## Entity hierarchy

```text
World
├── Time (current_date, scheduled_world_events)
├── Geography
│     Region → Territory → Holding
│     Monastery (institution on a monastic holding)
├── Population (territories.population)
├── Characters → Dynasties / DynastyHouses
├── Secular politics
│     Title + TitleOwnership + TitleClaim
│     Realm (cache) ← Titles + VassalRelationship
│     SuccessionLaw
├── Spiritual authority
│     Faith
│     ChurchProvince → See
│     SpiritualOffice + SpiritualOfficeHoldership
│     ClergyStatus
├── Catastrophe
│     PlagueWave → TerritoryPlagueState
│     CorruptionState
│     ApocalypseState
└── Hell
      DemonicFaction → DemonicThreat
      NamedDemon (optional persistent antagonist; incursions do not require one)
```

Title ranks: barony, county, duchy, kingdom, empire.

Spiritual office ranks: priest, abbot, bishop, archbishop, cardinal, pope.

These lists must not overlap. The validator rejects a title whose rank is a spiritual office.

---

## Fixture world (`lys-1348`)

Minimal engine proof, not a canon dump.

| Fact | Key / name |
|------|------------|
| World | `lys-1348` / Kingdom of Lys / 1348-06-24 |
| Dynasty | House of Lys |
| King | Aldric the Steadfast holds `kingdom-lys` |
| Duke | Renard of the Vale holds `duchy-vale`, vassal of Aldric |
| Count | Michel of Cressy holds `county-cressy`, vassal of Renard |
| Bishop | Tomas of Lys holds spiritual office `bishop-of-lys`, no secular title |
| Realm | `kingdom-of-lys`, top liege Aldric |
| Church province | `province-lys` containing `diocese-lys` |
| Town | Lys-port |
| Village | Cressy Village |
| Monastery | Abbey of St. Amand (Benedictine) |
| Plague | Great Mortality on Lys city territory |
| Corruption | Cressy territory |
| Demonic threat | Court of the Ash Prince / Cressy Whisper, **dormant** |
| Apocalypse stage | `great_mortality` |

---

## Commands

| Command | Purpose |
|---------|---------|
| `php artisan db:seed` | Load fixture world |
| `php artisan diesirae:validate-world {world?}` | Integrity report |

---

## Inspect UI

`/dev/inspect` and `/dev/inspect/worlds/{world}` are debug pages. No war, tax, or recruit actions.

Theme is Dies Irae (dark, ash and gold). It is not the Feudalism parchment skin.

---

## Tests

- `tests/Feature/Foundation/WorldBoundaryTest.php`  
- `tests/Feature/Validation/FixtureWorldTest.php`  

These prove the fixture graph, dual-authority separation, vassal chain, plague/corruption/dormant threat, and that a bishop stored as `TitleRank` fails validation.

---

## What is deliberately unfinished

Succession execute, calendar ticks, war, economy collection, sacraments, relics, miracles, NPC AI, full Europe seed, player onboarding.

Economy and famine: [ECONOMY_AND_FAMINE.md](ECONOMY_AND_FAMINE.md).

Named infernal entities: [NAMED_INFERNAL_ENTITIES.md](NAMED_INFERNAL_ENTITIES.md).

See `docs/dies_irae/RECONSTRUCTION_PLAN.md` for the package order.

---

## Related docs

- [FEUDALISM_REUSE_AUDIT.md](../dies_irae/FEUDALISM_REUSE_AUDIT.md)
- [DIES_IRAE_DOMAIN_MAP.md](../dies_irae/DIES_IRAE_DOMAIN_MAP.md)
- [RECONSTRUCTION_PLAN.md](../dies_irae/RECONSTRUCTION_PLAN.md)
- [NAMED_INFERNAL_ENTITIES.md](NAMED_INFERNAL_ENTITIES.md)
- [DEMONIC_THREAT_ENGINE.md](DEMONIC_THREAT_ENGINE.md)
