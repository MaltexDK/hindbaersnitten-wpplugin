# hindbaersnitten-wpplugin
WP-plugin til visning af hindbærsnitteanmeldelser

## Brug pluginet

Udviklet af [Maltex Gruppen](https://www.maltex.dk).
Sæt først API-base-URL under **Indstillinger → Hindbærsnitte API** (uden afsluttende `/`).

### Shortcodes

Indsæt en **Shortcode**-blok på en side eller et indlæg:

- `[snitter_reviews]`: liste med klikbare overskrifter til sortering efter overskrift, bageri/sted, karakter og dato. Klik på en anmeldelse for at åbne den i listen.
- `[snitter_reviews limit="20" sort="rating" dir="desc" q="København"]`: begræns antal, vælg startsortering og søgning. `sort` kan være `date`, `rating`, `title` eller `author`; `dir` er `asc` eller `desc`. `page` vælger API-side, og `per_page` kan bruges i stedet for `limit`.
- `[snitter_review id="42"]`: én fuld anmeldelse med det angivne ID.
- `[snitter_latest]`: de 1–3 nyeste anmeldelser som klikbare kort med overskrift, bageri/sted, karakter, tekstuddrag og eventuelt thumbnail. Klik for at åbne hele anmeldelsen i en popup.

Kolonneoverskrifterne sorterer de anmeldelser, der er indlæst i den viste liste.
Klik igen for at vende rækkefølgen. Karakteren går fra 0 til 6 krymmel.

### Gutenberg-blokke

Tryk **+** i blokeditoren og søg efter **Snitter**:

- **Snitter – Liste**: vælg startsortering, retning, antal og søgning i højre sidepanel.
- **Snitter – Anmeldelse**: angiv anmeldelsens ID i sidepanelet.
- **Snitter – Nyeste**: indsæt blokken direkte, fx på forsiden. Den viser automatisk 1–3 anmeldelseskort afhængigt af blokkens bredde. Vælg eventuelt bred eller fuld bredde i blokkens værktøjslinje.

**Nyeste-kortene** viser ét kort i smalle områder, to når der er mindst ca. 576 px,
og tre fra ca. 896 px. Det gælder også i sidekolonner på store skærme.
Med shortcode kan du placere blokken i en bred Gruppe for at give plads til flere kort.
Der vises højst tre anmeldelser i én række; de nyeste vises først.

Popup'en viser hele anmeldelsen og eventuelt billede. Luk med **Luk**, **Escape**
eller et klik udenfor boksen. Fokus vender tilbage til kortet.
Uden JavaScript åbnes hele anmeldelsen direkte under kortet.
`[snitter_review id="42"]` viser fortsat en fuld anmeldelse direkte på siden.

Blokkene er dynamiske; de henter API-data ved visning og behøver ikke gemmes igen, når der udgives en anmeldelse. API-svar caches kortvarigt (ca. 1–2 minutter).

## Ændringer i 1.1.0

- Nyeste-visningen er nu 1–3 responsive anmeldelseskort med tekstuddrag og valgfrit thumbnail.
- Hele anmeldelsen åbnes i en popup med tastaturbetjening og fokusretur.
- Bred og fuld bredde understøttes i Gutenberg.
- Visningen fungerer også uden JavaScript med udfoldning direkte på siden.

## Ændringer i 1.0.4

- Datoer sorteres kronologisk med numeriske tidsstempler, også ved danske datoformater. Manglende og ugyldige datoer vises uden dato og sorteres sidst.
- Brugsvejledningen findes nu også i WordPress’ pluginoplysninger.
- GitHub- og udviklerinstruktioner er fjernet fra denne vejledning.
