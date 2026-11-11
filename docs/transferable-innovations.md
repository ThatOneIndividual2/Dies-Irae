# Transferable innovations

Patterns from Dies Irae that can move to Daniel's other browser strategy games (BFTH, Secession, GOT, BlastShadows, and siblings). The names below are Dies Irae names. The idea is what travels.

## 1. Cohort clock beside the map integer

The map still shows one population number per territory, because that is what the player and the levy button already read. The demographic authority is a settlement census: nobles, clergy, burghers, peasants, unfree, plus infection batches.

When a scripted choice adds or removes people, both layers move by the same delta. The daily plague tick writes the census, then applies only the net change (deaths and flight) to the territory integer. It does not replace the territory total from the census. A refugee decision made earlier in the day is not erased when the tick runs.

Use this anywhere a game already shipped a single population column and later grew a real demographic model. Do not make the new model the only write path on day one, and do not let the tick stamp the old column with an absolute value.

## 2. Kernel pulse with a delayed map promotion

Hell has a full in-memory engine (incursion states, neighbor leak, countermeasures). The player map has a coarser overlay: ordinary, haunted, rifted.

Once the apocalypse stage is at least `thinning_veil`, the calendar runs that engine once per world-date and writes the incursion row, a capped corruption and despair delta, and an overlay intensity. The overlay kind stays haunted until the engine's own breach rule can fire (apocalypse intensity at least 40 and a breached incursion). A few catch-up days cannot repaint the county.

The same split fits any game that has a simulation kernel and a map mode the player already understands. Persist the kernel's state. Promote the map only when the kernel's published threshold says the promotion is legal. Key the pulse by world id and date so a catch-up loop cannot apply it twice.

## 3. A rite is a record, not a piety spend

Mass for the dead and anointing of the sick call `ConferSacrament`. That writes minister, subject, place, date, and validity, then applies the sacrament's own consequences. An unbaptized lord is given one historical baptism dated to birth before the public rite, so the mass is not invalid for a missing font. An exorcism is a hell countermeasure with a stored result. The county's corruption moves from that result.

For other games: if a religion, magic, or legitimacy system can succeed, fail, or be illicit, store the attempt. Do not subtract a flat "unrest" number and call it the rite.

## 4. Death is a title history plus a control handoff

`ProcessCharacterDeath` marks the person dead once, walks current secular titles, asks the Church eligibility gate, and either transfers with acquisition type `succession` or revokes to vacancy. The same pass moves the top liege, the vassal contracts, the house head, the personal treasury, the church standing, player levies, and the user who was controlling the dead person.

The title code does not know why a monk or an excommunicate is barred. The gate returns a reason. A second call for the same character returns the existing processing row.

This is the feudal handoff other games already need when a player character dies: one idempotent action, append-only ownership, and the account follows the heir instead of staying on a corpse.

## 5. What this pass did not pretend to solve

Named nobles do not die automatically when a settlement's noble cohort collapses. The count's death is a decision after the chronicle (`count_stricken`), due two days after the tenth beat, and it is not part of the original ten-beat sequence.

The hell pulse does not open a rift by itself while the stage is only thinning of the veil. Doctrine war (`warfare_*`) is still not the army page. Diplomacy, the faith service, and the sin/virtue service are still empty facades. Virtue and sin live on the spiritual engine. Trade, guilds, and buildings are still absent. NUTS-scale geography is still not the playable county.
