=== Hindbærsnitte – Reviews via API ===
Stable tag: 1.1.0
License: GPLv2 or later

Vis anmeldelser fra Hindbærsnitte-API'et med shortcodes eller Gutenberg-blokke.

== Description ==

Udviklet af [Maltex Gruppen](https://www.maltex.dk).
Sæt først API-base-URL under **Indstillinger → Hindbærsnitte API** (uden afsluttende `/`).

= Shortcodes =

Indsæt en **Shortcode**-blok på en side eller et indlæg:

- `[snitter_reviews]`: liste med klikbare overskrifter til sortering efter overskrift, bageri/sted, karakter og dato. Klik på en anmeldelse for at åbne den i listen.
- `[snitter_reviews limit="20" sort="rating" dir="desc" q="København"]`: begræns antal, vælg startsortering og søgning. `sort` kan være `date`, `rating`, `title` eller `author`; `dir` er `asc` eller `desc`. `page` vælger API-side, og `per_page` kan bruges i stedet for `limit`.
- `[snitter_review id="42"]`: én fuld anmeldelse med det angivne ID.
- `[snitter_latest]`: de 1–3 nyeste anmeldelser som klikbare kort med overskrift, bageri/sted, karakter, tekstuddrag og eventuelt thumbnail. Klik for at åbne hele anmeldelsen i en popup.

Kolonneoverskrifterne sorterer de anmeldelser, der er indlæst i den viste liste.
Klik igen for at vende rækkefølgen. Karakteren går fra 0 til 6 krymmel.

= Gutenberg-blokke =

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

== Installation ==

Aktivér pluginet og indstil API-base-URL under Indstillinger → Hindbærsnitte API.
Tilføj derefter en Snitter-blok eller en Shortcode-blok på din side.

== Changelog ==

= 1.1.0 =
* 1–3 responsive anmeldelseskort med tekstuddrag, bageri og valgfrit thumbnail.
* Klik åbner hele anmeldelsen i en popup med tastaturbetjening og fokusretur.
* Bred og fuld bredde i Gutenberg samt inline-visning uden JavaScript.

= 1.0.4 =
* Kronologisk sortering med numeriske tidsstempler og understøttelse af danske datoformater.
* Brugsvejledning i WordPress’ pluginoplysninger.
* GitHub-instruktioner fjernet fra brugervejledningen.

= 1.0.3 =
* Sorterbare kolonneoverskrifter, fuld nyeste anmeldelse og fælles Gutenberg-forhåndsvisning.
* Responsivt billede og forfatterlink til Maltex Gruppen.
