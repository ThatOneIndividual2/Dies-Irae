# Europa 1347 world seed

Opening snapshot for Dies Irae, not a census and not a claim of perfect accuracy.

- **World slug:** `europa-1347`
- **Start date:** 1 October 1347
- **Pack path:** `database/data/europa/1347/`
- **Seeder:** `Database\Seeders\HistoricalWorldSeeder`
- **Does not replace** `lys-1348` or `provence-1347`

October 1 sits after the surrender of Calais (August 1347) and on the traditional Messina landfall of the Great Mortality. Western and interior capitals stay clear.

## How to load and check

```
php artisan diesirae:seed-historical
php artisan diesirae:validate-world --historical
```

`diesirae:seed-historical` skips if `europa-1347` already exists. It does not truncate other worlds. `diesirae:seed-1347` is a separate campaign/archetype seeder that uses the same slug; whichever runs first owns that world.

`diesirae:validate-world --historical` checks title hierarchy, missing kingdom/empire rulers, vassal loops, disconnected settlements, church hierarchy, duplicate offices, population anomalies, realm membership, dates, Avignon papacy, and the early plague pattern.

## What is on the map

About seventy territories across Europe and the Mediterranean core:

England, France, the Holy Roman Empire, the Iberian kingdoms, Italian states, papal lands, Scotland, Ireland, Poland, Hungary, Bohemia, the Balkans, Byzantium, Scandinavia, major islands, Crusader remnants (Cyprus, Hospitaller Rhodes), and a thin African-approach node at Tunis.

Sea links are stored as ordinary neighbor edges. That is a play map, not a sailing simulator.

## Politics on 1 October 1347

| Situation | How the seed stores it |
| --- | --- |
| Hundred Years War | England and France at war. Edward III holds Aquitaine and Calais. He is **not** Philippe VI's vassal (one current liege per character). Homage is a `homage_dispute` relation. |
| Calais | English-owned after the August surrender. |
| Scotland | David II is a prisoner with residence London. Robert Stewart holds the stewartry as a Scottish vassal. |
| Empire | Louis IV holds the imperial title. Charles IV holds Bohemia as anti-king. That dispute is a relation, not a vassal bond. |
| Italy | Venice, Genoa, Florence, Ragusa, and Lubeck are duchy-rank republics with elective law. |
| Serbia | Imperial title after Dusan's 1346 coronation. |
| Byzantium | John VI Kantakouzenos. |
| North | Magnus Eriksson holds Sweden and Norway as two kingdoms under one realm. |
| Papacy | Clement VI at Avignon. He also holds the Comtat Venaissin as a secular county. Rome is a see with a cardinal legate. |
| Sicily | Luigi of Trinacria is a child king. Tunis Approaches is tagged to that crown as a gate node. |

Major vassal chains are filled for England, France, Scotland, and Austria. Most other crowns are top-level realms with local territory ownership and only a thin vassal tree. That is an explicit skeleton, not a claim that those kingdoms had no counts.

## Church and sacred geography

Latin provinces for Canterbury, York, Reims, Lyon, Mainz, Cologne, Prague, Gniezno, Esztergom, Toledo, Santiago, Naples, Avignon, and Lund. Greek patriarchate at Constantinople (Isidore I, not the emperor).

The see of London is vacant so John Stratford is not given two bishoprics.

Monasteries and pilgrimage sites sit on the nearest seeded territory when the real site is off the skeleton (Chartres on Orleans, Walsingham on York, Bari on Naples, Iona on Stirling, Esztergom on Buda).

## Plague

Wave `great-mortality-1347` is active. Infected territories at start:

1. Caffa (siege story, 1346)
2. Constantinople (spring/summer 1347)
3. Messina (1 October 1347)

Threatened but **not** infected: Genoa, Marseille, Venice, Naples, Palermo.

Required to stay clear: London, Paris, Cologne, Prague, Krakow, Buda, Toledo, Copenhagen, Edinburgh.

Apocalypse stage is `great_mortality`.

## Data files

| File | Contents |
| --- | --- |
| `world.json` | Slug, date, faiths, succession laws |
| `geography.json` | Regions, territories, neighbors, population, agriculture |
| `dynasties.json` | Ruling houses |
| `characters.json` | Rulers, major vassals, pope, metropolitans, commune abstractions |
| `titles.json` | Empires, kingdoms, selected duchies and counties |
| `realms.json` | Playable top-level polities |
| `vassals.json` | Current liege bonds |
| `church.json` | Provinces, sees, Avignon papacy |
| `sacred_sites.json` | Monasteries and pilgrimage sites |
| `trade.json` | Strategic corridors |
| `relations.json` | Wars, rivalries, claims, disputes |
| `plague.json` | Wave, infections, clear list |

Confidence grades live in [1347_DATA_CONFIDENCE.md](1347_DATA_CONFIDENCE.md).
