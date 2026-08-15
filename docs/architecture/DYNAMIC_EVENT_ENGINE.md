# Dynamic Event Engine

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Owner:** Events / Time  
**Companion:** [APOCALYPSE_PROGRESSION.md](APOCALYPSE_PROGRESSION.md)

Events are how the age speaks. They are not a random popup table. A definition may fire only when the simulated world already contains the wound, the office, the hunger, or the hook that makes the scene true.

---

## What this is

The engine selects **scoped occurrences** from data-driven definitions. Each occurrence is a `game_events` row (`engine = catalog`). Campaign beats from the vertical slice remain `engine = campaign_beat` and still resolve through their own action.

Other domains do not write event keys. They change state (plague, food, despair, cults, apocalypse signals, Church standing). The pulse reads that state.

---

## Definition shape

Content lives in `database/data/events/definitions/*.json`. Allowed scopes, categories, visibilities, and ops are listed in `database/data/events/catalog.json`.

A definition may declare:

| Field | Role |
|-------|------|
| `trigger` | Conditions against current world state. |
| `scope` | Where it can attach. |
| `weight` / `weight_modifiers` | How likely, given hooks and meters. |
| `cooldown_days` | Silence after fire. |
| `exclusivity` | Blocks siblings while pending. |
| `visibility` | `player`, `observer`, `hidden`, `npc_only`. |
| `choices` | Labels, `visible_if`, AI weights. |
| `immediate` / `hidden` / `delayed` | Consequence ops. |
| `chain` | Name for follow-up links. |

Delayed ops become `event_chain_links`. Follow-up events still check **their own triggers** when they come due. If despair never rose, the preacher does not arrive just because a JSON file listed him.

---

## Scopes

`character`, `dynasty`, `settlement`, `territory`, `realm`, `church_jurisdiction`, `monastery`, `army`, `war`, `cult`, `plague`, `apocalypse`, `world`.

`apocalypse` and `world` attach to the world row. Geography ops fall through to a territory when the scope has one (monastery seat, cult cell, army camp, see).

---

## Categories

court, dynasty, religious, plague, famine, warfare, politics, diplomacy, succession, heresy, cult, miracle, demonic, supernatural omen, economic, refugee, morality, Church politics.

Category is authoring metadata used by inspect and UI. It is not a second scheduler.

---

## Hooks (persistent future)

Table `event_hooks`. A choice may `set_hook` / `add_hook` / `clear_hook`. Hooks carry scope, intensity, optional expiry, and payload.

Later definitions read `has_hook` / `min_hooks`. Weight modifiers can make cult recruitment easier after `refugees_refused` without a scripted cutscene tree.

There is no score that farms the story backwards. Cooldowns and exclusivity stop spam. Irreversible apocalypse milestones remain owned by the apocalypse package.

---

## Consequence ops

Ops write simulation, not flavor text:

- food, population, despair, corruption, treasury, prestige, Church standing
- apocalypse signals
- hooks
- spawn a later event (still gated by state)
- move refugees toward the neighbor with the most food
- seed / strengthen a cult
- monastery stores
- plague intensity
- bishop condemnation (standing + hook)

Hidden ops apply and log to `event_hidden_logs`. They do not appear in the player-facing `effects` blob.

---

## Pulse

Event type: `narrative_event_pulse`  
Interval: `config('events.pulse_interval_days')` (default 1 world day)

Created with the world (`EnsureEventPulse`). Consumed by `diesirae:process-world-events` the same way apocalypse ticks are.

Each pulse:

1. Drops expired hooks.
2. Retries due chain links. If the `when` clause or follow-up trigger fails, the link waits (`waiting_state`) instead of firing a lie.
3. Collects eligible (definition, scope) candidates.
4. Picks up to `max_new_events_per_pulse` with a deterministic weighted roll from `worlds.simulation_seed`.
5. Fires them. Player-controlled `player` visibility waits for a decision. NPC, hidden, and observer occurrences resolve through AI weights.

---

## AI

Each choice has `ai.base` and `ai.modifiers` with the same condition language as triggers. The roll is seeded. It is not a personality sim. It is a biased coin the world can explain.

---

## Debug (local/testing)

- Inspect: `/dev/inspect/events/{world}`  
  force trigger, eligibility, weights, cooldowns, chain state, hidden log
- `diesirae:validate-events`
- `diesirae:event-status {world}`
- `diesirae:event-pulse {world} [--force]`
- `diesirae:event-force {world} {definition} {scope_type} {scope_id}`

Production returns 404 for inspect. Mutation commands refuse outside local/testing.

---

## Authoring check

`diesirae:validate-events` rejects unknown scopes, categories, ops, duplicate keys, missing triggers/choices, and `spawn_event` targets that are not in the catalog.

---

## Example chain: refused refugees

Not a hardcoded novella. State does the work.

1. `refugees_at_the_gate` needs food in the county **and** plague, inbound refugees, or despair.
2. **Refuse** keeps the stores. It sets `refugees_refused`, quietly lowers Church standing, and queues delayed ops.
3. Days later, `move_refugees_onward` picks a neighbor by food, not by scripted destination.
4. Despair rises on the origin only if the refusal hook still holds.
5. `hostile_preacher` is eligible when that hook exists **and** despair is already ugly.
6. `cult_finds_the_refused` is hidden. Weight climbs with the same hook. It seeds a cell and reports `successful_cult` to apocalypse.
7. `bishop_condemns_ruler` reads low standing plus the same hook.

Admit them instead and those follow-ups have nothing to stand on.

---

## Code map

| Piece | Path |
|-------|------|
| Catalog | `app/Domain/Events/EventCatalog.php` |
| Conditions / weights / AI | `app/Domain/Events/*` |
| Pulse / fire / resolve | `app/Actions/Events/*` |
| Content | `database/data/events/` |
| Inspect | `/dev/inspect/events/{world}` |

Campaign `ResolveGameEvent` delegates catalog rows to `ResolveCatalogEvent`. Do not put new story beats in PHP match arms.

---

## Invariants

1. Catalog events never reuse the vertical-slice unique `event_key` contract. Occurrences use `occurrence_key`.
2. Cross-world scope ids are not resolved; candidates are loaded per world.
3. Hidden events do not show in the play UI.
4. Delayed follow-ups re-check world state.
5. No public HTTP mutators.
6. Creating a world does not seed a crisis. The pulse starts quiet.
