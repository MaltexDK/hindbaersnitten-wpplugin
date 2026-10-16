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
- `[snitter_latest]`: nyeste fulde anmeldelse med karakter, tekst og eventuelt billede. Passer til indholdets bredde på fx forsiden.

Kolonneoverskrifterne sorterer de anmeldelser, der er indlæst i den viste liste.
Klik igen for at vende rækkefølgen. Karakteren går fra 0 til 6 krymmel.

### Gutenberg-blokke

Tryk **+** i blokeditoren og søg efter **Snitter**:

- **Snitter – Liste**: vælg startsortering, retning, antal og søgning i højre sidepanel.
- **Snitter – Anmeldelse**: angiv anmeldelsens ID i sidepanelet.
- **Snitter – Nyeste**: indsæt blokken direkte, fx på forsiden. Den viser automatisk den nyeste fulde anmeldelse.

Blokkene er dynamiske; de henter API-data ved visning og behøver ikke gemmes igen, når der udgives en anmeldelse. API-svar caches kortvarigt (ca. 1–2 minutter).

## Ændringer i 1.0.4

- Datoer sorteres kronologisk med numeriske tidsstempler, også ved danske datoformater. Manglende og ugyldige datoer vises uden dato og sorteres sidst.
- Brugsvejledningen findes nu også i WordPress’ pluginoplysninger.
- GitHub- og udviklerinstruktioner er fjernet fra denne vejledning.
