# Strategic AI

**Status:** Kernel implemented  
**Date:** 2026-08-13  
**Owner:** `App\Domain\Ai` via `NPCContract`

Believable institutional behavior, not perfect play. There is no universal NPC brain.

A king, a bishop, a monastery, and a demon commander can face the same wound in the land and come to different acts for different reasons. That is the point.

---

## Law

1. Do not route every actor through one `decide()` policy. The engine *orchestrates*. Reasoners *think*.
2. A character hat is not an organization. Realms, monasteries, holy orders, cults, and the papacy decide as institutions.
3. A prince-bishop is two hats on one person. The caller names the hat. The count-brain must not silently become the bishop-brain.
4. The papacy is not a kingdom. A pope reasoner may not pursue dynasty.
5. Demons are not a dynasty and have no sacramental faculty.
6. Debug traces are part of the design. Balancing without them is guesswork.
7. Content in `database/data/ai/`. Engine code uses keys.

False shortcuts this kernel refuses:

- One weighted utility function with a `class` string on the NPC
- User-NPC kings from Feudalism
- Forcing monastery or curia decisions through a character row
- A piety integer that buys the "correct" act
- Perfect-play minimax

---

## Placement

```text
app/Domain/Ai/                 # simulation kernel
  Enums/                       # actor types, concerns, crises, grain
  State/                       # actor, situation, personality, memory
  Catalog/                     # actions + profiles
  Reasoners/                   # one brain per actor type
  Scoring/UtilityScorer.php    # shared math, not shared policy
  Constraints/InstitutionalGate.php
  Trace/                       # considered / rejected / chosen
  StrategicAiEngine.php        # dispatcher
app/Domain/NPC/                # Laravel contract wrapping the engine
app/Actions/NPC/               # ConsiderNpcTurn, ExplainAiDecision
database/data/ai/
  actions.json
  profiles.json
  crises.json
database/migrations/2026_08_13_001300_create_strategic_ai_tables.php
config/ai.php
tests/Unit/Ai/
docs/architecture/STRATEGIC_AI.md
```

---

## Actor types

### Character hats

| Type | What it actually considers |
|------|----------------------------|
| king | realm levy, bannermen, vassals, papacy, succession |
| duke | local host, liege, claims |
| count | county survival, quarantine, petition |
| bishop | rite, relic, heresy, not a feudal host |
| pope | bulls, exorcists, excommunication; never dynasty |
| abbot | granary, refugees, prayer |
| holy order leader | march on the breach, papal sanction |
| military commander | hold, withdraw, cleanse |
| claimant | press the claim |
| merchant | goods, markets, plague roads |
| heretic leader | preach against Church failure |
| cult leader | conceal the cell |
| demon commander | widen the breach, harvest fear |

### Organizations

| Type | What it actually considers |
|------|----------------------------|
| realm | borders, vassal levy, seizure |
| monastery | granary, refugees, chronicle |
| holy order | commandery, march |
| cult | conceal; no open rite once discovered |
| papacy | bulls only while a papal office exists |

A famine makes a monastery open stores and a realm seize grain. Same crisis, different institution.

---

## Concerns

Survival, dynasty, legitimacy, faith, ambition, wealth, territorial security, loyalty, fear, corruption, revenge, Church standing, plague safety, famine, infernal threat.

Each profile has its own weights. A merchant's wealth weight is not a bishop's. Negative weights exist (cult Church standing, demon faith).

---

## Decision flow

```text
hat = actor.wearingType
reasoner = registry[hat]          # distinct class
candidates = reasoner.propose()   # profile action list + extras
for each candidate:
    cooldown?     reject
    reasoner.reject()             # institutional voice of that hat
    gate.reject()                 # grain, requirements, hard bans
    else score                    # shared utility math
pick highest final score
tie-break: action key
always emit a DecisionTrace
```

Reusable pieces (not a shared brain):

- **goals / actions** in `actions.json` (concern affinities, risk, requirements)
- **weights** per profile
- **utility scoring** (`UtilityScorer`)
- **personality** (ambitious, pious, cautious, vengeful, greedy, loyal, zealous, compassionate)
- **institutional constraints** (reasoner extras + `InstitutionalGate`)
- **cooldowns**
- **memory** (success/failure salience on a related action)
- **relationships** (rival standing on claim / excommunication)
- **risk tolerance** (profile default, lowered by caution, raised by ambition)

---

## Crisis behavior

Crises shift concern weights. They do not replace the reasoner.

| Crisis | Typical shift |
|--------|----------------|
| plague | plague safety up, ambition down |
| famine | famine up, wealth down |
| war | territorial security up |
| succession crisis | dynasty, legitimacy, ambition up |
| heresy | faith, Church standing up |
| cult discovery | faith, infernal, corruption up; cults hide |
| demonic incursion | infernal threat up, ambition down |
| papal conflict | Church standing, legitimacy up |
| local collapse | survival and fear up, ambition down |

A count also refuses military cleansing through plague unless an incursion is already open. A holy-order master refuses to withdraw from a severe breach. Those are reasoner rules, not weight tweaks.

---

## Dual authority

A character may be count and bishop. `AiActor.wearingType` is the hat under consideration. Tests prove the same person, different hat, different act. The engine will not merge the two offices into one average.

---

## Debug output

Every decision carries a `DecisionTrace`:

- considered actions with raw score, final score, modifier list
- rejected actions with reasons (cooldown, missing sacrament, "a king does not celebrate the rite")
- chosen action
- major modifiers (crises, risk tolerance, personality, memory)

`DecisionTrace::explain()` is a readable block for `/dev/inspect` and balancing.

Persist rows in `ai_decision_traces` when an Action records a turn.

---

## Persistence

- `character_personalities`
- `ai_memories` (character or organization keys)
- `ai_cooldowns`
- `ai_relationships`
- `ai_decision_traces`

The kernel itself is snapshot-based (`AiActor` + `Situation`). Laravel Actions hydrate and persist. Calendar ticks should call `ConsiderNpcTurn`; they must not invent a second AI clock.

---

## Testing

`tests/Unit/Ai/StrategicAiHarness.php` builds situations without a database.

Covered:

- king vs bishop on the same incursion
- ambitious vs cautious duke on succession
- monastery vs realm on famine
- pope will not pursue dynasty
- prince-bishop hat switch
- cooldown rejection
- merchant vs count on plague
- cult conceals while bishop acts
- demon vs holy-order leader at a breach
- heretic vs bishop on heresy
- determinism
- trace contents
- sede vacante papacy cannot issue a bull
- every registered type can produce an act

```bash
php vendor/bin/phpunit tests/Unit/Ai
php vendor/bin/phpunit tests/Feature/NPC/StrategicAiContractTest.php
```

---

## What this package does not do

- Daily NPC autonomy across all of Europe
- Pathfinding, battle resolution, or diplomacy execution (those domains apply the chosen key)
- A player-facing "AI difficulty"
- Perfect information omniscience (situation pressures are what the actor is given)

Later ticks should feed `Situation` from plague, hell, church, and title state. They must still name a hat and call a reasoner.
