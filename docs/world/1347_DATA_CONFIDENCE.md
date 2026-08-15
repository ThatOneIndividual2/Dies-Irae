# Europa 1347 data confidence

Every layer in `database/data/europa/1347/` is graded. Exact-looking numbers are still approximations unless marked HIGH.

Grades:

- **HIGH:** well-attested office, person, or event at this date
- **MODERATE:** standard narrative with known scholarly dispute or fuzzy timing
- **APPROXIMATE:** order-of-magnitude or conventional figure, not a census
- **GAMEPLAY ABSTRACTION:** stored this way because the engine needs a row, edge, or rank

Sources used as orientation, not as a bibliography dump: conventional political chronology for 1346-1347; the usual western plague itinerary (Caffa to Constantinople to Messina); city-size traditions in the Bairoch / Chandler / Russell family; Russell-style kingdom population scales collapsed onto a handful of nodes.

Piast `founded` is stored as 1000-01-01 because the engine uses SQL DATE. The house is older than that.

## World frame

| Item | Grade | Notes |
| --- | --- | --- |
| Start year 1347 | HIGH | Requested opening. |
| Calendar day 1 October | MODERATE | Chosen to sit after Calais and on the traditional Messina landfall. Other days in autumn 1347 would also be defensible. |
| Slug `europa-1347` | GAMEPLAY ABSTRACTION | Engine world key. |
| Faith split Latin / Greek / Nasrid Islam | MODERATE | Three playable rites. Does not model Jewish communities, dual-rite towns, or Latin patriarchs in the East as separate faiths. |
| Agnatic primogeniture as default law | GAMEPLAY ABSTRACTION | Most crowns used mixed custom. Elective law is reused for the Empire and communes. |

## Geography

| Item | Grade | Notes |
| --- | --- | --- |
| Presence of listed kingdoms and cities | HIGH to MODERATE | The named places existed. The set is a skeleton, not every shire. |
| Neighbor graph, including sea lanes | GAMEPLAY ABSTRACTION | Adjacency is how armies and plague walk. Channel, Adriatic, and Black Sea links are edges. |
| `tunis-gate` | GAMEPLAY ABSTRACTION | African approach node owned by Sicily so the graph does not float. |
| Terrain tags | APPROXIMATE | Coast / plains / hills / riverland only. |
| Territory = city-plus-hinterland | GAMEPLAY ABSTRACTION | One row stands for a city, its county, and often a whole duchy. |

## Population and agriculture

| Item | Grade | Notes |
| --- | --- | --- |
| Relative city sizes (Paris largest in the west, Italian communes next, London behind them, Constantinople reduced from its peak) | MODERATE | Directionally in line with the Bairoch/Chandler tradition. |
| Exact headcounts (Paris 200000, Venice 110000, London 80000, and the rest) | APPROXIMATE | Rounded play figures. Not tax rolls. |
| Agricultural capacity 0-100 | GAMEPLAY ABSTRACTION | Hinterland fertility index for later harvest code. Not yields in quarters. |
| Levy available | GAMEPLAY ABSTRACTION | `population * levy_ratio`. Not a feudal host list. |

## Dynasties, rulers, titles

| Item | Grade | Notes |
| --- | --- | --- |
| Identity of major kings, the emperor, the pope, Dusan, John VI, Yusuf I | HIGH | Standard 1347 incumbents. Some birth days are conventional (1 January) when the real day is unused by play. |
| Louis of Sicily as a child in 1338 | MODERATE | Year is right; day is conventional. |
| David II resident in London | HIGH | Prisoner after Neville's Cross (1346). |
| Charles IV not a vassal of Louis IV | HIGH | Anti-king from 11 July 1346. Stored as a `dispute` relation. |
| Edward III not a vassal of Philippe VI | GAMEPLAY ABSTRACTION | Homage crisis was real. The engine allows one current liege, so Aquitaine is owned without a French vassal row. |
| Commune "rulers" (Florence Priors, Lubeck council, Ragusa rector) | GAMEPLAY ABSTRACTION | One character stands for a college. |
| Republics as duchy-rank titles | GAMEPLAY ABSTRACTION | Venice, Genoa, Florence, Ragusa, Lubeck. Elective law is a stand-in for councils. |
| County of Flanders stored as a duchy rank | GAMEPLAY ABSTRACTION | Rank weight must sit under the French crown. |
| Lordship of Ireland as a duchy, Kildare as a county | GAMEPLAY ABSTRACTION | Prevents equal-rank parent errors. Cork has no separate title. |
| Thin vassal trees outside England/France/Scotland/Austria | APPROXIMATE | Other counts and prince-bishops exist in history and are not all seeded. |
| Magnus's Sweden-Norway union as one realm with two kingdom titles | MODERATE | Personal union. Norway is not a vassal of Sweden. |
| Serbian imperial title | HIGH | Coronation 1346. |
| Hospitaller Rhodes as a secular duchy | GAMEPLAY ABSTRACTION | Dieudonne de Gozon is not also stored as a second spiritual office. |

## Church

| Item | Grade | Notes |
| --- | --- | --- |
| Clement VI pope, resident at Avignon | HIGH | 1342-1352. Rome is not the papal seat in this seed. |
| Clement also holding the Comtat | MODERATE | Papal temporal control of Avignon/Comtat. Stored as county `d-venaissin`. |
| Named metropolitans (Stratford, Zouche, Walram, Virneburg, Arnost, Albornoz) | HIGH to MODERATE | Offices are right; a few forenames are conventional where the pack uses an `ordinary-*` key. |
| See of London vacant | GAMEPLAY ABSTRACTION | Avoids giving Stratford two current clergy rows. Historically London had its own bishop. |
| Isidore I as Greek patriarch, not the emperor | HIGH | Dual authority: church office is not a secular title. |
| Esztergom sitting on Buda, Chartres on Orleans, Walsingham on York, Bari on Naples, Iona on Stirling | GAMEPLAY ABSTRACTION | Nearest seeded territory. |
| Cardinal legate at Rome (Annibaldo di Ceccano) | MODERATE | A real cardinal; used here as the Roman see-holder while the pope is in Avignon. |
| One papal office per world | GAMEPLAY ABSTRACTION | Engine rule. No western schism yet (that is 1378). |

## Trade, pilgrimage, monasteries

| Item | Grade | Notes |
| --- | --- | --- |
| Wool to Flanders, Rhine, Hansa Baltic, Gascon wine, Levant spices, Genoese Black Sea, Sicilian grain | MODERATE | These corridors were real in outline. Stops are the seeded ports only. |
| Corridor volumes and goods tags | APPROXIMATE | Labels for later economy code. |
| Existence of Cluny, Citeaux, Monte Cassino, Westminster, Compostela, Becket's shrine, the Magi at Cologne | HIGH | Famous houses and shrines. |
| Religious population counts | APPROXIMATE | Order-of-magnitude convent sizes. |
| Stoudios as basilian on Constantinople | MODERATE | Greek house; faith is `greek-rite`. |

## Political relations

| Item | Grade | Notes |
| --- | --- | --- |
| Open Anglo-French war | HIGH | Since 1337 in this telling. |
| Anglo-Scottish war / David captive | HIGH | |
| Castile pressure on Granada after Salado | MODERATE | Intermittent, not a single named war with a clean end date. |
| Venice-Genoa rivalry | HIGH | Not yet the 1350-1355 war. |
| Hungarian claim on Naples after Andrew's murder (1345) | HIGH | Louis I's expedition is 1347-1348; stored as `claim` / preparation, not as a fully occupied Naples. |
| Aragon-Genoa Sardinian war | HIGH as a skip | Dated 1351 and marked `"skip": true`. Not seeded. |
| Serbian pressure on Byzantium | MODERATE | Expansion was real; intensity is a tag. |

## Black Death

| Item | Grade | Notes |
| --- | --- | --- |
| Plague not yet across Europe in October 1347 | HIGH | Western interior is still clear. |
| Caffa as a conventional western entry story | MODERATE | Famous Gabriele de' Mussi narrative. Disputed in detail, kept as the game's opening node. |
| Constantinople infected by spring/summer 1347 | MODERATE | Standard itinerary. |
| Messina landfall around autumn 1347 | MODERATE | Traditional Italian opening. Exact day is not known; 1 October is the seed's clock. |
| Genoa / Marseille / Venice threatened, not infected | APPROXIMATE | They sit on the next hops. Infection is left for simulation. |
| Strain key `yersinia-game` | GAMEPLAY ABSTRACTION | Not a medical model. See plague/population kernel docs. |
| Apocalypse stage `great_mortality` | GAMEPLAY ABSTRACTION | Ties the historical wave to the Dies Irae stage clock. |

## Validation coverage

`php artisan diesirae:validate-world --historical` treats these as errors:

- start year other than 1347
- too few territories or realms for a production map
- parent / de jure title rank inversions or cycles
- kingdom or empire with no current holder
- vassal loops (shared with the base validator)
- disconnected territory graph
- church see cycles, wrong metropolitan, duplicate office on a see, papal office count other than one
- empty kingdom capitals, city population above 400000, agriculture outside 0-100
- realm liege who does not hold the primary title
- births, title grants, offices, plague arrivals, or relations after the world date
- missing Caffa / Constantinople / Messina infection
- infection of the western/interior clear list
- papacy not seated at Avignon
- missing trade corridors or pilgrimage sites

The fixture world `lys-1348` is supposed to fail this flag.
