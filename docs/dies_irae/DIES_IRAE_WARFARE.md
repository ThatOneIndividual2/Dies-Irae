# Dies Irae Warfare

**Status:** Kernel implemented (domain + actions + cross-domain tests)  
**Date:** 2026-08-13  
**Donor:** `/var/www/html/Feudalism/feudalism-main` (read-only)  
**Companion:** [FEUDALISM_REUSE_AUDIT.md](../../dies_irae/FEUDALISM_REUSE_AUDIT.md), [DIES_IRAE_DOMAIN_MAP.md](../../dies_irae/DIES_IRAE_DOMAIN_MAP.md)

This is not Feudalism with a demon flag. Ordinary feudal war is the sealed baseline. Cult, demon, holy-order, and corrupted-army wars are doctrines that plug into the same pipeline without rewriting it.

---

## Verdict

| Piece | Decision |
|-------|----------|
| War / front / occupation *shape* | **FORK / ADAPT** from live `WarService` |
| User-as-army, influence-as-mana, instant `owner_id` flip | **DO NOT REUSE** |
| Contract levy math `floor(base * rate / 100)` | **REUSE AS-IS**, then scale by population and ruin |
| Unit weights infantry 1 / archers 1.2 / cavalry 2 / siege 3 | **REUSE AS-IS** as men-at-arms quality |
| Pressure-to-occupy 100, ~8% tick casualties | **REUSE AS-IS** as human baseline |
| Owner vs controller | **REUSE AS-IS** (occupation changes controller; legal owner waits for settlement) |
| `WorldBoundary` | **REUSE AS-IS** |
| Actions, not a fat `WarService` on User | **REUSE** Feudalism foundation pattern |

Hell is not a realm. A holy order is not a dynasty. A cult is not a guild. Those identities stay outside this kernel and arrive through ports.

---

## Two layers

Warfare has **war physics** and **army physics**. Mixing them is how supernatural rules leak into a border skirmish.

```text
War (WarfareKind + WarProfile)
  occupation mode, allowed war goals, aftermath channels

Army (ArmyNature + ForceProfile)
  supply, levies, fear aura, casualty conversion, portal tether
```

A holy order fighting a demon uses **demon war physics** (overlay, possession, desecration) and **two force profiles**: the order still draws tithe and consecrated men; the host still hangs on a portal. A human levy in that same war still eats food.

Priority for `WarfareKind` (first match):

1. Any demonic faction → `human_vs_demon`
2. Else any corrupted host → `corrupted_army`
3. Else any holy order → `holy_order`
4. Else any cult → `human_vs_cult`
5. Else → `human_vs_human`

---

## Donor systems, adapted

Feudalism live war is User vs User: declare (15 influence), border fronts, deploy infantry/cavalry/archers/siege from account columns, pressure → occupy, hourly tick, vassalize. Armies live on `users`. Foundation has `CalculateContractLevies` and `holdings.base_levy` unconnected to that loop.

Dies Irae keeps the loop, changes the subject.

| Donor | Dies Irae |
|-------|-----------|
| `War.aggressor_id` → User | `Belligerent` (realm, holy order, cult, demonic faction, corrupted host) |
| `casus_belli` string | `WarGoal` with a type legal for that `WarProfile` |
| `WarFront` on region | Occupation / infiltration / overlay on territory |
| `Deployment` unit columns | `Army` of `UnitStack`s at a territory |
| Recruit onto User | `RaiseLevies` + `RaiseMenAtArms` |
| Instant `region.owner_id` | Military control only, until settlement |
| Tick in `WarService` | `WarDirector` + Actions |

### Levies

Donor: `due = floor(vassalBaseLevy * levy_rate / 100)`.

Dies Irae then multiplies by living population over baseline, then by levy-eligible fraction, and refuses levies when `RuinState::raisesLevy` is false. Empty counties do not field hosts because the player forgot a recruit button. They field nothing because the people are dead or gone.

Human realms use feudal levies. Holy orders use their own call (consecrated sergeants and knights). Cults and corrupted hosts raise forced hosts. Demons raise nothing feudal; they manifest.

### Men-at-arms

Donor standing troops (infantry, cavalry, archers, siege engines) become persistent stacks on the belligerent, not columns on `users`. Same power weights.

### Commanders

A character lens: martial, piety, corruption, possessed, cleric, holy-order knight. Martial feeds battle. Piety and vows resist fear. Possession adds bite and subtracts resistance.

### Armies

World-scoped. Located on a territory. Carry morale, supply, fear, corruption exposure, plague exposure, optional manifestation budget and portal tether.

### Movement

Neighbor graph only. Days from geography. Each day ticks supply. Demons may collapse off-portal. Corrupting natures write terrain rot on the march, not on a human march.

### Occupation

Human: controller changes, owner does not. That was the donor's documented rule; live `WarService` violated it by writing `owner_id` on pressure. Dies Irae keeps the rule.

### Sieges

Fortification vs engines and martial. Human method is `siege`. Cults can `infiltrate`. Demons can `blight`. Starving human armies slow. Demons do not starve.

### Supply

| Nature | Kind | Starvable | Notes |
|--------|------|-----------|-------|
| Human | food | yes | Daily drain. Low supply cuts morale and combat. |
| Cult | plunder | no | Weak in open field; lives off the land. |
| Demonic | portal | no | Strength from an open portal. No portal → manifestation collapses. |
| Holy order | tithe | yes | Slower drain than feudal baggage. |
| Corrupted | devour | yes | Feeds on local population; starves in empty land. |

### Casualties

Human baseline uses the donor's ~8% of contested power, split killed / wounded / fled / captured.

Demons convert killed into **banished**. Cults convert captives. Possession wounds appear only when the war profile allows possession events.

### Wars and war goals

| Kind | Typical goals |
|------|----------------|
| Human vs human | conquest, vassalize, defensive |
| Human vs cult | suppress cult, holy war, defensive |
| Human vs demon | seal rift, defensive, holy war |
| Holy order | holy war, suppress cult, seal rift, defensive |
| Corrupted army | purge corruption, conquest, defensive |

A feudal count cannot take `seal_rift` as a human-vs-human goal. The kernel rejects it.

---

## Doctrine differences (required)

### Human vs human

Sealed baseline. Food, feudal levies, men-at-arms, commanders, marches, sieges, military occupation, mundane corpses, camp fever from the pile, settlement morale, war refugees. No relic combat, no plague exposure channel, no corruption, no possession, no desecration, no hell overlay, no infiltration. Legal title does not move until settlement.

### Human vs cult

Infiltration instead of feudal occupation. Legal owner remains. Faith takes a hit. Captives may be converted. Cults take an open-field penalty and a bonus on already-rotten ground. Aftermath is not mundane.

### Human vs demon

Demons are not a nation. They do not eat. They do not starve under a baggage interdiction. They tether to a portal, respect a manifestation cap, write terrain corruption, radiate terror, and banish rather than die. Occupation applies a **hell overlay**. The duke still "owns" the county. That is the point. Possession events and battlefield desecration are legal. Sealing the portal is a war goal; painting the map is not.

### Holy-order warfare

Not a realm with a cross. Own call, consecrated default, tithe supply, commandery occupation (military control without eating the title). High clergy and relic sensitivity when the war is not the sealed human kind. Versus demons, war physics become infernal; the order's force profile stays holy.

### Corrupted-army warfare

Started as men. Mixed stacks. Devours population, spreads plague and corruption, forced levies, unstable morale, possessed commanders possible. Occupies militarily **and** writes corrupt control. Does not become a portal host. Can still be starved in a dead county.

---

## Extensions (ports, not columns on Army)

The kernel reads these. It does not own Church, plague, or Hell tables.

| Extension | Where it lives | Human vs human |
|-----------|----------------|----------------|
| Morale | Army state + aftermath | Yes (mundane) |
| Fear | Force aura, resisted by piety / consecration | Aura is 0 |
| Corruption exposure | Hell port + aftermath | Forced to 0 |
| Plague exposure | Catastrophe port, gated by war profile | Channel closed |
| Clergy support | Spiritual port | Not read |
| Relic support | Spiritual port | Not read |
| Consecrated units | Stack flag / holy-order raise | Ordinary troops |
| Holy orders | Belligerent kind + force profile | N/A |
| Demonic terror | Demonic fear aura | N/A |
| Possession events | Battle + war profile | Impossible |
| Battlefield desecration | Aftermath | Sanctity stays ordinary |
| Corpse aftermath | `CorpseHandling` (population domain) | Consecrated or mass grave, never desecrated |

`NullSpiritualPort`, `NullHellPort`, and `NullCatastrophePort` keep the feudal loop valid before those domains exist. `RecordingAftermathSink` is how tests prove the sealed path.

---

## Post-battle consequences

Every battle emits an `AftermathReport` to a sink. Population, Church, and Hell apply it. Warfare does not write their tables.

| Channel | Human vs human | Other kinds (as profile allows) |
|---------|----------------|----------------------------------|
| Corpses | Yes | Yes |
| Disease | Camp fever from the pile | Plus plague exposure |
| Settlement morale | Yes, worse on sack | Yes |
| Corruption | 0 | From handling, blight, overlay |
| Local faith | 0 | Shock, infiltration, desecration |
| Refugees | War displacement on sack | Hell displacement when overlay |
| Battlefield sanctity | Ordinary | May become desecrated |

Corpse handling reuses the population domain's `CorpseHandling` (consecrated, mass grave, burned, abandoned, desecrated) so infectiousness and despair stay one vocabulary.

---

## Module layout

```text
app/Domain/Warfare/
  Enums/          WarfareKind, ArmyNature, goals, supply, occupation, sanctity
  Doctrine/       ForceProfile, WarProfile, DoctrineResolver
  Ports/          Levy, geography, population, spiritual, catastrophe, hell, aftermath
  State/          War, Army, Commander, stacks, siege, battle, occupation, aftermath
  Engine/         levies, armies, supply, movement, battle, siege, occupation, aftermath, director
  Support/        InMemoryWarfareWorld (tests and early wiring)
app/Actions/Warfare/
  DeclareWar, RaiseLevies, RaiseMenAtArms, FormArmy, AssignCommander,
  MarchArmy, ResolveBattle, TickSiege, OccupyTerritory, SettleWar
database/migrations/2026_08_13_000100_create_warfare_tables.php
tests/Feature/Warfare/
tests/Unit/Warfare/
```

No `app/Services/WarService`. Mutations go through Actions. `WarDirector` is the composition root.

Persistence is sketched, not required to run the kernel. Eloquent can wrap `State\*` later without changing doctrine flags.

---

## Isolation guarantee

`WarProfile::humanVsHuman()` closes every supernatural aftermath channel. `BattleService` does not read clergy, relics, or plague intensity on that kind. Hostile stub ports that return 90 plague / 80 relics therefore cannot change a feudal battle's winner, kills, morale, or aftermath. That is covered by `CrossDomainIsolationTest`.

If a later feature needs battlefield chaplains in ordinary war, add them as a commander fact, not by opening the Church port on `human_vs_human`.

---

## What this kernel refuses

- Troops, gold, or fear on `users`
- Modeling Hell as a `Realm` so occupation can reuse vassalize
- Modeling a holy order as a celibate dynasty
- Instant legal annex on pressure (donor live bug)
- A second apocalypse tick that ignores `worlds.current_date` (when the calendar exists, war days must share it)
- Spending piety to win a battle

---

## Tests

| Test | Proves |
|------|--------|
| `HumanVsHumanBaselineTest` | Levy math, MAA, commander, march, siege, battle, occupy-without-annex, settle, mundane aftermath |
| `CrossDomainIsolationTest` | Hostile Church/Hell/plague reads do not move feudal math; same pipeline serves demon wars |
| `DoctrineDifferenceTest` | Five kinds, five supply laws, infiltration vs overlay vs commandery vs corrupt control, portal collapse, devour starvation |
| `WarfareContractsTest` | Donor numbers, sealed profile, world boundary, holy order vs demon split (war physics vs force physics) |

Run from the app root:

```bash
./vendor/bin/phpunit tests/Unit/Warfare tests/Feature/Warfare
```

---

## Wiring notes for other domains

- Population: implement `LevySource` / `PopulationPort`; consume `AftermathReport` for corpses, refugees, ruin.
- Church: implement `SpiritualPort`; do not put sees into `TitleRank`.
- Hell: implement `HellPort` (portals, corruption). Overlay occupation is not a title grant.
- Apocalypse: may change balance numbers by stage. It must not delete `human_vs_human`.
- Calendar: pass world date into `DeclareWar`. Do not give warfare its own clock.

Feudalism was not modified. This kernel does not write `feudalism_game`.
