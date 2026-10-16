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

## GitHub-opdateringer

Plugin Update Checker **5.6** er inkluderet i `lib/plugin-update-checker`
(MIT; uændret upstream-kode fra tag `v5.6` hos
[YahnisElsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker)).
WordPress kontrollerer normalt opdateringer cirka hver 12. time. Kun den seneste
publicerede, stabile GitHub Release med filen **`hindbaersnitten-wpplugin.zip`** bruges.
Tags, prereleases og almindelige pushes tilbydes ikke som opdateringer.
Der kræves ingen GitHub-token på WordPress-serveren for dette offentlige repository.
Serveren skal kunne kontakte `api.github.com`, `github.com`, `raw.githubusercontent.com`
og GitHubs download-domæner over HTTPS. GitHubs grænser for anonyme API-kald gælder.

### Første installation og automatiske opdateringer

Installer ZIP-filen fra GitHub Releases manuelt første gang, og erstat den gamle
version gennem WordPress. Bevar mappen `hindbaersnitten-wpplugin`.
Aktivér derefter automatiske opdateringer for pluginet under Plugins.
WordPress styrer, om og hvornår opdateringen installeres; updateren ændrer ikke
administratorens valg. WP-Cron og skriveadgang til installationsmappen skal virke.

Filer i installationsmappen erstattes ved opdatering. Gem egne kodeændringer
uden for pakken. Tag backup før første opdatering.

### Udgiv en ny version

1. Opdatér `Version` i `hindbaersnitte-reviews.php` og `HSNITTE_PLUGIN_VER` i samme fil.
2. Commit og push ændringerne. Workflowet kontrollerer PHP-syntaks og bygger ZIP på branches og pull requests.
3. Opret et tilsvarende stabilt tag, fx `git tag v1.0.3`, og push det med `git push origin v1.0.3`.
4. Workflowet afviser uoverensstemmelse mellem tag og version, bygger ZIP,
   uploader den til en draft release og publicerer derefter releasen.

Workflowet bruger repositoryets `GITHUB_TOKEN` med `contents: write` til release-jobbet.
GitHub Actions skal være aktiveret. Installér den navngivne ZIP, ikke GitHubs
“Source code”-arkiv. ZIP-filen indeholder ikke tests, workflows, backups eller `desktop.ini`.

Byg lokalt: `python3 scripts/build-release.py --tag v1.0.3`.
Output er `dist/hindbaersnitten-wpplugin.zip` (ikke committed).

### Kontrol før første udgivelse

Test på en skrivbar WordPress-testinstallation med en ældre updater-version:
kontrollér opdateringsbesked, manuel opdatering og derefter en ny udgave via
WordPress' automatiske opdateringer. Kontrollér versionsnummer, aktivering og
bevarede indstillinger. Test begge komponenter aktive samtidigt.
Det lokale cloud-miljø har skrivebeskyttede repository-mounts og kan derfor
ikke bruges til selve filudskiftningen uden separate skrivbare testkopier.

Integrationstest (med begge komponenter aktive):
`php tests/github-updates.php /path/to/wordpress/wp-load.php`.
Testen simulerer GitHub-svar og kontrollerer stabile releases, drafts,
prereleases, manglende eller forkerte ZIP-filer og manglende releases.

## Ændringer i 1.0.3

- Klikbare kolonneoverskrifter til sortering af den viste liste efter overskrift, bageri/sted, karakter og dato.
- Nyeste anmeldelse hentes som fuld anmeldelse med karakter, tekst og eventuelt billede.
- Fælles, responsiv detaljevisning og CSS, også når nyeste står alene på forsiden.
- Gutenberg-forhåndsvisning af enkelt og nyeste anmeldelse bruger samme rendering som den publicerede side.
- Kort brugsvejledning til shortcodes og Gutenberg samt forfatterlink til Maltex Gruppen.

Test af fuld anmeldelse i WordPress:
`php tests/full-review.php /path/to/wordpress/wp-load.php`.
