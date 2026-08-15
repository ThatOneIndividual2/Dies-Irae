# Dies Irae Reconstruction Plan

**Status:** Plan only. No live architecture mutated.  
**Date:** 2026-08-13  
**Companions:** [FEUDALISM_REUSE_AUDIT.md](FEUDALISM_REUSE_AUDIT.md), [DIES_IRAE_DOMAIN_MAP.md](DIES_IRAE_DOMAIN_MAP.md)  
**Donor:** `/var/www/html/Feudalism/feudalism-main` (read-only)  
**Target app:** `/var/www/html/Dies Irae`

---

## Intent

Rebuild Dies Irae as a new Laravel game that **borrows Feudalism's proven secular contracts** and **refuses Feudalism's identity mistakes**. The first work is a kernel that can hold two authorities (political and spiritual) and a catastrophe clock. Features such as a full war UI or a formable-nation loop are not the opening move.

This plan does not write to `feudalism_game`. It does not reset Europa. It does not copy the dual live/foundation schema.

---

## Reconstruction principles

1. **New app, new database.** Suggested name: `dies_irae_game` / `dies_irae_game_testing`. Never the Feudalism DSN.
2. **Single vocabulary from day one.** Character, Dynasty, Title, Realm, Territory, Holding, See, Papacy. No leftover `houses`/`kingdoms`/`users.liege_id`.
3. **Re-implement donor contracts. Do not vendor-copy the tree.** Read Feudalism Actions and tests, then write Dies Irae files. This prevents dragging `Bridge/` and live Services along.
4. **Church in the first wave.** If spiritual authority is added "after the feudal game works," the game becomes Feudalism with demons.
5. **Catastrophe in the first wave.** If plague is a later DLC-shaped module, the apocalypse is a skin.
6. **Content in `database/data/`.** Engine code uses keys. Europe names stay in JSON.
7. **Tests before map chrome.** Integrity of dual authority beats a painted SVG.
8. **Additive migrations only** once any world is seeded.
9. **Local inspect only** for god tools. No public kill/tick routes.
10. **Same host stack as the donor** (PHP 8.0+, Laravel 9, MySQL, Blade, PHPUnit 9) so the environment matches. Do not take a framework leap in the reconstruction.

---

## What is in / out of the first wave

### In (packages 1-10)

- App kernel, world isolation, Actions pattern
- Geography import from files (NUTS fork)
- Characters, dynasties, playable control
- Secular titles, ownership history, succession
- Realms and vassalage (political only)
- One world calendar + scheduled events + character mortality
- Faith, clergy, Church hierarchy, papacy (kernel, not every rite)
- Population + plague + famine + displacement + ruin states
- Corruption, demonic influence, hell overlay, apocalypse stages
- Validation harness, file seeders, local inspect, thin player shell

### Out (explicitly later)

- Port of `WarService` / `TurnService` / `FormableService`
- Full sacrament simulation, miracle economy, inquisition gameplay
- NPC daily AI
- Trade, buildings, guilds
- Redis/queue scale-up
- Feudalism Blade theme and community/forum pages
- Any merge of Dies Irae into the Feudalism repo

---

## Target layout

```text
/var/www/html/Dies Irae/
├── app/
│   ├── Actions/{World,Time,Characters,Dynasties,Titles,Succession,
│   │            Realms,Control,Map,Church,Catastrophe,Hell,Population}/
│   ├── Domain/{Enums,Support,Succession,Time,Simulation,Playable,
│   │           Church,Catastrophe,Hell,Population,Map}/
│   ├── Models/
│   ├── Validation/              # integrity checks (peer idea from GOT, not Feudalism)
│   ├── Http/Controllers/        # thin, split by read domain; Dev/Inspect
│   └── Console/Commands/
├── config/game.php              # not feudalism.php
├── database/
│   ├── data/europa/             # geography, politics, church, campaign
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── docs/dies_irae/              # this audit set
├── resources/views/
├── routes/web.php
└── tests/Feature/{Foundation,Church,Catastrophe,Validation,Lifecycle,Map}/
```

No `app/Services` god layer. No `app/Game` JSON twin. No `Actions/Bridge` to a live User economy.

---

## Phased reconstruction

### Phase 0 — Custody and empty app (before package 1 code)

The workspace directory is currently `root:root` and empty. A human with sudo must:

```bash
sudo chown chavez:www-data "/var/www/html/Dies Irae"
sudo chmod 2775 "/var/www/html/Dies Irae"
```

Then install a clean Laravel 9 app **in that directory**, not a clone of `feudalism-main`. Copy these three docs into `docs/dies_irae/` if they are not already there.

Do not `cp -a` Feudalism. Do not point `.env` at `feudalism_game`.

### Phase A — Secular kernel (packages 1-6)

Enough to have a world, a map of Europe, living characters, lawful titles, and a clock. This is the donor's strongest tissue, rewritten clean.

### Phase B — Identity of the game (packages 7-9)

Church as a peer authority. Catastrophe and hell as first-class simulation. After this phase, a reviewer should be unable to mistake the project for Feudalism.

### Phase C — Proof and shell (package 10)

Validators, seeds, inspect, a playable-looking but thin UI. Then stop and re-audit before war/economy.

### Phase D — Later (not numbered in the first 10)

Warfare, holding economy + tithe, sacraments depth, relics/saints gameplay, holy orders as armies, NPC autonomy, diplomacy.

---

## The first 10 implementation packages

Each package: goal, donor reuse, new work, first tables/actions, acceptance tests, forbidden shortcuts.

### Package 1 — App kernel and world isolation

**Goal.** A bootable Dies Irae app with one `World`, world-scoped mutations, and the Action/Domain/test skeleton.

**Reuse.** **REUSE AS-IS:** `WorldBoundary`, `Transactional`, `AfterCommit`, after-commit domain events, `worlds` shape (date, speed, status, seed).

**New.** Project name, `config/game.php`, empty handler registry, no Feudalism command names.

**First surface.**

- Tables: `worlds`, `users` (auth only: name, email, password, `controlled_character_id` nullable later)
- Actions: `CreateWorld`
- Tests: world create, cross-world mutation rejected

**Forbidden.** Copying `users` resource/army columns. Copying `app/Services`.

### Package 2 — Geography and map adapter

**Goal.** Europe as land, not as politics. File-imported territories with stable map keys.

**Reuse.** **FORK / ADAPT:** `nuts_regions.json` as a content source; Territory model; optional Region adapter for SVG keys. **REUSE AS-IS:** Territory ≠ Title.

**New.** `database/data/europa/geography.json` (or equivalent) produced from a *copy* of NUTS data, then owned by Dies Irae. Overlay columns reserved but unused: plague, famine, despair, corruption, supernatural state, ruin.

**First surface.**

- Tables: `regions` (map keys only), `territories`, `holdings` (castle/town/village; monastic type allowed as holding_type hook only)
- Commands: `diesirae:import-geography` (dry-run default)
- Tests: import idempotency, 1:1 map key, no owner required

**Forbidden.** `owner_id` on regions pointing at users. Seeding from the live Feudalism DB. Creating titles in this package.

### Package 3 — Characters, dynasties, and playable control

**Goal.** Persons and bloodlines. A user controls one living character in one dynasty.

**Reuse.** **REUSE WITH EXTENSION:** Character, Dynasty, DynastyHouse, marriage, parentage, control histories, `PlayableCharacterPolicy`, onboarding ensure.

**New.** No `faith` string as a system. Optional `faith_id` nullable FK reserved for package 7. Death causes enum left open.

**First surface.**

- Tables: `dynasties`, `dynasty_houses`, `characters`, `character_marriages`, `character_relationships`, `character_control_histories`
- Actions: `CreateCharacter`, `CreateDynasty`, `AssignCharacterControl`, `EnsureUserHasPlayableCharacter`
- Tests: control exclusivity, extinct dynasty does not auto-revive, world isolation

**Forbidden.** `users.is_npc` kings. Gold/army on User. Prestige-as-the-only-score (prestige ledger may wait until package 4-5).

### Package 4 — Secular titles, ownership, and succession

**Goal.** Legal political offices with history and fingerprint-locked succession.

**Reuse.** **REUSE AS-IS:** `TitleOwnershipMutator` contract, `SuccessionPlan`, preview/execute, unique current owner. **REUSE WITH EXTENSION:** two laws plus an eligibility port.

**New.** `TitleRank` remains barony→empire only. Eligibility interface `SuccessionEligibilityGate` with a no-op implementation until Church exists.

**First surface.**

- Tables: `succession_laws`, `titles`, `title_ownerships`, `title_claims`, `character_death_processings`, `succession_summaries`
- Actions: grant/transfer/revoke, preview/execute succession, `ProcessCharacterDeath`
- Tests: donor-equivalent ownership and succession cases; stale plan rollback

**Forbidden.** `bishop` / `pope` ranks. Religious claim type used as Church.

### Package 5 — Political realms and vassalage

**Goal.** Derived secular polities. One current liege per vassal.

**Reuse.** **REUSE AS-IS:** realm-as-cache, `AddVassal` / `RemoveVassal`, `DetermineTopLiege`, `GetRealmMembers`. **REUSE WITH EXTENSION:** contracts without pretending `religious_protection` is the Church.

**New.** `DetermineTopLiege` documented to ignore spiritual offices. Investiture table not in this package (belongs with Church).

**First surface.**

- Tables: `realms`, `vassal_relationships`, `feudal_contracts`
- Tests: membership derivation, one current vassalage, realm deletion does not delete titles

**Forbidden.** Papal states as a special-case `Realm` that *is* the papacy. Dual `liege_id` on users.

### Package 6 — World calendar, events, and character mortality

**Goal.** One clock. Characters age and die. Deaths go through `ProcessCharacterDeath`.

**Reuse.** **REUSE WITH EXTENSION:** `ProcessEuropaCalendar` renamed to a generic `AdvanceWorldCalendar`, scheduled event pipeline, `SimulationRandom`, age bands.

**New.** Config `config/game.php` (or `calendar.php`) without Feudalism env names. Event type list reserved for catastrophe/hell. Character mortality remains *personal*, not demographic.

**First surface.**

- Tables: extend `scheduled_world_events` with status/lock/idempotency
- Commands: `diesirae:process-calendar`, `diesirae:process-world-events` (no public HTTP)
- Tests: lock, catch-up cap, deterministic death, pending events cancelled on death

**Forbidden.** A second hourly `TurnService` harvest tick. Page-load world advancement as the authority (lazy catch-up may call the same locked action).

### Package 7 — Spiritual authority kernel

**Goal.** The other half of the game exists as tables and invariants, not as flavor.

**Reuse.** **REUSE AS-IS (pattern only):** office holdership history copied from title ownership. **DO NOT REUSE** faith strings, piety integer, `ClaimType::RELIGIOUS`.

**New (minimum that makes it real):**

- `faiths`
- `clergy_statuses` (history)
- `sees` (jurisdiction over territories)
- `spiritual_offices` + `spiritual_office_holderships`
- `papacies` (one per world, nullable current office)
- `SuccessionEligibilityGate` now asks clergy/excommunication
- Map resolver stub: diocesan mode

**Not required in this package:** full sacrament engine, relic economy, miracle petitions, inquisition loop. Reserve tables or wait for Phase D. Do reserve `sacrament_records` if it is cheap and prevents piety-button design.

**First actions.** `CreateSee`, `AppointToSpiritualOffice`, `DeclarePapalVacancy`, `SetClergyStatus`.

**Acceptance.**

- A character can be king and not bishop, bishop and not king, both, or neither
- Papacy uniqueness per world
- Validators fail if a `titles.rank` is a spiritual office
- Top liege walk does not return the Pope unless he also holds a secular title in that chain

**Forbidden.** Storing the Pope as `titles.rank = empire` named "Papacy".

### Package 8 — Population and opening catastrophe

**Goal.** The Black Death can happen to *places*, not only to named nobles.

**Reuse.** NUTS population integers as **seed only**. Character mortality from package 6 stays. **DO NOT REUSE** static `regions.population` as the system.

**New.**

- Population totals on territory (souls, labor, levy base)
- `plague_waves`, `territory_plague_states`
- `territory_famine_states`
- `displacements`
- `ruin_states` on holding/territory
- Event types: `territory_plague_tick`, `territory_famine_tick`, `population_census`
- Plague can schedule character mortality modifiers or deaths in the infected land

**Acceptance.**

- A wave can depopulate a territory without any character dying
- A character in that territory can also die of plague (cause recorded)
- Refugees move population; source + destination reconcile
- Ruin transition when population crosses a threshold; tax/levy hooks read ruin (even if economy is stub)
- Population never negative

**Forbidden.** Global mortality buff named "Black Death". Zeroing food on User.

### Package 9 — Corruption, hell overlay, apocalypse clock

**Goal.** The world can be spiritually wounded and the age can advance.

**Reuse.** Scheduled events + world lock. Map version bump when overlays change.

**New.**

- `corruption_states` (character, territory)
- `demonic_factions`
- `demonic_influences`
- `supernatural_overlays` on territories
- `apocalypse_states` on world (stage, signs)
- Event types: `territory_despair_tick`, `hell_incursion_pulse`, `apocalypse_stage_check`
- Despair state (territory + character) if not already started in package 8

**Acceptance.**

- A titled county can be legally held and rifted at once
- A demonic faction is not a `Realm` and not a `Dynasty`
- Stage changes are idempotent and recorded
- Exorcism/miracle tables may still be stubs, but influence has a place for them to target

**Forbidden.** One hell `Kingdom`. Recoloring the political map as the only hell representation.

### Package 10 — Validation, seeding, inspect, thin shell

**Goal.** The kernel can be seen, seeded, and refused when inconsistent.

**Reuse.** **REUSE WITH EXTENSION:** local inspect UI idea, dry-run imports, factories. **FORK / ADAPT:** character/title/region/map page shells. Peer idea from GOT: `app/Validation` + `validation_expectations.json` (not GOT lore).

**New.**

- Validators: world isolation, one current title owner, one current spiritual holder per office, one papacy, see ≠ title, population bounds, overlay/legal divergence allowed
- Seed packs: `campaign.json`, `geography.json`, `politics.json`, `church.json` (minimal historical 14th-c. slice, not all of Europe on day one)
- Routes: auth, dashboard, character, title, see, territory, map (few modes), apocalypse status, `/dev/inspect`
- No war/recruit/tax buttons

**Acceptance.**

- `php artisan diesirae:validate-world` fails on a bishop stored as a title rank
- A short historical slice seeds (a kingdom, a duchy, a diocese, a monastery, a plague starting province)
- Dev inspect shows both authorities and catastrophe state
- Player shell never shows User gold/army

**Forbidden.** Porting `ActionController`. Porting Feudalism CSS as the product identity. Seeding from `feudalism_game`.

---

## Suggested first historical slice (seed, not engine)

Do not wait for 1111 counties to be politically real. Import geography widely if cheap; attach politics and church to a **narrow slice** for tests:

- One kingdom (e.g. France or a fragment of the Empire)
- A handful of counties and one duchy
- One archdiocese, two dioceses, one monastery
- A living king, a living bishop, a living abbot, a playable minor lord
- Plague seeded in one port or city
- Papacy exists in the world even if Rome is outside the playable slice (vacancy or distant holder is allowed)

Widen content after the validators hold.

---

## Mapping donor commands to Dies Irae

| Feudalism | Dies Irae |
|-----------|-----------|
| `feudalism:tick` | **Do not port** in the first 10 |
| `feudalism:process-calendar` | `diesirae:process-calendar` |
| `feudalism:process-world-events` | `diesirae:process-world-events` |
| `feudalism:bridge-regions-to-territories` | Fold into `diesirae:import-geography` |
| `feudalism:create-map-titles` | Later content command, after package 4 |
| `feudalism:map-audit` | `diesirae:validate-world` + map audit |
| `feudalism:kill-character` | Dev-only, local/testing |
| `/dev/feudal` | `/dev/inspect` |

---

## Risk register

| Risk | Why it happens | Mitigation |
|------|----------------|------------|
| Feudalism-with-demons | Church delayed until "after MVP feudal" | Package 7 is in the first 10 and blocks title ranks |
| Dual schema again | Copying Feudalism tree "to go faster" | Re-implement; no `Actions/Bridge` to a live layer |
| Two clocks again | Porting `TurnService` beside the calendar | Harvest waits for Phase D and must use world date |
| Production accident | Pointing `.env` at `feudalism_game` | Different DB name; never run Feudalism seeders here |
| Scope flood | Building relics, miracles, AI, war together | Packages 7-9 are kernels, not full gameplay |
| Empty Europe | Waiting for all NUTS titles before testing | Wide geo import, narrow politics/church slice |
| Permission block | `Dies Irae` owned by root | Phase 0 chown before any app files |

---

## Definition of done for the reconstruction wave

The first 10 packages are done when all of the following are true:

1. Dies Irae boots on its own database.
2. A world has a date, a map adapter, characters, secular titles, and vassalage.
3. A see and a papacy exist that are not rows in `titles`.
4. Plague can empty a territory and also kill a named character.
5. A county can be legally held and supernaturally wounded at once.
6. Validators reject a merged political/spiritual office model.
7. Feudalism production is untouched.
8. A reviewer reading the character and see pages can tell this is Dies Irae.

Only then open Phase D (war, economy depth, sacraments, relics, orders as armies).

---

## Immediate next action

1. Chown the workspace (Phase 0).
2. Install the empty Laravel 9 app.
3. Place this doc set at `docs/dies_irae/`.
4. Start Package 1. Do not start a war system. Do not copy `feudalism-main` into the tree.
