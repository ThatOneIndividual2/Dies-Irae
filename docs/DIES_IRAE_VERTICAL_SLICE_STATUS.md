# Dies Irae vertical slice status

**Date:** 2026-08-13  
**World:** `provence-1347` (1 November 1347, County of Salon)  
**Login:** `lord@diesirae.test` / `password`  
**Database:** `dies_irae_game` (live) / `dies_irae_game_testing` (PHPUnit). Neither is Feudalism.

The first complete playable loop is in. The player is Raimond Adhémar, Count of Salon. Ten campaign beats, a human battle, a demonic manifestation, Church and plague decisions, and an apocalypse stage change all run in one world.

## How to play the loop

1. Sign in as the demo lord.
2. Inspect ruler, dynasty, titles, realm, map, Church, spiritual state, plague, army, apocalypse.
3. Raise a levy at Salon (about 80 to 120 men).
4. March Salon to Aix to Miramas. Fight the Host of Miramas.
5. Advance the calendar. Resolve each event when it opens.
   Recommended choices: send physicians, admit refugees, demand compliance, grant the abbey, ask the bishop, arrest the cult, call clergy, exorcism, attack the manifestation, record the chronicle.
6. Apocalypse should leave ordinary order and reach thinning of the veil.

`php artisan diesirae:seed-slice` rebuilds this world only. It does not touch `feudalism_game`.

## Implemented

Playable vertical slice (end to end):

* Inspect character, dynasty, titles, realm, vassals, settlements, Church (bishop, parish, monastery, papacy as Avignon proxy), plague, faith/corruption, army, apocalypse, event decisions.
* Raise an army from Salon levy, march adjacent land only, fight a human enemy at Miramas.
* Ten deterministic campaign events:
  1. Plague appears in Aix
  2. Refugees arrive
  3. Local noble refuses aid (Gui de Pélissanne)
  4. Monastery requests resources (Church decision)
  5. Rumors of heresy
  6. Cult discovery (Brothers of the Open Grave)
  7. First minor demonic manifestation
  8. Clergy response
  9. Military response
  10. Aftermath (corruption and population)
* Apocalypse coarse stage: `ordinary_order` to `great_mortality` (plague) to `thinning_veil` (manifestation). Kernel `phase_key` is mapped through `CoarseStageMap` so the tick engine still reads catalog phases.
* Dual authority in the slice: bishop/abbot/priest/pope live on `spiritual_offices`, not `titles`. Validator rejects a spiritual rank stored as a title.
* World isolation: slice events belong to `provence-1347` only. DSN is `dies_irae_game`, never `feudalism_game`.
* UI pages (usable, not pretty): dashboard, ruler, dynasty, titles, realm, map, settlement, Church, spiritual, plague, army, apocalypse, events.
* Commands: `diesirae:seed-slice`, `diesirae:validate-world`, calendar advance from the dashboard.

Kernel already present and used or aligned by the slice:

* Worlds, geography, characters, dynasties, secular titles, realms, vassalage
* Church tables (sees, offices, papacy, monasteries, heresy)
* Catastrophe plague waves at territory grain
* Hell overlay, cults, demonic influence
* Apocalypse meters and scheduled ticks
* Playable `armies` / `battles` (separate from `warfare_*`)

## Partially implemented

* **Apocalypse:** coarse playable stages work. Full meter/signal/milestone catalog ticks in the background and is not the player-facing clock for this slice.
* **Church:** inspect and one resource grant work. Excommunication, investiture, papal vacancy, and appointment policy exist as kernel actions and tests, not as slice events.
* **Plague:** territory intensity and deaths work. Settlement-cohort plague (`settlements`, `settlement_populations`, host ticks) is schema-ready and not driven by the ten beats.
* **Hell:** one haunted overlay, one demonic influence row, one demonic host-as-army. Named demons, breaches, and incursion pulses are not in the player loop.
* **Warfare:** human and supernatural fights use the simple playable combat table. Doctrine kernel (`warfare_*`, WarDirector) is in-repo and tested, not wired to the army page.
* **Geography:** six named places and adjacency, not a NUTS Europe import.
* **Fixture world `lys-1348`:** still seedable for kernel validation. It is not the playable login.

## Stubbed

* Sacrament simulation and miracle economy
* NPC daily AI
* Trade, buildings, guilds
* Formable nations / full war UI
* Inquisition gameplay beyond the cult arrest/burn/conceal choices
* Public god tools (inspect stays local/testing)

## Missing

* Succession play (laws exist; the slice does not kill the count)
* Multi-realm diplomacy
* Painted political map of Europe
* Queue/Redis scale-up
* Linking playable `armies` to `warfare_armies`
* Driving settlement-grain plague from campaign beats
* Player-facing chronicle writing beyond the aftermath option

## Tests

Command: `php artisan test`

**Vertical slice suite (required):** 8 passed, 0 failed

* `tests/Feature/VerticalSlice/VerticalSliceLoopTest.php` (seed, raise, march, human fight, all 10 beats, supernatural battle, population/corruption/Church standing, apocalypse at least `thinning_veil`)
* `tests/Feature/VerticalSlice/ArmyLoopTest.php`
* `tests/Feature/VerticalSlice/DualAuthorityAndIsolationTest.php`
* `tests/Feature/VerticalSlice/VerticalSliceUiTest.php`

**Full suite (latest clean run):** 165 passed, 0 failed

Includes unit Hell/Apocalypse/Population/Spiritual/Warfare, Feature Catastrophe, Church dual-authority and powers, fixture `lys-1348` validation, and the slice.

## Failing tests

None on the latest clean `php artisan test` run against `dies_irae_game_testing`.

Earlier in this work, Church kernel tests failed because `000600` created skinny Church tables and `000650` skipped them. Additive columns were put on `2026_08_13_200000_create_vertical_slice_play_tables.php`. If those tests regress, check `sees.parent_see_id`, nullable `sees.church_province_id`, and `spiritual_office_holderships.acquisition_type`.

## Next recommended packages

Do not start a new map or war doctrine UI until a second playable loop needs them. Recommended order:

1. **Succession that can fire in this county** (death of Raimond, heir, title history). The slice currently assumes the count lives.
2. **Wire settlement-grain plague** to the Aix wave so Package 8 population/host ticks match what the player already sees on the plague page.
3. **One sacrament on the bishop/parish path** (mass for the dead, or a valid/invalid exorcism using the spiritual engine instead of a flat corruption delta).
4. **Hell pulse on thinning veil** so the manifestation can deepen without a new campaign script.
5. **NUTS-lite geography import** only after the Salon loop still passes. Keep Church and catastrophe in the same wave as new land.
6. **Playable war goals** only after 1 to 4. Keep `armies`/`battles` as the player table; do not replace them with `warfare_*` in the UI.

Out of scope until the above: Feudalism Blade theme, community pages, Redis, formable nations.
