# Dies Irae Domain Map

**Status:** Target architecture  
**Date:** 2026-08-13  
**Companion:** [FEUDALISM_REUSE_AUDIT.md](FEUDALISM_REUSE_AUDIT.md), [RECONSTRUCTION_PLAN.md](RECONSTRUCTION_PLAN.md)

Dies Irae is a persistent medieval apocalypse grand-strategy / roleplay simulation. The world begins as a recognizable feudal Europe. The Black Death is the door, not the whole house. Hell enters the map. Political authority and spiritual authority stay distinct until the order itself fails.

This is not Feudalism with a demon flag.

---

## Design rules (non-negotiable)

1. **User ≠ Character.** An account controls one living person. It is not an army, a treasury, or a title.
2. **Dynasty ≠ Realm.** Bloodline is not a kingdom.
3. **Title ≠ Territory ≠ Holding.** Legal office, land, and property on land stay separate.
4. **Realm is derived.** Political aggregates are caches from titles and vassalage, not the ownership ledger.
5. **Political authority ≠ spiritual authority.** A see is not a duchy. The papacy is not a realm. A prince-bishop is a character with two offices, not one hybrid rank.
6. **Faith is an institution, not a string.** Catholic hierarchy, sacraments, relics, and law are first-class domains.
7. **Hell is a polity and a geography, not a monster table.** Demonic factions have agendas. Incursions have territory.
8. **Catastrophe is demographic.** Plague, famine, despair, and ruin act on populations, then on lords.
9. **The apocalypse distorts the medieval order.** It does not replace the feudal rules on day one. Distortion is a progression.
10. **Gameplay mutations live in Actions.** Models hold state. Events fire after commit.
11. **One world date.** Harvest, liturgy, plague, and war share `worlds.current_date`.
12. **Preserve history.** Ownership, office, control, sacrament, relic custody, and ruin are append-only where the donor already proved that pattern.
13. **Modular monolith.** No microservices.
14. **Content in data files.** Engine code stays generic. Europe names live in `database/data/`.

---

## Two authorities, one person

```text
                         Character
                        /         \
           Secular office          Spiritual office
          (Title domain)          (Church domain)
                 |                       |
              Realm                   Diocese / Order / Papacy
                 |                       |
            Territory  <---------->  Parish / shrine / monastery
                 |
              Holding
                 |
            Population
```

A character may be:

- A landed lord with no clerical status
- A priest, monk, bishop, or legate with no secular title
- Both (prince-bishop, royal chaplain, warrior-monk of a holy order)
- Neither (landless kin, refugee noble, possessed peasant later)

The engine must allow all four. Feudalism only modeled the first.

Cross-domain relations (investiture, interdict, crusade blessing, excommunication of a king) are **edges between domains**, not extra columns on `titles`.

---

## Bounded contexts

```text
Identity (User)
World
├── Time (one calendar + scheduled events)
├── Geography (Region adapter, Territory, Holding, supernatural overlay)
├── Population (census, mortality, refugees, ruin)
├── Characters ──► Dynasties / Houses
├── Secular politics
│     Titles + ownership history + claims
│     Realms (cache) ◄── Titles + Vassalage + Contracts
│     Succession (asks Church for eligibility)
├── Spiritual authority          ← NEW, peer to secular politics
│     Faith
│     Clergy status + vows
│     Church hierarchy + Papacy
│     Sacraments
│     Relics + Saints
│     Monasteries + Holy orders
│     Exorcism + Miracles
├── Moral / spiritual state      ← NEW
│     Virtue / sin
│     Corruption
│     Demonic influence
│     Heresy + Cults
├── Catastrophe                  ← NEW
│     Plague
│     Famine
│     Despair
│     Apocalypse escalation
├── Hell                         ← NEW
│     Demonic factions
│     Incursions
│     Supernatural geography
├── Warfare (later; Character/Realm/Army)
├── Economy (later; holdings + tithe + collapse)
└── Playable control (User ↔ Character ↔ Dynasty)
```

Dependency direction:

- World scopes all.
- Geography does not own politics or faith.
- Titles do not own sees.
- Church does not own kingdoms.
- Population feeds levies, tithes, despair, and ruin.
- Apocalypse and Hell *read* geography and *write* overlays; they do not replace Territory.
- Succession may query Church. Church succession (papal, abbatial) does not use `SuccessionStrategy` for counties.

---

## Domain catalog

Each domain lists: purpose, proposed entities, donor relation, and what would be a false shortcut.

### 1. World / Identity

**Purpose.** Isolated campaign instance. Account vs person.

**Entities.** `World`, `User`, `character_control_histories`.

**Donor.** **REUSE WITH EXTENSION** of `worlds` + control histories. Drop live `users` resource/army columns.

**False shortcut.** Putting gold, troops, or piety on `users`.

### 2. Time

**Purpose.** Authoritative date. Scheduled work. Deterministic rolls.

**Entities.** `worlds.current_date`, `scheduled_world_events`, handler registry, `SimulationRandom`.

**Donor.** **REUSE WITH EXTENSION.** Same pipeline, more event types, one clock.

**False shortcut.** A second "apocalypse tick" service that ignores world date.

### 3. Geography

**Purpose.** Physical Europe. Later, wounded Europe.

**Entities.** `Region` (map key adapter), `Territory`, `Holding`, coordinates, terrain, parent land.

**Donor.** **FORK / ADAPT** NUTS + SVG. **REUSE AS-IS** Territory ≠ Title.

**False shortcut.** Letting `regions.owner_id` remain the political truth.

**Overlay (not a replacement table for land):** territory spiritual/catastrophe state (see Population, Hell, Apocalypse).

### 4. Characters and dynasties

**Purpose.** Mortal persons and bloodlines.

**Entities.** `Character`, `Dynasty`, `DynastyHouse`, marriages, parentage, relationships.

**Donor.** **REUSE WITH EXTENSION.** Keep genealogy and control. Add clerical status as a *link* to Church, not a job string.

**False shortcut.** `characters.faith = 'Catholicism'` as the Church.

**New character facts (owned here or by link):**

- Alive / dead / missing / presumed damned (death domain can extend)
- Clerical state via `clergy_statuses` (Church owns the rules)
- Playable dynasty membership (Control owns the rules)

### 5. Secular titles, claims, succession

**Purpose.** Legal political offices and who holds them.

**Entities.** `Title`, `TitleOwnership`, `TitleClaim`, `SuccessionLaw`, `SuccessionPlan`.

**Donor.** **REUSE WITH EXTENSION.** Best donor code.

**False shortcut.** Adding `pope` and `bishop` to `TitleRank`.

**Required extension.** Eligibility hook: `ChurchEligibility` can mark a character ineligible (vows, excommunication, irregular orders) without Title knowing why.

### 6. Political realms and vassalage

**Purpose.** Who answers to whom in the secular world.

**Entities.** `Realm`, `VassalRelationship`, `FeudalContract`.

**Donor.** **REUSE WITH EXTENSION.** Realm stays a cache.

**False shortcut.** Papal states modeled only as a `Realm` with a special name. The Pope may *also* hold secular titles. The papacy itself is Church.

### 7. Faith

**Purpose.** The religion as a body of doctrine and communal identity, not a culture tag.

**Entities.** `Faith` (Catholicism as the primary live faith), optional later schism/faith records, doctrine flags, liturgical calendar keys.

**Donor.** **NEW.** Feudalism has a nullable string.

**False shortcut.** One enum on character and region.

v1 assumes Latin Christianity as the mechanical center. Other communities can exist as population facts before they become full Faith records.

### 8. Clergy

**Purpose.** A person can be in holy orders. That changes succession, marriage, war, and sacrament rights.

**Entities.** `ClergyStatus` (minor orders, priest, monk, nun, bishop, cardinal, etc.), vow set, regularity, laicization history.

**Donor.** **NEW.**

**False shortcut.** `characters.class = 'cleric'` or CK-style trait soup with no office.

Clergy is status. Office is Church hierarchy. A defrocked priest can remain a character with history.

### 9. Church hierarchy

**Purpose.** Parish, diocese, archdiocese, patriarchate/college, papal see. Geographic and personal jurisdiction.

**Entities.** `See` (spiritual jurisdiction over territories), `SpiritualOffice`, `SpiritualOfficeHoldership` (history, same `is_current` pattern as titles), `ChurchProvince`.

**Donor.** **NEW.** Pattern borrowed from title ownership, **not** stored in `titles`.

**False shortcut.** `titles.rank = 'bishopric'`.

Investiture: a secular character or realm may have a *right to nominate* or *duty to accept*. That is a contract between Title/Realm and See, owned by a small `Investiture` relation.

### 10. Papacy

**Purpose.** Unique spiritual apex. Elective, political, and apocalyptic.

**Entities.** `Papacy` (one per world), `Conclave`, papal acts (bull, interdict, excommunication, crusade call).

**Donor.** **NEW.**

**False shortcut.** Strongest theocratic realm, or a User named Pope.

The Pope is a Character holding the papal `SpiritualOffice`. The Papacy is the institution. Vacancy is a first-class state (especially as the world breaks).

### 11. Sacraments

**Purpose.** Mechanical rites that change persons and communities.

**Entities.** `SacramentRecord` (baptism, confirmation, Eucharist as rare event, penance/confession, marriage-as-sacrament distinct from civil marriage history, holy orders, extreme unction).

**Donor.** **NEW.** Marriage table in Feudalism is civil/dynastic only. Keep both: a marriage can be valid in law and irregular in sacrament, or the reverse under collapse.

**False shortcut.** A piety button labeled "Confess".

Sacraments need a minister (clergy), a subject, a place, a date, and validity. Invalid rites matter (simony, clandestine marriage, last rites on the possessed).

### 12. Relics

**Purpose.** Holy matter in the world. Portable, stealable, forgeable, desecratable.

**Entities.** `Relic`, `RelicCustody` (history), authenticity, associated saint, current holding/territory/character.

**Donor.** **NEW.**

**False shortcut.** An item inventory on User.

Relics sit on geography and in politics: a city with a major relic resists despair; a stolen relic is a casus and a sin.

### 13. Saints / patronage

**Purpose.** Canonized persons and the places/people under their patronage.

**Entities.** `Saint` (may point at a dead Character, or at a historical seed person), `Patronage` (territory, dynasty, order, guild-like craft later).

**Donor.** **NEW.**

**False shortcut.** A flavor epithet on dynasty motto.

Feast days schedule world events. Patronage modifies plague, battle, and despair locally.

### 14. Virtue and sin

**Purpose.** Moral state of a person, distinct from prestige and from piety-as-currency.

**Entities.** `VirtueSinLedger` (or parallel to `prestige_transactions`), cached scores, grave vs venial classifications as data not hard theology engine.

**Donor.** **NEW.** Do not reuse `characters.piety` as the only meter.

**False shortcut.** A single `piety` integer that buys miracles.

Prestige = worldly reputation. Virtue/sin = moral record. Piety, if kept, is *devotional standing with the Church*, computed from sacraments, relics, and obedience, not a spendable mana.

### 15. Spiritual corruption

**Purpose.** Rot in a person or a place. Not the same as heresy (belief) or plague (body) or occupation (army).

**Entities.** `CorruptionState` on character and territory, source, intensity, visible signs.

**Donor.** **NEW.**

**False shortcut.** Reusing `territories.control` or `regions.loyalty`.

Corruption can exist under a loyal Catholic count. Loyalty is political. Corruption is spiritual weather.

### 16. Demonic influence

**Purpose.** Agency of hell on a person: temptation, pact, possession, whisper.

**Entities.** `DemonicInfluence` (target character, source faction, stage: temptation → oppression → possession → pact).

**Donor.** **NEW.**

**False shortcut.** `health` going down, or a trait named "possessed".

Influence is a relation to a demonic faction. Exorcism targets this relation.

### 17. Demonic factions

**Purpose.** Hell has politics. Courts, princes, rival incursions.

**Entities.** `DemonicFaction`, goals, rivalries, claimed territories, cult links.

**Donor.** **NEW.** Not a `Dynasty`. Not a `Realm`.

**False shortcut.** One "Hell" kingdom using `realms`.

If a demon prince holds land, geography records occupation/incursion. The faction remains infernal, not a dynasty with a red map color.

### 18. Plague

**Purpose.** The opening catastrophe and a recurring one.

**Entities.** `PlagueWave`, `TerritoryPlagueState` (intensity, strain, arrived_on, peaked_on), character infection as optional later.

**Donor.** **NEW.** Character mortality bands are not plague.

**False shortcut.** Raising `mortality_base_by_age` globally.

Plague is spatial and timed. It creates refugees, kills population, kills characters, empties sees, and feeds despair.

### 19. Famine

**Purpose.** Food collapse from weather, war, blight, labor death, or hell.

**Entities.** `TerritoryFamineState`, harvest modifiers, granary on holdings.

**Donor.** **NEW.** Live `food` on User is not this.

**False shortcut.** Setting `food_yield = 0` on a region row and calling it a system.

### 20. Despair

**Purpose.** Loss of will in a population and in persons. The spiritual twin of famine.

**Entities.** `DespairState` on territory and character.

**Donor.** **NEW.** Not `loyalty`.

**False shortcut.** Loyalty reaching 0.

A terrified loyal city can despair. A cynical heretic town can be politically rebellious without despair. Despair enables cults, suicide of institutions, and failed defense against incursions.

### 21. Heresy

**Purpose.** Doctrinal and popular deviation. Visible enough for inquisition and war.

**Entities.** `Heresy`, `HeresyPresence` on territory/character, condemnation records.

**Donor.** **NEW.**

**False shortcut.** `faith = 'heretic'`.

Heresy is named (flagellants, free spirit, later infernal gospels). It can be sincere or a mask for a cult.

### 22. Cults

**Purpose.** Secret or open worship of demons or of broken saints.

**Entities.** `Cult`, cells, membership, patron faction, hidden vs revealed.

**Donor.** **NEW.** Not `guilds`.

**False shortcut.** Reusing `guilds` / strikes.

Guilds can return later as economic actors. Cults are spiritual-political actors.

### 23. Monasteries

**Purpose.** Houses of prayer, copywork, refuge, granary, and sometimes corruption.

**Entities.** `Monastery` linked to a Holding of a monastic type, rule (Benedictine, etc.), abbot as `SpiritualOffice`, population of religious.

**Donor.** **NEW.** `holding_type = temple` is only a hook.

**False shortcut.** A temple holding with higher piety yield.

Monasteries are where relics hide, refugees arrive, and chronicles are written. They can fall, be sacked, or become cult shells.

### 24. Holy orders

**Purpose.** Military-religious corporations.

**Entities.** `HolyOrder`, commanderies, master as office, army later, independence from both king and (sometimes) local bishop.

**Donor.** **NEW.**

**False shortcut.** A realm with a cross, or a dynasty of celibate knights.

Holy orders sit beside feudal vassalage. They may owe the papacy, a hospital rule, and local protection duties at once.

### 25. Exorcism

**Purpose.** Clergy action against possession and local incursion.

**Entities.** `ExorcismAttempt` (minister, subject or territory, rite, outcome, backlash).

**Donor.** **NEW.**

**False shortcut.** A combat ability on a priest character.

Exorcism consumes Church legitimacy, virtue, relics, and time. Failure can worsen `DemonicInfluence` or open a hell overlay.

### 26. Miracles

**Purpose.** Rare, costly, public ruptures of the natural order from the holy side.

**Entities.** `Miracle` (saint, relic, place, petition, authenticity dispute).

**Donor.** **NEW.**

**False shortcut.** Spend 100 piety to win a battle.

Miracles are scheduled or petitioned events with witnesses and political aftermath (pilgrimage, envy, accusation of sorcery).

### 27. Apocalypse escalation

**Purpose.** World-level stages that change what rules still hold.

**Entities.** `ApocalypseState` on World: stage, signs completed, broken institutions.

**Donor.** **NEW.** Not `game_month`.

**Example stages (design, not locked numbers):**

1. Ordinary medieval order (tutorial truth)
2. Great Mortality (plague opens)
3. Thinning of the veil (corruption, first possessions)
4. Open rifts (hell geography)
5. Collapse of offices (vacant sees, vacant thrones, broken succession)
6. End of the age (win/lose is survival of a people, a faith, or a bloodline)

Each stage unlocks event types and disables assumptions (stable harvest, safe travel, guaranteed papal succession).

### 28. Hell incursions / supernatural geography

**Purpose.** Land that is no longer only Europe.

**Entities.** `SupernaturalOverlay` on territory: ordinary, blighted, haunted, rifted, occupied-by-hell, inverted.

**Donor.** **NEW.**

**False shortcut.** Recoloring a region and setting `controller_id` to a demon User.

Legal title can persist over a rifted county. That is the point: a duke can "own" a wound.

### 29. Population mortality

**Purpose.** People die in numbers that change the map.

**Entities.** `PopulationSnapshot` or running totals on territory: souls, households, clergy count, labor, levy base.

**Donor.** **NEW.** Seed from NUTS population integers, then simulate.

**False shortcut.** Character deaths standing in for the Black Death.

### 30. Refugees / displacement

**Purpose.** People move. Some places empty. Some swell and sicken.

**Entities.** `Displacement` (from territory, to territory or camp, size, cause, date).

**Donor.** **NEW.**

**False shortcut.** Subtracting population in one row without a destination.

Refugees carry plague, heresy, despair, and labor. Cities and monasteries become pressure valves.

### 31. Ruined settlements

**Purpose.** Holdings and territories can die as places.

**Entities.** `RuinState` on holding/territory: abandoned, sacked, depopulated, consecrated-ruin, hell-ruin.

**Donor.** **NEW.**

**False shortcut.** `population = 0` with the town still collecting tax.

Ruin stops ordinary economy and parish life. It may still host relics, ghosts, or a rift.

### 32. End-times progression

**Purpose.** The campaign has a direction. Not a score, a weather.

**Entities.** Owned by Apocalypse + Time. Signs, failed signs, chronicles.

**Donor.** **NEW.**

**False shortcut.** A win screen when you paint the map.

Players participate in a feudal world that is being eaten. Success is dynasty survival, salvation of a people, holding a see, sealing a rift, or dying well. That scoring is later. The clock must exist first.

---

## Warfare and economy (present, deferred as first-class rebuilds)

These are real Dies Irae domains. They are **not** in the first reconstruction wave as ports of `WarService` / `TurnService`.

| Domain | When | Donor |
|--------|------|-------|
| Warfare | After titles, Church, population, and overlays exist | **FORK / ADAPT** fronts/occupation |
| Economy | After holdings + population + tithe + famine exist | **FORK / ADAPT** ledger + holding yields |
| Diplomacy | After realms + papacy exist | **FORK / ADAPT** treaties |
| NPC autonomy | After seed persons and offices exist | **NEW** (not User NPCs) |

---

## Map modes Dies Irae owes the player

| Mode | Reads |
|------|-------|
| Political / realms / dynasties / legal titles | Secular domains |
| Military control | Occupation, including hell occupation |
| Diocesan / faith | Church hierarchy, adherence |
| Plague / famine / despair | Catastrophe |
| Corruption / hell | Spiritual + infernal overlays |
| Ruin / refugees | Population |
| Relics / patronage | Sacred geography |

Political and faith maps **must be allowed to disagree**.

---

## Eligibility and authority matrix (v1 intent)

| Act | Political system | Spiritual system |
|-----|------------------|------------------|
| Inherit a county | Title + succession law | May block (vows, excommunication) |
| Appoint a bishop | Investiture right only if granted | Church office history |
| Call a levy | Contract + population | Holy order has its own call |
| Collect tax | Holding + contract | Tithe is Church |
| Declare feudal war | Realm / claim | May be sinful; not automatically invalid |
| Call a crusade / holy war | May join | Papal or conciliar act |
| Excommunicate a king | Political aftermath | Papal/episcopal act |
| Exorcise a rift | May garrison | Clergy + relic + rite |
| Seal or cede a ruined see | Cannot "conquer" a sacrament | Church vacancy rules |

---

## What Feudalism already gave us, restated as Dies Irae law

Keep these donor laws in the secular half:

- Dynasty ≠ Realm
- Character ≠ User
- Title ≠ Territory
- Realm is derived
- History over mutable FKs
- Preview/execute plans with fingerprints
- Scheduled events over full scans
- World isolation on every row

Add these Dies Irae laws:

- See ≠ Title
- Papacy ≠ Realm
- Monastery ≠ Holding type alone
- Holy order ≠ Dynasty
- Demonic faction ≠ Realm
- Plague ≠ Character mortality
- Despair ≠ Loyalty
- Corruption ≠ Control
- Ruin ≠ Zero gold yield
