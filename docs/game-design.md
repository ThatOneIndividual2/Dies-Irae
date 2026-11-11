# Dies Irae design brief

**Audience:** cloud agents working in this repository.  
**Date:** 2026-11-11  
**Status of play:** `docs/DIES_IRAE_VERTICAL_SLICE_STATUS.md` (the August inventory plus the 2026-11-11 finish pass).

This file is the standing brief. It does not replace the domain essays under `docs/architecture/` or `docs/dies_irae/`. When those essays disagree with the playable Salon loop, trust the vertical-slice status and this brief. The August architecture note still says succession, the calendar, and sacraments are unfinished. That sentence is the kernel snapshot. The county loop has moved past it.

## Identity and setting

Dies Irae is a persistent medieval apocalypse grand-strategy and roleplay simulation. The world starts as a recognizable feudal Europe in 1347. The Black Death is the door. Hell enters the map as the order thins. Political authority and spiritual authority stay distinct until that order fails.

The player is one living person, reached through a user account. The account is not an army, a treasury, or a title. A dynasty is not a realm. A title is not a territory and not a holding. A see is not a duchy. The papacy is not a realm. A prince-bishop is one character with two offices.

The apocalypse distorts the medieval rules. It does not replace them on day one. Hell is a polity and a wounded geography. Plague, famine, despair, and ruin act on populations, then on lords.

Feudalism (BFTH), Secession, GOT, and BlastShadows are sibling browser strategy games. They are inspiration and, for Feudalism, a donor of secular contracts. Dies Irae is its own Laravel app. It does not query `feudalism_game`.

## Direction already undertaken

The reconstruction chose a kernel that can hold two authorities and a catastrophe clock before a full war UI or a formable-nation loop. That choice is still the direction.

What shipped, in order:

1. App kernel, world isolation, Actions for writes, Domain for rules, Models for state.
2. Secular graph: worlds, characters, dynasties, titles with ownership history, realms as a cache, vassalage, one world date.
3. Church as a peer authority: sees, spiritual offices, papacy, monasteries. A validator rejects a spiritual rank stored as a title.
4. Catastrophe and hell as kernels: plague, corruption, overlays, apocalypse stages, demonic factions.
5. A thin Blade play shell on one county, then a separate Europa 1347 seed.
6. The 2026-11-11 Salon continuation: a heir, a day-16 death choice, settlement-grain plague on the Aix wave, recorded rites, and a hell pulse that deepens a haunted place.

Stay on that line. Finish a stub or wire a kernel the player can already see. Do not pivot the fantasy, and do not start a second game inside this repo.

## Stack and architecture

PHP 8.0+, Laravel 9.52, MySQL, Blade, PHPUnit 9. Composer package `dies-irae/game`. Namespace `App\`. Commands `diesirae:*`. Config `config/game.php`.

| Concern | Use | Leave alone |
| --- | --- | --- |
| Live database | `dies_irae_game` | `feudalism_game`, `got_game`, other games |
| PHPUnit database | `dies_irae_game_testing` | `feudalism_game_testing` |
| Content | `database/data/` | Hard-coded Europe names in engine classes |
| Mutations | `app/Actions/{Domain}` | A god `app/Services` layer |
| Rules | `app/Domain/{Domain}` | Rules copied into controllers |
| Player HTTP | `PlayController` and `routes/web.php` | New public kill or tick routes |
| Inspect | `/dev/inspect`, `local` and `testing` only | Shipping inspect on the play login |

`WorldBoundary` scopes mutations to one world. `Transactional` wraps writes. Nested calls use savepoints. `WorldIntegrityValidator` fails if the connected database is a sibling game.

History rows use nullable `is_current`. A current row is `true`. A closed row is SQL `NULL`. The boolean cast will store `false` if you assign null on the model and save it, and the unique index then allows only one closed row. Close history with a query-builder update that sets `is_current` to null. This covers title ownership, control history, vassal links, and overlays.

Migrations are additive once a world is seeded. One world date: `worlds.current_date`. Harvest, liturgy, plague, war, and hell share it.

## Worlds

| Slug | Role | How to load |
| --- | --- | --- |
| `provence-1347` | Playable county. 1 November 1347. County of Salon. | `php artisan diesirae:seed-slice` rebuilds this world only |
| `lys-1348` | Fixture for kernel validation. Kingdom of Lys. | `php artisan db:seed`, then `diesirae:validate-world lys-1348` |
| `europa-1347` | Two different seeders share this slug | See below |

`diesirae:seed-1347` builds the campaign world and one archetype login (default `king`). `diesirae:seed-historical` loads the map pack under `database/data/europa/1347/`. Whichever runs first owns `europa-1347`. The other skips. `--force` on the campaign seed rebuilds that world only. Neither seeder is the Salon login.

Salon demo: `lord@diesirae.test` / `password`. Europa archetype emails and the same password live in `config/campaign.php` (`demo_users`) and the README. The seeder prints the login it created.

## Player loop (County of Salon)

Sign in, inspect the ruler, dynasty, titles, realm, map, Church, spiritual state, plague, army, and apocalypse. Raise a levy at Salon (about 80 to 120 men; `game.levy_ratio` is 0.08). March only to adjacent land. Fight the Host of Miramas. Advance the calendar and resolve each event when it opens.

`BeatKey::sequence()` is the ten beats. Day offsets from the start date: 1, 3, 4, 5, 7, 8, 10, 11, 12, 14.

1. Plague appears in Aix
2. Refugees arrive
3. Gui de Pélissanne refuses aid
4. Monastery requests resources
5. Rumors of heresy
6. Cult discovery (Brothers of the Open Grave)
7. First minor demonic manifestation
8. Clergy response
9. Military response
10. Aftermath

Do not add a key to `BeatKey::sequence()`. `VerticalSliceLoopTest` advances until every key in that list is resolved. A new beat becomes a required choice in that test.

`count_stricken` is a separate event, due on start date plus 16 days. It is not a beat. Options: `anoint`, `hide_the_fever`, `yield_the_county`. Audience is null so the heir can still see it after control moves. The ten-beat test stops at the aftermath, so this event stays scheduled there.

Recommended path through the ten beats: send physicians, admit refugees, demand compliance, grant the abbey, ask the bishop, arrest the cult, call clergy, exorcism, attack the manifestation, record the chronicle. Apocalypse moves `ordinary_order` to `great_mortality` to `thinning_veil`.

Yielding the county runs `ProcessCharacterDeath`. Raimond dies of plague. Hugues Adhémar (key `hugues_adhemar`, legitimate son, lay, born 1336) takes the county under `male_preference_primogeniture`, and the user account follows him. Anointing and concealment leave Raimond alive. Do not kill him during the ten beats. That would break the scripted loop and the player's identity.

Play pages are usable. They are not a theme pass. Leave the Feudalism parchment skin alone.

## Major systems

| System | Where it stands |
| --- | --- |
| World, time, geography, characters, dynasties, titles, realms, vassals | Live in the slice and in Europa. Realm is a cache. `top_liege_character_id` is not nullable. |
| Playable control | `users.controlled_character_id` plus `character_control_histories`. Seed reason is `slice_seed`. Succession closes that row, then inserts reason `succession`. `CurrentPlay` loads the controlled character and does not check `is_alive`. |
| Succession | Play path is `PreviewSuccession`, `HeirOrder`, `ChurchEligibilityGate`, `ProcessCharacterDeath`. Law key `male_preference_primogeniture` is global, not per world. Sons by birth, then daughters. No siblings. Church blocks an heir who is excommunicated, under regular vows, or in major orders (deacon and above). `SuccessionService` is still a `domainKey()` facade. Extend the actions. Do not pretend the facade is the loop. |
| Church and papacy | Sees and offices are not titles. Slice seats a bishop, an abbot, a priest, and a pope proxy at Aix. Papacy, investiture, and excommunication exist as kernel actions. They are not extra slice events. |
| Sacraments | `ConferSacrament` records minister, subject, place, date, and validity. Baptism is required before other rites. The slice gives the count one historical baptism dated to birth, then a mass for the dead (abbey grant or prayers) or anointing of the sick (day 16). Eucharist and unction need priest or bishop faculty. Set `orders_grade` to `priest` or `bishop`. The string `abbot` is an office, not a faculty grade. The abbot is `priest` plus `regular`. An invalid rite still records. Miracle economy is kernel-only. |
| Plague | Territory intensity is what the old loop wrote. The Aix choice also creates one settlement per territory and seeds infection batches on Aix. Later days call `PersistedSettlementPlague::tick`. Territory `population` stays a rollup: the tick applies the net soul change, and scripted `addPopulation` updates both layers. Cohort collapse does not kill named characters. `CharacterDeathPort` stays null. Ruin flips when population is under `game.ruin_population_threshold` (80). Saint-Michel starts at 280. |
| Hell | One haunted overlay on Aix, one demonic host stored as an `armies` row, one exorcism through `DemonicThreatEngine::attemptCountermeasure`. After `thinning_veil`, `PulsePlayableHell` runs once per advanced day. Intensity 28 at thinning, 46 at open rifts, 60 at collapse, 80 at the end. The play overlay stays `haunted` until intensity is at least 40 and the incursion is breached. Do not paint the map with `IncursionState::overlayKind()`. That mapper turns manifested into `rifted` and fails the ten-beat test. The full `ThreatWorld` is not persisted. |
| Apocalypse | Coarse stages are the player clock. Kernel `phase_key` is mapped through `CoarseStageMap`. The aftermath writes one chronicle row. Succession writes `succession_summaries`. There is no freeform chronicle writer. |
| Warfare | The army page uses `armies` and `battles`. The doctrine kernel (`warfare_*`, WarDirector) is tested and stays off that page. Do not replace the player table with it. |
| Economy, famine, careers, holy orders, saints, heresy engine, dynamic events, strategic AI | Kernels with their own docs and tests. The Salon player does not drive them day to day. Europa campaign pulses use some of the event and AI machinery. Wire a kernel into Salon only when a current page or beat already calls for it. |
| Diplomacy, Faith, SinVirtue | `DiplomacyService`, `FaithService`, and `SinVirtueService` return a domain key and nothing else. Faith as an institution lives on the Church domain. Virtue and sin live on the spiritual engine. Do not invent a diplomacy screen or a piety button on these facades. |
| NPC daily schedule, trade, buildings, guilds, formable nations, Redis | Not in the slice. |

Deeper essays, when you need a kernel's own rules:

- Church: `docs/architecture/CHURCH_DOMAIN.md`, `PAPACY_AND_CONCLAVE.md`, `HOLY_ORDERS.md`
- Soul and sin: `docs/architecture/SPIRITUAL_STATE_ENGINE.md`
- Plague and people: `docs/architecture/PLAGUE_AND_POPULATION.md`, `ECONOMY_AND_FAMINE.md`
- Hell: `docs/architecture/DEMONIC_THREAT_ENGINE.md`, `NAMED_INFERNAL_ENTITIES.md`
- Apocalypse, war, events, AI, heresy, saints, careers: the matching file in `docs/architecture/`
- Bounded contexts and donor rules: `docs/dies_irae/DIES_IRAE_DOMAIN_MAP.md`, `FEUDALISM_REUSE_AUDIT.md`, `RECONSTRUCTION_PLAN.md`
- Europa pack: `docs/world/1347_WORLD_SEED.md`, `docs/world/1347_DATA_CONFIDENCE.md`
- Patterns worth copying to sibling games: `docs/transferable-innovations.md`

## Hard rules for agents

- Do not deploy. Do not SSH. Do not request or use a droplet key. Do not touch live server folders, Apache, DocumentRoots, or another game's directory. Do not touch live BFTH.
- This tree can be pointed at a live game. Do not reset a world, truncate a table, or rerun a seeder against production unless the task explicitly allows that world. `diesirae:seed-slice` rebuilds `provence-1347` only. Keep that habit.
- Do not rotate, rewrite, or remove committed credentials. `phpunit.xml` contains a database username and password. Note the path if you must mention it. Leave the values in place. Do not print the password. Do not add a new secret. Do not "clean up" `phpunit.xml`.
- Do not enable AutoDev, and do not change AutoDev flags, supervisor code, or live-server config.
- Keep code changes in this repository. Read sibling repos only as inspiration.
- Inspect routes stay on `local` and `testing`.
- Do not put spiritual ranks on `titles`.
- Do not add a beat to `BeatKey::sequence()` to sneak in a feature.
- Do not repaint Aix from haunted to rifted because the hell kernel's overlay map says manifested means rifted.
- Do not let the settlement tick replace territory population with an absolute census. Apply the net delta.
- Close `is_current` history with NULL, via the query builder.

## How to test, and known limitations

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan test
```

PHPUnit forces MySQL through `phpunit.xml`: database `dies_irae_game_testing`, host `127.0.0.1`. Create that database and grant the configured user. Do not edit `phpunit.xml` to point elsewhere.

`Tests\Feature\Events\DynamicEventEngineTest` creates `dies_irae_events_testing` at runtime. The test user needs CREATE and rights on that schema. A privilege error there is an environment gap, not a game failure. Say so in the report.

On 2026-11-11, PHP 8.3.6 and PHPUnit 9.6.36: `php artisan test` reported 294 passed and 7 failed. The 7 were `DynamicEventEngineTest` before the local CREATE grant. After the grant, that class passed 7 tests. Nothing was skipped. The other 294 were not rerun after the grant. Play pages were hit by `VerticalSliceUiTest` and the yield path in `CountyContinuationTest`. A browser was not opened.

Required slice tests:

- `tests/Feature/VerticalSlice/VerticalSliceLoopTest.php`
- `tests/Feature/VerticalSlice/ArmyLoopTest.php`
- `tests/Feature/VerticalSlice/DualAuthorityAndIsolationTest.php`
- `tests/Feature/VerticalSlice/VerticalSliceUiTest.php`
- `tests/Feature/VerticalSlice/EventAudienceResolveTest.php`
- `tests/Feature/VerticalSlice/CountyContinuationTest.php`

The slice world has 11 game events: the ten beats plus `count_stricken`. Isolation tests should expect 11 on `provence-1347` and 0 on any other world.

Known limits agents should not "fix" by accident:

- Named nobles do not die when a cohort collapses.
- Thinning-veil pulses do not open a rift.
- Settlement food is not the lord's granary. Territory `food_stores` stays the scripted store.
- Exorcism can return success, partial, failure, or backlash. Assert that a `countermeasure_attempts` row exists. The demonic army is weakened and kept (strength floors at 10) so the later fight still has a host.
- Calendar catch-up is capped by `game.calendar.catch_up_day_cap` (30).
- Hell and plague pulses are idempotent per world-date where a daily row exists (`hell_daily_pulses`). Do not double-apply them inside one day.
- UI copy should stay plain. Do not add an em dash or other filler the player can see.

Report the runtime version, the command, the database, pass and fail counts, anything skipped, and anything that could not boot. Do not call a filtered run a full suite.

## Open work and priorities

The August status named six next packages. One through four are done inside Salon. The remaining order is still the order:

1. NUTS-lite geography, only while the Salon loop still passes. Europa 1347 already exists. Do not invent a new map first. Bring Church and catastrophe along with any new land.
2. Playable war goals. Keep `armies` and `battles` as the player table. Leave `warfare_*` off the army page until a goal needs a doctrine fact the simple battle cannot store.

Holes inside the current county, to document or to close without a new campaign:

- Death of a named person when their cohort collapses.
- Persisting enough of the hell world that a later day remembers the pulse without rebuilding from overlays.
- Rites beyond requiem, anointing, and one exorcism. Inquisition beyond the cult arrest, burn, and conceal choices.
- A chronicle wider than the aftermath entry and the succession summaries.

Leave these until the two packages above, unless the task you were given is one of these holes:

- Diplomacy, the faith facade, and the sin/virtue facade.
- Trade, buildings, guilds, an NPC daily schedule for the Salon lord, Redis, formable nations.
- The Feudalism Blade theme and community pages.
- Merging this repo into Feudalism, or pointing a seeder at `feudalism_game`.

If a task conflicts with this order, do the task that was asked, say what you deferred, and write the deferral in the pull request and in `docs/DIES_IRAE_VERTICAL_SLICE_STATUS.md`. Do not leave a silent hole. Do not open a second design document that competes with this file. Update this brief when the playable truth changes.
