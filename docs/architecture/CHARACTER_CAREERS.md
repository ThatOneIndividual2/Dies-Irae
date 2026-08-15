# Character Careers and Life Paths

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Peers:** Church (orders, offices), Titles (secular ranks), Spiritual State (interior life), NPC (behavior)

Characters are not static records with a job string. They walk tracks. A career is a trajectory. A title is a legal office. A see is a spiritual office. Interior virtue is none of these.

---

## Law

1. **Career ≠ title ≠ spiritual office ≠ life state ≠ hidden spiritual state.** A prince-bishop is a landed noble career (or still a bishop career) plus a county plus a see. Those three facts stay in three systems.
2. Career history is append-only (`character_careers.is_current`). So are life states and education.
3. Skills reuse Feudalism's public aptitude pattern (tiny integers, default 5, cap 20). They do not store Faith, Hope, Charity, vices, despair, or a piety wallet.
4. `piety_reputation` is what the world thinks of someone's holiness. True spiritual state lives in `character_spiritual_states` and is not a career skill.
5. NPC intents and appointment suitability read careers and life states, not interior vices.
6. World isolation on every row. Mutations go through Actions.

---

## Independent axes on a person

| Axis | Owner | Example |
|------|--------|---------|
| Career track | Careers | page → squire → knight |
| Secular office | Titles | duke |
| Spiritual office | Church | bishop of Paris |
| Holy orders / vows | Church `clergy_statuses` plus career life states | ordained, solemn vows |
| Marital bond | Careers life state (civil). Sacrament of matrimony is Spiritual/Church | married, widowed |
| Confinement | Careers | imprisoned, exile |
| Deviation | Careers overlay; Church heresy tables remain canonical | heretic, cult member |
| Interior soul | Spiritual State | hidden Faith / Lust / despair |

Losing a duchy does not erase knightly training. Becoming a heretic does not automatically un-priest the career; it ruins Catholic appointment suitability and public reputation.

---

## Secular careers

`CareerKey` secular list:

courtier, page, squire, knight, household officer, steward, marshal, diplomat, magistrate, landed noble, mercenary captain, scholar, physician, merchant.

Ladders (not the only legal edges, but the usual ones):

```text
page → squire → knight → landed noble | mercenary captain | marshal | household officer
courtier → diplomat | magistrate | steward | marshal | household officer | landed noble
scholar / physician / merchant     (civic entries from education)
```

Inheritance of a barony or higher forces `landed_noble` even from knight or courtier. That is a life event, not a silent title side effect.

---

## Clerical careers

novice, monk, priest, abbot, canon, bishop, archbishop, cardinal, papal official, theologian, inquisitor, exorcist, chaplain.

```text
novice → monk → abbot | priest
priest → chaplain | canon | theologian | inquisitor | exorcist | bishop | papal official | abbot
bishop → archbishop → cardinal
```

Priest as a career requires **ordination**. The Church still owns `clergy_statuses` and spiritual offices. Career `bishop` means the life-path; `spiritual_offices.rank = bishop` is the see. Appointment suitability asks both.

Novices may leave. Ordained priests and solemnly vowed monks may not leave by the ordinary `leave_role` event.

---

## Life events and states

Events (`LifeEventKey`) that rewrite life states:

| Event | State change |
|-------|----------------|
| enter clergy | `in_clergy`, usually career novice |
| leave role | only if the career and vows allow it |
| take vows | `simple_vows` or `solemn_vows`; blocked if married |
| marry / widow | marital states; blocked by vows, ordination, holy order |
| inherit | title rank on the person; blocked by vows/ordination |
| ordain | `ordained`; career becomes priest if still novice/monk |
| receive / lose benefice | `beneficed` overlay, not the office row |
| imprison / release | `imprisoned` |
| exile / return | `exile`; optional `claimant` |
| join holy order | knightly track + solemn vows + `holy_order` |
| become hermit | `hermit` |
| become heretic | `heretic`; piety reputation falls |
| join cult | `cult_member` |

Married and solemn vows are exclusive. Imprisoned and exile are exclusive. Heretic and priest can coexist. That is the point of the priest-to-heretic trajectory.

---

## Skills

Feudalism gave martial, diplomacy, stewardship, intrigue, learning (and a piety integer that Dies Irae refuses as soul-state).

Public career skills:

| Skill | Role |
|-------|------|
| martial | arms, war, holy-order fighting |
| stewardship | household, abbey, landed administration |
| diplomacy | court, curia, negotiation |
| intrigue | plots, inquisition, exile politics |
| learning | schools and universities |
| theology | clerical appointment, preaching |
| medicine | physicians |
| leadership | command, sees, duchies |
| piety_reputation | public holy fame |

Education and career assignment bump focus skills. They never write `character_spiritual_states`.

---

## Education

Sources: monastery school, cathedral school, noble household, military household, university, apprenticeship.

Each source opens entry careers and applies a skill package when completed (`character_educations`). A noble household does not make you a knight. It makes page and courtier reachable.

---

## AI and appointments

`NpcBehaviorProfile` is what the NPC domain reads:

- Career catalog supplies baseline intents (train, govern see, trade, …).
- Exile + claimant → `plot_return`, `press_claim`, posture `exiled_claimant`.
- Heretic → `preach_heresy`, will not accept Catholic spiritual office.
- Holy order → `crusade`.
- Hermit → withdraw. Imprisoned → endure captivity.

`AppointmentSuitability` scores spiritual vs secular ranks from career ladder, ordination, vows, heresy, imprisonment, and **piety reputation / theology**, never from hidden Hope or Lust.

`CareerNpcAdvisor` is the seam. `NPCService` exposes `behavior()`, `wouldAcceptAppointment()`, and `considerWithCareer()`, which stamps career posture onto an AI `Situation`. `CareerAiGate` then forbids captive action, hermit dynasty play, and exile musters. Ordinary strategic turns that omit career posture are unchanged.

Church appointment policy can call suitability without importing spiritual-state tables.

---

## Persistence

Additive migration `2026_08_13_001300_create_character_career_tables.php`:

- `characters.theology`, `medicine`, `leadership`, `piety_reputation`
- `character_careers` (history)
- `character_life_states` (stackable history)
- `character_educations`
- `character_career_events`

Actions: `AssignCareer`, `ApplyLifeEvent`, `CompleteEducation`. Domain authority: `CareerEngine`.

---

## Tests

`tests/Feature/Careers/CareerTrajectoryTest.php` (domain kernel, no database):

- noble child → page → squire → knight → duke
- novice → monk → priest → bishop
- priest → heretic (career remains priest; Catholic appointment dies)
- knight → holy order (cannot marry)
- courtier → exiled claimant
- novice may leave; ordained priest may not
- skills refuse hidden piety / vices

Run:

```bash
./vendor/bin/phpunit tests/Feature/Careers
```
