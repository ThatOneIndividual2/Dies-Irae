# Heresy and Schism

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Peer documents:** `docs/architecture/CHURCH_DOMAIN.md`, `docs/architecture/DEMONIC_THREAT_ENGINE.md`

Religious fracture is not one meter. Doctrinal error, broken obedience, personal apostasy, popular piety, secret cells, demonic patronage, anti-clerical riot, and false prophecy are different facts. They share a movement envelope so they can spread, hide, and be answered. They do not share a single status column.

A reform preacher is not a heretic until a competent ordinary condemns a doctrine. A bishop who obeys an antipope is not, by that fact, a teacher of false doctrine. A peasant riot against tithes is not a cult. A cult is not automatically a hell overlay.

---

## Envelope vs ledger

`religious_movements` is the shared envelope:

- `kind` (discriminator)
- `visibility` (`secret`, `rumored`, `public`)
- `radicalization`
- founder / leader / origin
- `status` (`active`, `reconciled`, `healed`, `suppressed`)

Kind-specific ledgers hold the facts that must not collapse:

| Kind | Ledger | What it records | Typical consequence |
|------|--------|-----------------|---------------------|
| `doctrinal_heresy` | `heresies` + `heresy_presences` | Named doctrine error, judgement | Condemnation, excommunication, inquisition, reconciliation |
| `schism` | `schisms` + obedience tables | Rival claims and parallel networks | Obedience split, interdict, papal intervention, healing |
| `apostasy` | `apostasy_states` | One person's abandonment of the faith | Penance or excommunication of that person |
| `popular_movement` | `popular_movement_states` | Fervor, violence, orthodoxy stance | Preaching, synod, penance, regularization |
| `clandestine_cult` | `cult_organizations` (no demonic faction) | Cells, secrecy, recruitment | Investigation, penance, secular arrest |
| `demonic_cult` | `cult_organizations` + optional hell `cults` | Patronage, sacrifice, hidden objective | Inquisition, military suppression, overlay reveal |
| `anti_clerical_unrest` | `anti_clerical_unrests` | Riot intensity against clergy and property | Preaching, interdict, secular suppress/negotiate |
| `false_prophet_movement` | `false_prophet_states` | Claimed revelation, following, examination | Synod, condemnation of the claim, not automatic schism |

Policy lives in `config/fracture.php`. Spread vectors, church replies, and secular replies are per kind. A reply missing from the kind list is rejected, even if the actor has the office or the crown.

Engine: `App\Domain\Heresy\FractureEngine`.  
Actions: `App\Actions\Heresy\*`.

---

## What each kind is not

**Doctrinal heresy** is a false teaching about the faith. Founding a heresy movement *names* an error (`judgement = named`). It does not condemn it. Condemnation is a later church act (`theological_condemnation`) that requires `declare_heresy` power.

**Schism** is a break in obedience: two claimants, two networks. It uses `papal_claims` and does not replace `papacies.papal_office_id`. A schismatic bishop may still teach orthodox doctrine. Theological condemnation is not a legal reply to schism.

**Apostasy** is a person leaving the faith. It is not a territorial heresy presence and not a rival papacy.

**Popular religious movement** is public fervor that may be orthodox, reformist, or later radicalized. It is regularized by preaching, synod, and penance. It is not reconciled "from heresy." Condemnation is forbidden unless the kind itself changes.

**Clandestine cult** is a human secret society: cells, recruitment, infiltration. It has no demonic `faction_id` and must not write a hell overlay `cults` row.

**Demonic cult** may take a `demonic_factions` patron, run sacrifice rites, and optionally stamp a hell overlay `cults` row (`organization_id`, `kind = demonic`). That overlay is geography and patronage. The living organization is `cult_organizations`.

**Anti-clerical unrest** is violence and refusal aimed at clergy and church property. Failed suppression of heresy or popular fervor can spawn it. It is not a doctrine and not a cult.

**False prophet movement** is a charismatic claim of private revelation. A local synod *examines*. Theological condemnation judges the claim. That is not the same as an antipope (no papal claim) and not the same as apostasy (the prophet may still profess the old faith, wrongly).

---

## Spread

`movement_presences` are per territory (`is_current` history). `movement_spread_events` record the vector.

Vectors (`SpreadVector`):

| Vector | Gate |
|--------|------|
| clergy | Current clergy adherent, or `clergy_adherents` on the origin presence |
| preaching | Movement and origin presence are `public` |
| migration | Adjacency, or a large popular following |
| trade_routes | Adjacent territories |
| noble_patronage | A current patron holds a secular title (or owns the target land) |
| war | An army in origin or target |
| famine | Food stores exhausted or ruin state starving |
| plague | Active plague state on origin or target |
| despair | Despair intensity at or above policy threshold |
| corruption | Territory corruption at or above policy threshold |
| charismatic_leaders | Movement has a leader, or the actor is the founder |

Secret movements cannot preach in public. A kind that omits a vector cannot use it (clandestine cults do not preach; demonic cults do not ride trade as a listed vector).

Spread of a cult also plants or strengthens a `cult_cells` row in the destination.

---

## Secrecy and detection

Visibility is independent of kind intensity.

| Visibility | Meaning |
|------------|---------|
| `secret` | No public knowledge. Cult discovery_state `hidden`. |
| `rumored` | Talk exists. Not confirmed. |
| `public` | Open presence. Cult discovery_state `revealed`. |

Detection sources (`DetectionSource`) write `fracture_detections` and do **not** all reveal:

| Source | Outcome | Public reveal |
|--------|---------|---------------|
| rumor | `rumor` | No. Secret becomes rumored. |
| investigation | `confirmed` | Yes |
| denunciation | `confirmed` | No (private accusation) |
| confession | `sealed` | No. Seal is stored. |
| informant | `confirmed` | No |
| clergy_report | `confirmed` | Yes |
| inquisitorial_investigation | `confirmed` | Yes |
| false_accusation | `false` | No. Never reveals. |

Church inquisition is both a detection source and a church response. It does not invent a second heresy table.

---

## Church response

Recorded on `church_fracture_responses`. Rank gates are in `config/fracture.php` (`church_response_ranks`). The actor must hold a spiritual office of an allowed rank.

| Response | Distinct effect |
|----------|-----------------|
| preaching | Lowers popular fervor / presence intensity |
| theological_condemnation | Allowed only for doctrinal heresy and false prophecy. Sets `heresies.judgement = condemned`. Does not convert a reform movement into a heresy. |
| local_synod | Examines popular movements and false prophets. Records schism. Does not itself condemn doctrine. |
| excommunication | Uses `ChurchAuthority::excommunicate` on the movement leader |
| interdict | Uses `ChurchAuthority::placeInterdict` on the origin territory |
| inquisitorial_investigation | Public detection |
| reconciliation | Heresy reconciled, schism healed, apostasy closed. Popular movements are rejected here. |
| penance | Popular movements may be `church_regularized`. Others take assigned penance. |
| military_suppression | Same suppression math as secular force, church-initiated |
| papal_intervention | Pope only. Forces public reveal. |

Failed suppression raises `radicalization`. For popular movements and doctrinal heresy it can spawn a separate `anti_clerical_unrest` movement. For cults it deepens secrecy and returns them to `hidden`.

---

## Secular response

Recorded on `secular_fracture_responses`. The actor must currently hold a secular title. A king may patronize a heresy without gaining a spiritual office. A prince-bishop may act on both axes because he holds both offices, not because the axes merged.

| Response | Distinct effect |
|----------|-----------------|
| tolerate | Recorded permission, no doctrinal change |
| suppress | Intensity check; failure radicalizes |
| exploit | Recorded political use of the movement |
| patronize | Actor becomes a patron adherent; movement becomes public |
| negotiate | Softens popular fervor |
| expel | Recorded expulsion |
| imprison_leaders | Recorded seizure of persons |
| confiscate_property | Recorded seizure of goods |

Rulers cannot issue church condemnations. Clergy without a title cannot issue secular replies.

---

## Schism architecture

Schism wraps the existing papacy kernel. It does not invent a second `papacies` row.

```text
papacies.papal_office_id     = canonical papal office (unchanged)
papal_claims                 = recognized claimant + antipope + disputed election
schisms                      = fracture envelope for that split
schism_obediences            = characters choosing a claim
schism_see_allegiances       = sees / rival bishops choosing a claim
schism_secular_backers       = crowns backing a claim
```

Supported now:

- Rival bishops: a see pledges to the rival claim while the papal see remains with the recognized claim.
- Antipopes: `PapalClaimStatus::ANTIPOPE` plus a `schisms` row. Multiple current `papal_claims` set `papacies.status = schism`.
- Disputed papal elections: two (or more) current claims on one papacy. `claimPapacy` already marks `disputed` then `schism`.
- Parallel obedience networks: `schism_obediences` + `schism_see_allegiances`.
- Secular backing of competing claimants: `schism_secular_backers` (title required).

Ready for later expansion without a new discriminator:

- Rival patriarchal claims: same allegiance tables, pointed at a non-Roman see and a future patriarchal office. Do not reuse `TitleRank`. Do not collapse into heresy.

Healing a schism (`reconciliation` by a competent cleric) sets `schisms.status = healed` and ends the movement. It does not by itself delete papal claim history.

---

## Cults

`cult_organizations` is first-class cult life. Hell overlay `cults` is optional demonic geography.

| Concern | Table |
|---------|--------|
| Organization, kind, secrecy, discovery, hidden objective | `cult_organizations` |
| Local cell | `cult_cells` |
| Membership / leadership | `cult_members` |
| Rites | `cult_rituals` |
| Infiltration of see, office, or court | `cult_infiltrations` |
| Hidden objectives | `cult_objectives` |
| Demonic map overlay (optional) | `cults.organization_id` |

Rules:

- Clandestine cults must not require `faction_id`.
- Sacrifice rites (`requires_sacrifice`) belong to demonic cults only.
- Recruitment is per cell. A person may currently belong to only one cult organization.
- Infiltration marks `infiltrating_church` when the target is a see or office.
- Discovery is a state (`hidden` / `revealed`), not a heresy judgement.
- Destroying a hell overlay cell later must not be confused with reconciling a heresy.

---

## Cross-domain boundaries

Political authority and spiritual authority remain distinct (`CHURCH_DOMAIN.md`).

- Patronizing a heresy does not confer holy orders.
- Backing an antipope does not make the king a cardinal.
- Excommunicating a heresiarch does not revoke his county.
- Hell corruption and despair are spread *gates*, not heresy kinds.
- `TitleRank` stays barony through empire. Antipope is a `papal_claims` status.

---

## Persistence notes

Additive migration: `database/migrations/2026_08_13_210000_create_religious_fracture_tables.php`.

Current rows use the existing `is_current` nullable unique pattern. Do not rewrite `000650` heresy tables or `000900` hell overlay except for the additive `cults` columns (`organization_id`, `kind`, `discovery_state`) and nullable `faction_id`.

---

## Tests

`tests/Feature/Fracture/ReligiousFractureScenariosTest.php` covers:

- Harmless reform movement (regularized, not condemned)
- Doctrinal heresy (named, then condemned)
- Clandestine cult (secrecy, false accusation, sealed confession, no sacrifice)
- Demon cult (faction, overlay, sacrifice, infiltration, inquisitorial reveal)
- Antipapal schism (rival claims, see allegiance, secular backing)
- Ruler backing heresy (patronage spread)
- Successful reconciliation
- Failed suppression causing radicalization and anti-clerical unrest

Related church kernel: `tests/Feature/Church/DualAuthorityIndependenceTest.php`.
