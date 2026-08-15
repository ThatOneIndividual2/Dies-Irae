# Holy Orders

**Status:** Implemented kernel  
**Date:** 2026-08-13  
**Peer documents:** `docs/architecture/CHURCH_DOMAIN.md`, `docs/architecture/DIES_IRAE_WARFARE.md`

A Holy Order is a hybrid religious-military corporation. It is not an ordinary army owned by the Church, not a dynasty of celibate knights, and not a realm with a cross painted on it.

The order is the actor. The Pope may recognize it. A king may patronize it. Neither ownership makes the order collapse into that authority.

---

## What an order is not

| False shortcut | Actual model |
|----------------|--------------|
| Church-owned army (`armies.owner` = Pope) | `holy_orders` plus `holy_order_forces` |
| Secular title named "Templar" | Grand Master is a `spiritual_offices` row of rank `grand_master` |
| Dynasty of knights | Memberships and vows, not blood |
| Playable `armies` row | Deployment writes `holy_order_forces`, not `armies` |
| Sacramental grade `HolyOrdersGrade` | That enum is deacon/priest/bishop. Keep it separate. |

`DualAuthorityIndependenceTest` still asserts the order table is neither `dynasties` nor `realms`.

---

## Organization

Stored on `holy_orders` and satellite tables:

| Piece | Table / column |
|-------|----------------|
| Holy Order | `holy_orders` |
| Grand Master | `spiritual_offices.rank = grand_master` pointed at `holy_order_id` |
| Command hierarchy | Grand Master, house commanders, member ranks |
| Members | `holy_order_memberships` |
| Knights / sergeants / chaplains | `member_rank` |
| Houses / commanderies | `holy_order_houses` |
| Land holdings | `holy_order_holdings` (corporate estate, not a title) |
| Treasury | `holy_orders.treasury` + `holy_order_treasury_entries` |
| Relic custody | `relic_custodies.holy_order_id` / `holy_order_house_id` |
| Recruitment | `RecruitHolyOrderMember` against `manpower_cap` |
| Vows | `holy_order_vows` (poverty, chastity, obedience, hospitality) |
| Legitimacy | `holy_orders.legitimacy` |
| Papal recognition | `holy_order_recognitions` + `papal_recognition_status` |
| Secular patronage | `holy_order_patronages` |

History uses the same `is_current` uniqueness pattern as titles and spiritual offices.

---

## Political position

An order may:

- serve the Pope (`papal_recognition_status = recognized`, often `allegiance_type = papal`)
- serve a king (current `holy_order_patronages` row, `allegiance_type = royal`)
- operate independently (no recognition, no patron)
- hold lands inside secular realms (`holy_order_holdings` on a holding whose territory still has a county or kingdom title)
- receive donations (gold ledger, land donations)
- defend pilgrimage routes, fight heretics, fight cults, fight demons (`holy_order_missions`)
- become politically controversial (`controversial`, status `controversial`, or fractured legitimacy)

Papal sanction and royal patronage can coexist. That is obligation, not a merger. `PoliticalPositionResolver` reports `serves_pope`, `serves_king`, and `independent` as separate flags.

Holding land does not grant a `titles` row. The donor keeps the secular title. The order records a corporate estate.

---

## Military differentiation

Doctrine warfare already treats a holy-order belligerent as `BelligerentKind::HOLY_ORDER` with `ForceProfile::holyOrder()` (tithe supply, consecrated by default). That is war physics, not organization.

Organization deployment is `holy_order_forces`. Quality starts at 1.0 and stacks small modifiers from:

- vows kept
- morale
- relics in custody (recognized beats unrecognized)
- chaplains in the force
- consecration
- corruption resistance (against demons)
- reputation
- leadership (a sitting Grand Master)

They are not a blanket upgrade. The same calculator applies penalties from:

- limited manpower / understrength
- political obligations (papal or royal allegiance)
- institutional corruption
- fractured legitimacy
- loss of papal recognition
- internal schism
- excommunication

Floor 0.45, ceiling 1.45. A corrupt, unrecognized, understrength house is worse than a levy. A healthy house with a recognized relic and chaplains is better, not unbeatable.

`DeployHolyOrderForce` does not insert into the playable `armies` table.

---

## Foundation and dissolution

| Act | Effect |
|-----|--------|
| Found | Unsanctioned independent corporation, Grand Master office, founder as member |
| Headquarters | Active mother house on a holding |
| Expansion | Further commanderies / preceptories |
| Papal recognition | `SanctionHolyOrder` (pope). Writes recognition history, papal protection, legitimacy `recognized` |
| Withdrawal | Pope removes protection. Legitimacy becomes disputed. Order may become controversial. |
| Suppression | Pope freezes houses, disbands forces, status `suppressed`. Assets not yet seized. |
| Excommunication | Order-level censure. Recognition withdrawn. Cannot deploy. |
| Dissolution | Status `dissolved`. Memberships, patronage, missions, houses end. |
| Confiscation | Current estates and treasury transfer to the confiscator. Relic custody leaves the order. |

Papal acts go through `ChurchAuthority` (`sanction_holy_order`, `suppress_holy_order`, `withdraw_holy_order_recognition`). Organization mutations live in `HolyOrderService`.

---

## Cross-domain

| Domain | Integration |
|--------|-------------|
| Church | Papal sanction, suppression, withdrawal, excommunication. Grand Master is a spiritual office, not a title. |
| Warfare | Belligerent kind `holy_order`. Organization forces stay on `holy_order_forces`. |
| Economy | Treasury ledger and donations. Not a realm tax farm. |
| Titles | Donor title is recorded. The order does not become a duke. |
| Diplomacy | Secular patronage is the bond to a king. |
| Relics | `RelicCustodianType::HOLY_ORDER`. Recognized relics feed force quality. |
| Apocalypse | High pressure strains corruption, morale, and reputation. |
| Heresy | Mission `fight_heretics`. Schism can mark legitimacy `schismatic`. |

Config: `config/holy_orders.php`. Provider: `HolyOrdersServiceProvider`. Contract: `HolyOrderContract`.

---

## Tests

`tests/Feature/HolyOrders/HolyOrderOrganizationTest.php` covers:

- independent order
- papally sanctioned order
- secular patronage
- land ownership inside a secular realm
- deployment (and that it is not an ordinary army)
- corruption
- suppression
- dissolution / confiscation
- relic custody

Existing Church tests still require `SanctionHolyOrder` to set `sanction_status = sanctioned` and `papal_protection = true`.
