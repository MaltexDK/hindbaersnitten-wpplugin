# hindbaersnitten-wpplugin
WP-plugin til visning af hindbærsnitteanmeldelser

## Brug pluginet

Udviklet af [Maltex Gruppen](https://www.maltex.dk).
Sæt først API-base-URL under **Indstillinger → Hindbærsnitte API** (uden afsluttende `/`).

### Shortcodes

Indsæt en **Shortcode**-blok på en side eller et indlæg:

- `[snitter_reviews]`: liste med klikbare overskrifter til sortering efter overskrift, bageri/sted, karakter og dato. Klik på en anmeldelse for at åbne hele anmeldelsen i samme popup som Nyeste-kortene.
- `[snitter_reviews limit="20" sort="rating" dir="desc" q="København"]`: begræns antal, vælg startsortering og søgning. `sort` kan være `date`, `rating`, `title` eller `author`; `dir` er `asc` eller `desc`. `page` vælger API-side, og `per_page` kan bruges i stedet for `limit`.
- `[snitter_reviewers]`: liste over anmeldere med udgivne anmeldelser. Fold et navn ud for at se en sorterbar anmeldelsesliste; klik på en anmeldelse for at åbne popup’en. Flere end 100 anmeldelser hentes med **Vis flere anmeldelser**.
- `[snitter_review id="42"]`: én fuld anmeldelse med det angivne ID.
- `[snitter_latest]`: de 1–3 nyeste anmeldelser som klikbare kort med overskrift, bageri/sted, karakter, tekstuddrag og eventuelt thumbnail. Klik for at åbne hele anmeldelsen i en popup.

Kolonneoverskrifterne sorterer de anmeldelser, der er indlæst i den viste liste.
Klik igen for at vende rækkefølgen. Karakteren går fra 0 til 6 krymmel.

### Gutenberg-blokke

Tryk **+** i blokeditoren og søg efter **Snitter**:

- **Snitter – Liste**: vælg startsortering, retning, antal og søgning i højre sidepanel.
- **Snitter – Anmeldere**: indsæt blokken direkte for at vise anmeldere med udfoldelige anmeldelseslister og antal udgivne anmeldelser. Anmelderens valgfrie præsentation vises ved udfoldning før anmeldelseslisten. Understøtter bred og fuld bredde.
- **Snitter – Anmeldelse**: angiv anmeldelsens ID i sidepanelet.
- **Snitter – Nyeste**: indsæt blokken direkte, fx på forsiden. Den viser automatisk 1–3 anmeldelseskort afhængigt af blokkens bredde. Vælg eventuelt bred eller fuld bredde i blokkens værktøjslinje.

**Nyeste-kortene** viser ét kort i smalle områder, to når der er mindst ca. 576 px,
og tre fra ca. 896 px. Det gælder også i sidekolonner på store skærme.
Med shortcode kan du placere blokken i en bred Gruppe for at give plads til flere kort.
Der vises højst tre anmeldelser i én række; de nyeste vises først.

Popup'en viser hele anmeldelsen og eventuelt billede. Luk med **Luk**, **Escape**
eller et klik udenfor boksen. Fokus vender tilbage til kortet eller linket i listen.
Uden JavaScript åbnes hele anmeldelsen direkte under kortet.
`[snitter_review id="42"]` viser fortsat en fuld anmeldelse direkte på siden.

Anmeldelsesteksten bevarer editorens HTML-formatering og links. Den renses med WordPress’ almindelige regler for indlægs-HTML, så usikre scripts og attributter fjernes. Alle fulde visninger bruger samme renderer. Efter teksten og før billedet vises **Anmeldt af [handle] den [dato]** med en afdæmpet farve. Datoen følger WordPress’ datoformat.

Anmelderlisten viser kun handles med udgivne anmeldelser, aldrig kontooplysninger. Den kræver anmeldersystemets API med `GET /reviewers` og `GET /reviews?author_id=ID`. Upload det opdaterede `api/index.php` til anmeldersystemet før brug. Anmeldelser hentes ved udfoldning og bliver liggende, når listen lukkes igen. Sortering gælder indlæste anmeldelser. JavaScript kræves til indlæsning og popup.

Blokkene er dynamiske; de henter API-data ved visning og behøver ikke gemmes igen, når der udgives en anmeldelse. API-svar caches kortvarigt (ca. 1–2 minutter).

## Ændringer i 1.3.0

- Anmelderens valgfrie, formaterede præsentation vises før anmeldelseslisten ved udfoldning.
- Tomme præsentationer udelades. Opdatér først anmeldersystemets database og kode for at redigere præsentationer.

## Ændringer i 1.2.0

- Formateret HTML og links i alle fulde anmeldelsesvisninger.
- Anmelderens handle og dato efter teksten og før billedet.
- Ny blok **Snitter – Anmeldere** og shortcode `[snitter_reviewers]` med udfoldelige, sorterbare lister og fælles popup.
- Fælles PHP-renderer sikrer ens formatering og HTML-rensning.

## Ændringer i 1.1.1

- Listevisningen åbner nu anmeldelser i samme popup som Nyeste-kortene.
- Sortering bevares, og fokus vender tilbage til anmeldelsens link ved lukning.

## Ændringer i 1.1.0

- Nyeste-visningen er nu 1–3 responsive anmeldelseskort med tekstuddrag og valgfrit thumbnail.
- Hele anmeldelsen åbnes i en popup med tastaturbetjening og fokusretur.
- Bred og fuld bredde understøttes i Gutenberg.
- Visningen fungerer også uden JavaScript med udfoldning direkte på siden.

## Ændringer i 1.0.4

- Datoer sorteres kronologisk med numeriske tidsstempler, også ved danske datoformater. Manglende og ugyldige datoer vises uden dato og sorteres sidst.
- Brugsvejledningen findes nu også i WordPress’ pluginoplysninger.
- GitHub- og udviklerinstruktioner er fjernet fra denne vejledning.
