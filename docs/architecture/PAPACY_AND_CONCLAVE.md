# Papacy and Conclave

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Companions:** [CHURCH_DOMAIN.md](CHURCH_DOMAIN.md), [DIES_IRAE_DOMAIN_MAP.md](../dies_irae/DIES_IRAE_DOMAIN_MAP.md)

The papacy is an office with institutional succession. It is not a realm, a title rank, a piety score, or a single random roll.

One world has one `papacies` row. That row points at a papal see and a papal spiritual office. An antipope is a rival claim against that institution. He does not replace `papacies.papal_office_id`.

---

## What this package owns

| Concern | Owner |
|---------|--------|
| Papal vacancy | `PapacyEngine::openVacancy` / `DeclarePapalVacancy` |
| Cardinal electors | `CardinalElector` + `cardinal_profiles` |
| Conclave eligibility | alive, accessible, present; extraordinary bishops only by fallback |
| Candidate support | preferences, relationships, theology, faction |
| Faction blocs | `CardinalFaction` on each elector |
| Secular influence | `SecularPressure` (diplomacy, patronage, threat, military, alliance) |
| Bribery / patronage | corruption-weighted score, never `installPope` |
| Theological reputation | elector trait, scored into ballots |
| Church legitimacy | `ChurchLegitimacy` (recognized vs rival basis points) |
| Disputed elections | `ConclavePhase::CONTESTED` + `PapacyStatus::DISPUTED` |
| Antipopes | `raiseAntipope` + `papal_claims` |
| Competing obediences | `PapalObedience` (character, see, realm, elector) |
| Papal recognition | `recognize` / `RecognizePapalClaimant` |

Secular appointment of clergy stays in the Church package. This package elects the pope.

---

## Election is a state machine

```text
vacant
  -> assembling
    -> voting
      -> deadlock (optional)
        -> compromise candidate
          -> voting
    -> elected
      -> accepted
        -> enthroned
    -> contested (optional, from elected / accepted / enthroned)
```

Each step is an action. A vacancy does not spawn a pope. A ballot does not skip acceptance. Enthronement is a later state.

Rounds are recorded on the session. Coalitions can move. Deadlock is a first-class phase. The College names a compromise by maximin score (then reputation), then hopeless blocs shift to that name. That is politics, not a die roll.

Majority is two-thirds of electors present, unless an extraordinary reduced College is using simple majority.

---

## Cardinals

A cardinal is a spiritual office holder with a political profile:

- relationships (affinity to other electors)
- political loyalties (`faction`)
- theological leanings
- ambition (self-vote weight; also acceptance)
- fear (threats, military pressure)
- corruption (patronage and bribery)
- realm ties
- Church office
- candidate preferences

Eligibility for a given conclave: living and accessible. Presence is set at assembly. Inaccessible electors (occupied land, broken roads, plague cordon, demonic zone) do not vote.

---

## Secular rulers

Rulers may press the College. They may not appoint the pope.

Allowed pressure:

- diplomacy
- patronage
- bribery
- threats
- military pressure (can occupy Rome and delay assembly)
- political alliances
- support for a rival claimant

`PapacyEngine::installPope` always throws. `attemptImperialAppointment` throws unless every extraordinary gate is open: the College is extinct, Rome is inaccessible, and the apocalypse stage is at least `collapse_of_offices`. That gate is catalog policy, not a hidden shortcut.

---

## Antipope and schism

A rival claimant needs:

- a recognized papal name to oppose
- some College support
- optional see recognition
- optional realm alignment

Effects:

- papacy status becomes `schism`
- legitimacy splits between recognized and rival meters
- bishops and realms may pledge either obedience
- the papal office row does not change hands to the antipope

Reconciliation heals the schism, moves every current obedience onto the surviving claimant, absorbs rival legitimacy, and returns status to `occupied`.

This composes with `FractureEngine::openAntipapalSchism` and `schism_*` tables when the heresy package records the same split.

---

## Apocalypse fallbacks

Succession must get harder. It must not vanish.

| Pressure | Fallback |
|----------|----------|
| Dead cardinals | Remaining eligible electors still elect |
| Inaccessible Rome | Relocate the conclave seat; the papal see stays Rome |
| Occupied territories | Electors from those lands may be inaccessible |
| Demonic incursions | Same as occupation, plus delayed assembly |
| Plague | Delayed assembly; dead electors drop out |
| Schism | Two obediences, not two papacies |
| Destroyed communications | Delayed, incomplete College |
| College below quorum | Reduced College, simple majority |
| No living cardinals | Expand electors to remaining bishops |
| No cardinals and no bishops | Sede vacante persists. No silent pope. |

Extraordinary rules are written onto the session. They are visible. They are not a second hidden election.

---

## Church legitimacy

`ChurchLegitimacy` is not piety and not dynastic blood.

- Vacancy decays the recognized meter
- Enthronement restores some of it
- A contested result penalizes it
- Schism splits it toward the rival
- Reconciliation absorbs the rival meter and restores more

Persisted on `papacies.church_legitimacy_bp` / `rival_legitimacy_bp` and `church_legitimacy_states`.

---

## Persistence

Domain tests run the in-memory engine. Tables exist for the live world:

- `cardinal_profiles`
- `conclave_sessions`
- `conclave_electors`
- `conclave_ballots`
- `secular_papal_pressures`
- `papal_obediences`
- `church_legitimacy_states`

Existing `papacies` and `papal_claims` remain the institutional spine.

---

## False shortcuts (forbidden)

- One random roll that names a pope
- `titles.rank = pope`
- A king writing the papal office holder
- An antipope overwriting `papacies.papal_office_id`
- Auto-electing a pope because Rome fell or the College died
- Treating the papal states as the papacy

---

## Tests

`tests/Feature/Papacy/PapacyAndConclaveTest.php` (domain kernel, no database):

- normal election through vacancy, assembly, ballot, acceptance, enthronement
- deadlocked 3-3-3 College, named compromise, coalition shift
- secular patronage swings votes; `installPope` and ordinary imperial appointment throw
- antipope splits obedience and legitimacy without replacing the office
- pope dies in the apocalypse; a reduced relocated College still elects
- Rome inaccessible delays and relocates; succession continues
- schism reconciliation returns one obedience and restores legitimacy
- extinct College expands to bishops
- no electors left keeps sede vacante; no silent pope
