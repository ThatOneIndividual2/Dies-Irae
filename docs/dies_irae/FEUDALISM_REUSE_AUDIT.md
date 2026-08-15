# Feudalism Reuse Audit

**Status:** Reconstruction audit (read-only of donor)  
**Donor:** `/var/www/html/Feudalism/feudalism-main`  
**Target:** Dies Irae  
**Date:** 2026-08-13  
**Stack of donor:** PHP 8.0+, Laravel 9.52, MySQL, Blade + Bootstrap 5, PHPUnit 9

This document is an architecture and reuse audit of Feudalism. It does not authorize copying the live Feudalism database, resetting Europa, or mutating production. Dies Irae must be reconstructed as its own game.

---

## Verdict in one page

Feudalism is a **dual-layer Laravel app**:

1. A **live User-centric multiplayer loop** (`users`, `houses`, `kingdoms`, `regions`, `WarService`, `TurnService`). This is playable and fat.
2. A **parallel Character-centric foundation** (`worlds`, `characters`, `dynasties`, `titles`, `realms`, `territories`, succession, calendar). This is tested and the real donor.

The foundation is the architectural gift. The live loop is a warning.

**Copy the contracts. Do not copy the identity model.**

| Donor layer | Maturity | Dies Irae verdict |
|-------------|----------|-------------------|
| Actions + Domain + world scoping | Implemented, tested | **REUSE AS-IS** (pattern) |
| Title ownership history + succession plan fingerprint | Implemented, tested | **REUSE WITH EXTENSION** |
| Playable dynasty lifecycle (death, control, extinction) | Implemented, tested | **REUSE WITH EXTENSION** |
| Scheduled world events + Europa calendar + character mortality | Implemented, tested | **REUSE WITH EXTENSION** |
| Territory / title / holding separation | Schema + map bridge | **REUSE WITH EXTENSION** |
| NUTS Europe SVG map + political map cache | Implemented, tested | **FORK / ADAPT** |
| Live war / economy / diplomacy on `User` | Playable, untested | **FORK / ADAPT** (ideas only) |
| Holdings, contract tax/levy, realm authority | Stub columns | **FORK / ADAPT** |
| Faith / piety / religion | Display strings | **DO NOT REUSE** as a system |
| Population simulation | Static integers | **DO NOT REUSE** as a system |
| NPC AI | Seeded `users.is_npc` only | **DO NOT REUSE** |
| User-fat identity + dual liege + dual vocabulary | Live debt | **DO NOT REUSE** |
| Legacy `app/Game` JSON world | Orphaned | **DO NOT REUSE** |

Feudalism's own design rule is "no premature simulation" for religion, trade, AI, and disease. Dies Irae's core fantasy **is** that simulation. Those extension points are empty on purpose. Filling them by stuffing demons into `characters.faith` would produce Feudalism with a skin.

---

## 1. Directory-level architecture map

```text
/var/www/html/Feudalism/feudalism-main/
├── app/
│   ├── Actions/                 # 70 invokable mutation classes (foundation)
│   │   ├── Bridge/              # User↔Character, Region↔Territory, legacy import
│   │   ├── Characters/
│   │   ├── Claims/
│   │   ├── Contracts/
│   │   ├── Control/
│   │   ├── Dynasties/
│   │   ├── Map/
│   │   ├── Prestige/
│   │   ├── Realms/
│   │   ├── Relationships/
│   │   ├── Succession/
│   │   ├── Time/
│   │   └── Titles/
│   ├── Console/Commands/        # 16 artisan commands
│   ├── Domain/                  # Pure logic (enums, succession, simulation, map, playable)
│   ├── Events/                  # 20 domain events, dispatched, no listeners
│   ├── Game/                    # Orphaned JSON world (LandService, PoliticsService, WorldRepository)
│   ├── Http/Controllers/        # Auth, Game (reads), Action (writes), Dev inspect
│   ├── Models/                  # 34 Eloquent models (live + foundation)
│   ├── Providers/AppServiceProvider.php
│   ├── Services/                # Live loop: Turn, War, Realm, Formable
│   └── Support/                 # NutsRegionCatalog, helpers
├── config/
│   ├── feudalism.php            # Live tick/recruit/formable balance
│   └── feudal_calendar.php      # Speed, mortality bands, event types
├── database/
│   ├── data/nuts_regions.json   # 1111 NUTS-3 regions
│   ├── factories/               # Foundation factories
│   ├── migrations/              # 8 feudal-specific + Laravel defaults
│   └── seeders/                 # WorldSeeder (NUTS + NPC kings), FeudalFoundationSeeder (Valenreach)
├── docs/
│   ├── architecture/            # Current + foundation + calendar + map + lifecycle
│   └── design/                  # vision.md, dynasty-and-succession.md
├── public/js/map-interactive.js
├── resources/views/             # Blade: game/, dashboard/, auth/, dev/feudal/, leftover root duplicates
├── routes/web.php               # All live routes
├── routes/api.php               # Sanctum stub
├── storage/app/world/           # Legacy JSON, unused
└── tests/Feature/               # Foundation, Lifecycle, Calendar, Map (~61 cases)
```

**Classification: REUSE WITH EXTENSION** for the modular-monolith layout (`Actions/`, `Domain/`, `Models/`, `tests/Feature/`).

**Classification: DO NOT REUSE** for `app/Game/`, root-level leftover views, `app/Services/` as the home of core rules, and any copy of the dual live/foundation tree.

Dies Irae should start as a single Character-centric tree. No parallel `houses`/`kingdoms`/`users.liege_id` leftover layer.

---

## 2. Major domains and services

### Foundation domains (Character-centric)

| Domain | Owner classes | What it actually does |
|--------|---------------|------------------------|
| World | `World`, `AdvanceWorldTime`, `ProcessEuropaCalendar` | Isolated campaign clock, speed, seed, map version |
| Characters | `CreateCharacter`, `RecordCharacterDeath`, `ProcessCharacterDeath` | Persons with parentage, stats, piety column, death orchestration |
| Dynasties | `CreateDynasty`, `CreateHouse`, `DetermineHouseHead` | Bloodline + cadet houses |
| Titles | `CreateTitle`, `TitleOwnershipMutator`, `GrantTitle`, `TransferTitle`, `RevokeTitle` | Legal offices with append-only ownership |
| Claims | `CreateClaim`, `InheritClaims`, `ExpireClaim` | Title claims, including unused `religious` type |
| Realms | `CreateRealm`, `AddVassal`, `RemoveVassal`, `DetermineTopLiege`, `GetRealmMembers` | Derived political aggregate |
| Contracts | `CreateFeudalContract`, `CalculateContractTax`, `CalculateContractLevies` | Rate math; not collected on tick |
| Succession | `PreviewSuccession`, `ExecuteSuccession`, strategies | Two laws; fingerprint-locked execute |
| Control | `AssignCharacterControl`, `TransferCharacterControl`, `ReleaseCharacterControl` | User owns one living character |
| Time | `ScheduleWorldEvent`, claim/process/mark pipeline | Aging, mortality, scheduled death |
| Map | `MapPoliticalStateResolver`, `BuildPoliticalMapData` | Cached political coloring |
| Prestige | `AwardPrestige` | Explainable ledger |
| Relationships | `CreateCharacterRelationship` | Store only; no opinion sim |
| Bridge | `EnsureUserHasPlayableCharacter`, region↔territory linkers | Soft join between live and foundation |

**Classification: REUSE WITH EXTENSION.** These are the correct bounded contexts for secular feudal play. Extend them. Do not replace them with User rows.

### Live services (User-centric)

| Service | Path | What it actually does |
|---------|------|------------------------|
| `TurnService` | `app/Services/TurnService.php` | Personal influence/stamina tick + realm harvest/tribute/war tick |
| `WarService` | `app/Services/WarService.php` | Person-vs-person war, fronts, deploy, occupy, settle |
| `RealmService` | `app/Services/RealmService.php` | Claim region, press land claim, recruit, tax, treaty, vassalage, join house |
| `FormableService` | `app/Services/FormableService.php` | Config-driven formable kingdoms |

**Classification: FORK / ADAPT.** Keep the *ideas* (owner vs controller, border fronts, harvest multipliers, ledger). Rewrite onto Character / Title / Territory. Do not port `User` as the lord.

### Orphaned / stub domains

| Piece | Status | Verdict |
|-------|--------|---------|
| `app/Game/*` JSON world | Orphaned from routes | **DO NOT REUSE** |
| Holdings economy | Schema + seeder only | **FORK / ADAPT** (empty vessel) |
| `feudal_contracts.religious_protection` | Boolean flag | **DO NOT REUSE** as Church |
| `characters.faith` / `characters.piety` | Strings / integer | **DO NOT REUSE** as Faith domain |
| `regions.religion` | Map display | **DO NOT REUSE** as Faith domain |
| Domain events | Dispatched, no listeners | **REUSE AS-IS** (pattern: after-commit, add listeners only when they work) |

---

## 3. Database / schema overview

### Live tables (do not become Dies Irae source of truth)

`users` (account + character + army + treasury), `houses`, `kingdoms`, `regions`, `vassalages`, `wars`, `war_fronts`, `deployments`, `treaties`, `land_claims`, `guilds`, `guild_region`, `ledger_entries`, `game_states`.

Key live columns that look useful and are the wrong grain:

- `users.gold/food/wood/stone`, unit counts, `liege_id`, `faith`, `is_npc`
- `regions.owner_id` vs `controller_id` vs `kingdom_id` (good *distinction*, bad *subject*)
- `regions.population`, `wealth`, `loyalty`, yields (static)

**Classification: DO NOT REUSE** as primary schema. **REUSE WITH EXTENSION** for the owner/controller/kingdom distinction as a *rule*, re-homed onto Territory / Title / Realm.

### Foundation tables (donor schema)

From `2026_08_03_000010` plus lifecycle, calendar, and map migrations:

| Table | Role |
|-------|------|
| `worlds` | Isolated world: date, speed, status, seed, `political_map_version` |
| `succession_laws` | Law catalog (`agnatic_primogeniture`, `partition`) |
| `dynasties` / `dynasty_houses` | Bloodline and cadet branches |
| `characters` | Person: parentage, spouse, stats, `faith` string, `piety` int, gold |
| `character_marriages` | Marriage history |
| `character_relationships` | Extensible typed links |
| `territories` | Physical geography; optional 1:1 `region_id` |
| `holdings` | Castle/town/temple/village stubs (`base_tax`, `base_levy`) |
| `titles` | Legal office: rank, de jure liege, parent, capital, primary territory |
| `title_ownerships` | Append-only; unique `(title_id, is_current)` |
| `title_claims` | Inheritance/dispossessed/fabricated/marriage/grant/**religious**/event |
| `realms` | Cached top liege + primary title + authority + treasury |
| `feudal_contracts` | Tax/levy rates + rights flags |
| `vassal_relationships` | Character vassalage; unique current vassal |
| `prestige_transactions` | Explainable prestige |
| `character_control_histories` | User↔character control audit |
| `scheduled_world_events` | Queue with status, lock, idempotency |
| `character_death_processings` | Idempotent death |
| `succession_summaries` | Durable player death report |
| `region_territory_import_runs` | Map bridge checkpoints |

**Classification: REUSE WITH EXTENSION.**

Reuse the history patterns (`is_current` nullable unique, `world_id` on every row, lock-then-mutate). Extend with new first-class tables for Faith, Church, catastrophe, and hell. Do not widen `characters.faith` into those systems.

### Naming collision (must not be copied)

| Concept | Foundation | Live leftover |
|---------|------------|---------------|
| Dynasty | `dynasties` | `houses` |
| House | `dynasty_houses` | `houses` |
| Title | `titles` | `users.title` string |
| Realm | `realms` | `kingdoms` |
| Vassalage | `vassal_relationships` | `vassalages` + `users.liege_id` |
| Territory | `territories` | `regions` |
| Claim | `title_claims` | `land_claims` |

Dies Irae uses **one vocabulary**. If a leftover name is kept for map SVG keys, it is a presentation adapter, not a second political system.

---

## 4. Title / dynasty / realm / succession

### What exists

- Title is a legal office, not land. Ranks: barony, county, duchy, kingdom, empire.
- De facto tree: `parent_title_id`. De jure tree: `de_jure_liege_title_id` (schema stronger than gameplay).
- Ownership is history, not a mutable FK on the title.
- `TitleOwnershipMutator` is the mutation contract: transaction, lock, world check, close old row, insert current, after-commit events, map version bump.
- Dynasty is family. Realm is a cache derived from titles + vassal links.
- Succession: `PreviewSuccession` builds a `SuccessionPlan`; `ExecuteSuccession` refuses a stale fingerprint.
- Laws implemented: agnatic primogeniture, basic partition.
- Eligible heirs today: living legitimate children only. No uncles, no clergy exclusion, no designation, no elective.
- Player control is dynasty-scoped and can diverge from legal title heirs.

### Classification

| Piece | Verdict | Why |
|-------|---------|-----|
| Title ≠ Territory ≠ Holding | **REUSE AS-IS** | Non-negotiable and already correct |
| Dynasty ≠ Realm | **REUSE AS-IS** | Matches Dies Irae (bloodline vs polity) |
| Ownership history + mutator | **REUSE AS-IS** | Best-tested mutation in the donor |
| SuccessionPlan fingerprint | **REUSE AS-IS** | Prevents preview/execute drift |
| ProcessCharacterDeath orchestration | **REUSE WITH EXTENSION** | Add plague, war, possession, execution, martyrdom causes; keep idempotency |
| Two succession strategies | **REUSE WITH EXTENSION** | Need cognatic, seniority, elective, and **clerical ineligibility** |
| De jure columns | **REUSE WITH EXTENSION** | Needed for collapsing medieval order vs surviving legal memory |
| Title ranks as the only office system | **DO NOT REUSE** | Bishop, abbot, legate, pope are not extra `TitleRank` values |
| `ClaimType::RELIGIOUS` as Church | **DO NOT REUSE** | A string on a secular claim is not spiritual authority |

### Dies Irae fork points

1. Secular titles remain this system.
2. Spiritual offices are a **peer domain** (see domain map). A character may hold a county and not a see, or a see and not a county. Investiture is a relation between the two, not a rank on one table.
3. Holy orders and monasteries are corporations, not dynasties and not realms.
4. Succession must ask the Church domain: vows, excommunication, and clerical status can remove heirs without the title system knowing theology.

---

## 5. Map / territory / region

### What exists

- 1111 NUTS-3 European regions in `database/data/nuts_regions.json`.
- SVG paths keyed by `map_key` in `resources/views/map.blade.php`.
- JS controller `public/js/map-interactive.js` (`FeudalismMap`).
- Foundation `territories` optionally 1:1 with live `regions`.
- Commands: bridge, create county titles, import holders, map-audit, rebuild cache.
- Map modes: political/legacy ownership, legal titles, realms, dynasties, military control, claims.
- Display-only modes already in JS: culture, religion, government, population, terrain.
- Cache key: `political_map:{worldId}:{mode}:{version}`.

### Classification

| Piece | Verdict | Why |
|-------|---------|-----|
| Europe NUTS catalog as geography seed | **FORK / ADAPT** | Right continent and grain for 14th-century Europe; re-seed as Dies Irae content, not Feudalism live rows |
| SVG + pan/zoom + mode API | **FORK / ADAPT** | Keep the interaction model; new modes and new art direction |
| Territory as physical geography | **REUSE AS-IS** | Correct grain |
| Region as map adapter | **REUSE WITH EXTENSION** | Keep if SVG needs a stable key table; do not let it own politics |
| Owner vs controller on land | **REUSE AS-IS** (as a rule) | Law vs occupation vs later hell occupation |
| Political map version bump | **REUSE AS-IS** | Cheap invalidation |
| Religion map mode from a string | **DO NOT REUSE** | Faith map must read Church / heresy / cult domains |
| Auto-sync title holder → region owner | Already refused by donor | Keep refused. Succession must not silently rewrite geography. |

### Dies Irae map modes that Feudalism cannot color today

Faith adherence, diocesan jurisdiction, plague intensity, famine, despair, corruption, hell incursion, ruin, refugee pressure, relic presence. These are new resolvers, not new strings on `regions.religion`.

---

## 6. Political authority

### What exists

Foundation: character vassalage, optional contract, top-liege walk, realm cache, `realm_authority` column with no enforcement.

Live: `users.liege_id` **and** `vassalages` (can diverge), treaties, land claims on regions, formable kingdoms.

### Classification

| Piece | Verdict | Why |
|-------|---------|-----|
| Character vassalage + one current liege | **REUSE AS-IS** | Correct subject |
| Realm as cache, not ledger | **REUSE AS-IS** | Prevents a third ownership source |
| Feudal contract rates/rights | **REUSE WITH EXTENSION** | Add secular-only rights; spiritual rights belong to Church law |
| `DetermineTopLiege` | **REUSE WITH EXTENSION** | Must never walk papal or diocesan links |
| Live dual liege | **DO NOT REUSE** | Documented donor risk |
| Treaties on User | **FORK / ADAPT** | Need character/realm treaties plus truce with Church and with hell factions |
| Formable tags in `config/feudalism.php` | **DO NOT REUSE** | Dies Irae starts from a recognizable medieval order, then breaks it. It does not unlock France from a formable checklist. |
| Realm authority enforcement | Stub | **FORK / ADAPT** | Authority must degrade under plague, heresy, and papal interdict |

### Hard rule for Dies Irae

Political authority is titles, vassalage, realms, and secular law.

Spiritual authority is sees, orders, sacraments, and the papacy.

A bishop who is also a prince is a **dual-office character**, not a dual-use title row.

---

## 7. Warfare

### What exists (live only)

`WarService`: declare war (15 influence), border fronts from neighbors, deploy infantry/cavalry/archers/siege, pressure → occupation, hourly war tick, settlements including vassalize. Armies live on `users`.

Foundation: no Army model, no battle deaths, no war-front map overlay, `CalculateContractLevies` unconnected.

### Classification: FORK / ADAPT

Reuse the *shape*: war, fronts on territories, deployments, occupation distinct from legal ownership, settlement types.

Reject the *subject*: User vs User with influence as mana.

Dies Irae needs wars that can be:

- Feudal (claim, succession, contract breach)
- Religious (crusade, heretic suppression, interdict aftermath) without making the Pope a `War.aggressor_id` User
- Demonic (incursion, defense of a see, failed exorcism becoming a front)
- Collapsing (levies vanish because the county is dead, not because the player forgot to click Recruit)

Do not port `WarService` file-for-file. Write a Character/Realm/Army action layer. Connect levy math to holdings **and** to population mortality.

---

## 8. Economy

### What exists

Live: User resources, region yields, loyalty and guild-strike multipliers, tribute, `ledger_entries`, recruit costs in config.

Foundation: `holdings.base_tax/base_levy`, `characters.gold`, `realms.treasury`, contract calculators. No tick.

Absent: buildings, trade, holding income, character-level tax collection.

### Classification: FORK / ADAPT

Keep: ledger-of-record, holding as the economic atom, owner vs controller, yield multiplied by local state.

Reject: four cartoon resources on the account, guild strikes as the main disruption, taxes as a button on `ActionController`.

Dies Irae economy is a **survival and collapse** economy first: harvest, tithe, famine, abandoned fields, monastic granaries, ruined towns, refugee mouths. Gold remains, but food and labor become existential. Trade can wait.

`holding_type = temple` is not a monastery system.

---

## 9. Population / demographics

### What exists

`regions.population` and `territories.population` as seeded integers. Map mode colors them. Character mortality does not change them. No births at population scale. No migration.

Donor docs explicitly defer disease and population sim.

### Classification: DO NOT REUSE as a system. FORK / ADAPT the column as a seed input only.

Dies Irae requires a first-class demographic domain:

- Population on territory (and later settlement)
- Mortality from plague, famine, war, despair, hell
- Displacement / refugees
- Ruin when population crosses collapse thresholds
- Labor and levy derived from living people, not from `users.infantry`

Character mortality (nobles, clergy) and population mortality (the county) are **different clocks** that must be able to affect each other.

---

## 10. Clock / turn / tick

### What exists

Two clocks that do not call each other:

| Clock | Command | Advances |
|-------|---------|----------|
| Legacy ticks | `feudalism:tick` + page-load `ensureTicks` | Harvest, tribute, war, `game_month` |
| Europa calendar | `feudalism:process-calendar` | World date, aging, character mortality |

Calendar: speed table, 3600s = 1 day at speed 2, 30-day catch-up cap, world row lock, scheduled event pipeline with idempotency and handler registry. Deterministic `SimulationRandom`.

Event types today: age milestone, mortality evaluation, scheduled death.

### Classification: REUSE WITH EXTENSION

Reuse: one authoritative world date, scheduled events instead of full-table scans, handler registry, world lock, deterministic RNG, catch-up cap, no public web mutators for time.

Extend event types immediately in design (implement in later packages):

- `territory_plague_tick`
- `territory_famine_tick`
- `territory_despair_tick`
- `apocalypse_stage_check`
- `hell_incursion_pulse`
- `sacrament_calendar` / feast / relic translation
- `population_census` (rare)

**DO NOT REUSE** the dual-clock split as an end state. Dies Irae may keep a cheap personal stamina tick later, but harvest, war, plague, and liturgy must share one world date. Two clocks were a donor compatibility hack.

---

## 11. NPC architecture

### What exists

Seeded historical-ish `users.is_npc` kings. They own regions and can be war targets because they are Users. Foundation characters have no `is_npc` flag. No planner, no daily NPC actions, no clergy AI, no faction AI.

### Classification: DO NOT REUSE the User-NPC pattern. FORK / ADAPT the idea of seeded historical persons as Characters.

Dies Irae NPCs are:

- Secular lords (characters with titles)
- Clergy (characters with spiritual offices)
- Monastic and holy-order corporations (not persons)
- Demonic factions (not dynasties)
- Populations and cults (not characters)

Autonomy comes later. Seed correctness comes first. Do not create 1111 NPC User accounts to hold Europe.

---

## 12. Validation / testing

### What exists

~61 feature tests, no unit suite:

- `tests/Feature/Foundation/*` (character, title, claim, realm, succession, prestige, world time)
- `tests/Feature/Lifecycle/*` (onboard, control, death, authz)
- `tests/Feature/Calendar/EuropaCalendarTest.php`
- `tests/Feature/Map/TitleMapIntegrationTest.php`

Strengths: world boundary, transactional rollback, succession fingerprint, control exclusivity, map audit conflicts.

Gaps: no WarService/RealmService tests, no Policies, no Form Requests, no concurrent lock stress, no spiritual invariants (because the domain is absent).

### Classification: REUSE WITH EXTENSION

Reuse the test *shape* and the invariants (one current owner, one current control, world isolation, death idempotency).

Add Dies Irae invariants that Feudalism cannot express:

- No spiritual office stored as a secular `TitleRank`
- Papacy uniqueness per world
- Excommunicated characters cannot receive certain sacraments
- Territory hell-state cannot keep a functioning parish without explicit exception
- Population cannot go negative; ruin is a state transition
- Political map and faith map can disagree

Look at GOT's later validation harness (`app/Validation`, seed expectations) as a *peer example*, not a donor. Feudalism itself has no integrity validator class.

---

## 13. Admin / import / seeding

### What exists

16 artisan commands (tick, calendar, events, mortality preview, kill character, preview succession, character status, repair control, inspect world, region bridge, create map titles, import holders, map audit, rebuild cache, NUTS extract).

Seeders: `WorldSeeder` (live Europa NUTS + NPC kings + demo player), `FeudalFoundationSeeder` (fictional Valenreach, does not touch NUTS).

Dev UI: `/dev/feudal` local-only inspect.

Import runs table for resumable bridges.

### Classification: REUSE WITH EXTENSION (tooling pattern). DO NOT REUSE (Feudalism seeds and command names).

Reuse: dry-run default, checkpointed imports, local inspect, CLI preview of succession/mortality, repair tools.

Reject: seeding Dies Irae from `feudalism_game`, demo `player@feudalism.test`, Valenreach as the live world, any command that writes Feudalism production.

Dies Irae imports **files** (`database/data/europa/...`), never the live Feudalism schema.

---

## 14. UI pages and controllers

### Controllers

| Controller | Role | Verdict |
|------------|------|---------|
| `AuthController` | Session auth; register calls playable-character ensure | **FORK / ADAPT** |
| `GameController` | All reads + map API + title/region pages | **FORK / ADAPT** (split by domain) |
| `ActionController` | All live writes | **DO NOT REUSE** as a god object |
| `FeudalInspectController` | Local inspect | **REUSE WITH EXTENSION** |
| `RegionController` | Orphaned | **DO NOT REUSE** |

### Player pages worth forking as shells

`/dashboard`, `/character`, `/dynasty-ended`, `/titles/{title}`, `/regions/{slug}`, `/map`, `/realms`, auth pages.

### Pages that encode the wrong game

`/military`, `/economy`, `/guilds`, `/politicians`, `/forum`, `/community` leaderboard, formable-kingdom chrome, influence-as-mana recruit.

**Classification: FORK / ADAPT** the character, title, region, map, and extinction shells. **DO NOT REUSE** Feudalism theming, copy, or the User resource panel as the player fantasy.

New first-class pages (not in donor): diocese, papacy, monastery, relic, plague, hell front, confession/sacrament, apocalypse chronicle.

---

## System-by-system scorecard

| # | System | Verdict | One-line why |
|---|--------|---------|--------------|
| 1 | Directory / modular monolith | **REUSE WITH EXTENSION** | Actions + Domain + tests; drop dual tree |
| 2 | Foundation domains | **REUSE WITH EXTENSION** | Correct secular core |
| 3 | Live Services | **FORK / ADAPT** | Ideas, not User grain |
| 4 | Foundation schema | **REUSE WITH EXTENSION** | History + world_id |
| 5 | Live schema | **DO NOT REUSE** | Fat User, dual liege |
| 6 | Titles / succession | **REUSE WITH EXTENSION** | Best donor code; no Church ranks |
| 7 | Map / territory | **FORK / ADAPT** | Europe yes; modes must grow |
| 8 | Political authority | **REUSE WITH EXTENSION** | Keep secular; isolate spiritual |
| 9 | Warfare | **FORK / ADAPT** | Fronts yes; User armies no |
| 10 | Economy | **FORK / ADAPT** | Holdings yes; cartoon resources no |
| 11 | Population | **DO NOT REUSE** | Integers are not a people |
| 12 | Clock / events | **REUSE WITH EXTENSION** | One world date; more event types |
| 13 | NPC | **DO NOT REUSE** | No User-NPC kings |
| 14 | Tests | **REUSE WITH EXTENSION** | Add dual-authority invariants |
| 15 | Admin / import | **REUSE WITH EXTENSION** | File seeds, not live DB |
| 16 | UI | **FORK / ADAPT** | Shells only |
| 17 | Faith-as-string | **DO NOT REUSE** | Would make "Feudalism with demons" |
| 18 | Legacy JSON game | **DO NOT REUSE** | Dead layer |

---

## What Dies Irae must not inherit

1. **User as lord.** Account, person, army, and purse must split on day one.
2. **Two political vocabularies.** One set of words, one set of tables.
3. **Religion as a column.** Catholic institutions are the other half of the game.
4. **Two clocks forever.** Compatibility hack, not a design.
5. **Formable-nation loop.** The world begins already medieval, then the order dies.
6. **Any write to `feudalism_game`.** Donor stays live and untouched.

---

## Donor files to treat as contracts (read, then re-implement)

| Concern | Path |
|---------|------|
| Domain rules | `docs/architecture/FEUDAL_DOMAIN_ARCHITECTURE.md` |
| Lifecycle | `docs/architecture/PLAYABLE_DYNASTY_LIFECYCLE.md` |
| Calendar | `docs/architecture/WORLD_CALENDAR_AND_MORTALITY.md` |
| Map bridge | `docs/architecture/TITLE_AND_MAP_INTEGRATION.md` |
| Foundation migration | `database/migrations/2026_08_03_000010_create_feudal_foundation_tables.php` |
| Title mutator | `app/Actions/Titles/TitleOwnershipMutator.php` |
| Death orchestrator | `app/Actions/Characters/ProcessCharacterDeath.php` |
| Calendar | `app/Actions/Time/ProcessEuropaCalendar.php` |
| Event pipeline | `app/Actions/Time/ScheduleWorldEvent.php` and siblings |
| Succession plan | `app/Domain/Succession/SuccessionPlan.php` |
| World boundary | `app/Domain/Support/WorldBoundary.php` |
| NUTS data | `database/data/nuts_regions.json` (content fork, not live rows) |
