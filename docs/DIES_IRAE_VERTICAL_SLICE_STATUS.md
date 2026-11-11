# Dies Irae vertical slice status

**Date:** 2026-08-13  
**World:** `provence-1347` (1 November 1347, County of Salon)  
**Login:** `lord@diesirae.test` / `password`  
**Database:** `dies_irae_game` (live) / `dies_irae_game_testing` (PHPUnit). Neither is Feudalism.

The first complete playable loop is in. The player is Raimond Adhémar, Count of Salon. Ten campaign beats, a human battle, a demonic manifestation, Church and plague decisions, and an apocalypse stage change all run in one world.

## Finish pass, 2026-11-11

This pass closed the four loops the August note named as next, inside the same county. It did not add a new map, a war-doctrine screen, or a second campaign.

Complete in the Salon loop:

* Succession can fire. Hugues Adhémar is the living heir under male-preference primogeniture. On day 16 the event `count_stricken` (not one of the ten beats) offers anointing, concealment, or yielding the county. Yielding kills Raimond, writes title history with acquisition `succession`, moves vassals and the player account onto Hugues. Anointing and concealment leave him alive.
* The Aix plague choice creates settlement censuses and infection batches. Later calendar days tick that settlement plague and move the territory population by the net death and flight, so the plague page and the map stay on the same wave.
* The abbey grant and the prayer option record a mass for the dead through `ConferSacrament`, after one historical baptism. The count's fever can be anointed the same way. The bishop's exorcism is a hell countermeasure with a stored result, not a flat corruption spend.
* After the veil thins, each advanced day runs one hell pulse. Aix keeps the haunted overlay, gains an incursion row, and the overlay intensity can rise. A rift waits for the engine's own breach threshold.
* The aftermath writes one chronicle row. Reusable notes are in `docs/transferable-innovations.md`.

Still open:

* Named characters do not die when a settlement cohort collapses. The count dies only if the player yields the county.
* The thinning-veil pulse does not open a rift by itself. The full hell world is not stored, only the incursion, the deltas, the overlay intensity, and the daily pulse log.
* NUTS-scale geography is not the playable county. `europa-1347` remains the separate campaign seed.
* Playable war goals are not done. The army page stays on `armies` and `battles`. `warfare_*` stays off that page.
* DiplomacyService, FaithService, and SinVirtueService are still one-method stubs. Virtue and sin stay on the spiritual engine.
* Sacraments beyond requiem, anointing, and one exorcism are not in the slice. Inquisition beyond the cult choices is not in the slice.
* Trade, buildings, guilds, Redis, formable nations, and an NPC daily schedule are not in the slice.
* Settlement food is not the lord's granary. Territory `food_stores` stays the scripted granary.

The August sections below are the original slice inventory. Where they disagree with this section, this section is the later one.

Test run on 2026-11-11, PHP 8.3.6, PHPUnit 9.6.36, MySQL database `dies_irae_game_testing`: `php artisan test` reported 294 passed and 7 failed. The 7 failures were `Tests\Feature\Events\DynamicEventEngineTest`, which creates `dies_irae_events_testing` and was denied that privilege on this machine. After that local database privilege was granted, the same class passed 7 tests. No test was skipped. Browser play was not opened; the play pages were exercised by `VerticalSliceUiTest` and the yield path in `CountyContinuationTest`.

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
* **Plague:** the Aix choice seeds settlement cohorts and infection batches, and later days tick them. Cohort collapse still does not kill named characters.
* **Hell:** one haunted overlay, one demonic influence row, one demonic host-as-army, a stored exorcism, and a daily pulse after thinning of the veil. Named demons are not in the player loop. A rift is not opened by the thinning-veil pulse.
* **Warfare:** human and supernatural fights use the simple playable combat table. Doctrine kernel (`warfare_*`, WarDirector) is in-repo and tested, not wired to the army page.
* **Geography:** six named places and adjacency, not a NUTS Europe import.
* **Fixture world `lys-1348`:** still seedable for kernel validation. It is not the playable login.

## Stubbed

* Sacrament simulation beyond requiem, anointing, and one exorcism, and the miracle economy
* NPC daily AI
* Trade, buildings, guilds
* Formable nations / full war UI
* Inquisition gameplay beyond the cult arrest/burn/conceal choices
* Public god tools (inspect stays local/testing)

## Missing

* Automatic death of the count during the ten beats (death is the day-16 yield, not a scripted beat)
* Multi-realm diplomacy
* Painted political map of Europe
* Queue/Redis scale-up
* Linking playable `armies` to `warfare_armies`
* A freeform chronicle. The aftermath writes one entry, and succession writes its own summaries.

## Tests

The 2026-11-11 finish pass records its own run above. The counts in this section are the August run.

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

1. **Succession that can fire in this county.** Done as the day-16 `count_stricken` choice. The ten beats still assume the count lives.
2. **Wire settlement-grain plague** to the Aix wave. Done. Named-character death from cohort collapse is still open.
3. **One sacrament on the bishop/parish path.** Done: mass for the dead, anointing, and an exorcism countermeasure.
4. **Hell pulse on thinning veil.** Done, without promoting Aix to rifted.
5. **NUTS-lite geography import** only after the Salon loop still passes. Still open. Keep Church and catastrophe in the same wave as new land.
6. **Playable war goals.** Still open. Keep `armies`/`battles` as the player table; do not replace them with `warfare_*` in the UI.

Out of scope until the above: Feudalism Blade theme, community pages, Redis, formable nations.
