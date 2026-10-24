# SEO-Grundlage, robots.txt & Code-Audit

> **Historischer Bericht (Theme 1.x).** Seit 3.0 ist das Theme ein Block-Theme: Die hier
> genannten PHP-Vorlagen (`page.php`, `front-page.php`, `404.php`, `index.php`) sind durch
> `templates/*.html` ersetzt. Aktueller Stand: `BLOCKS-PATTERNS.md`, `SYSTEM-REPORT.md`.

Ergänzung zum Theme **Fahrschule edi**. Dieses Dokument beschreibt (1) die
gefundenen und behobenen Code-Probleme, (2) das neue SEO-Fundament und
(3) die robots.txt-Strategie.

---

## Teil 1 – Code-Audit: gefundene Probleme & Fixes

Geprüft wurde automatisiert auf: ungenutzte PHP-Funktionen, ungenutzte
Icons/CSS-Klassen, verwaiste JS-Hooks, fehlende Render-Callbacks, Klammer-/
Syntaxbalance, Escaping und doppelte Funktionsnamen.

### Echte Bugs (Funktionsfehler)

| # | Problem | Auswirkung | Fix |
|---|---|---|---|
| 1 | **TikTok-Icon: SVG-Pfad beschädigt** (`-.04-.1z` statt `-.96-.1z`) | Icon wurde verzerrt gezeichnet | Pfad auf Originalwert korrigiert |
| 2 | **Scroll-Animation war wirkungslos** – der `IntersectionObserver` setzte `animationPlayState = 'running'`, obwohl die Animation nie pausiert war | Toter Code; Elemente erschienen ohne Reveal-Effekt | CSS auf `.fs-fade-up.is-visible` umgestellt, JS setzt die Klasse; Fallback für alte Browser ergänzt |
| 3 | **Style-Handle im Editor nicht registriert** – `fahrschule-main` wurde nur per `wp_enqueue_scripts` (Frontend) geladen, aber in `register_block_type()` als `style`/`editor_style` referenziert | Block-Styles fehlten im Block-Editor, potenzielle WP-Notice | Assets werden jetzt auf `init` **registriert** und kontextabhängig enqueued |
| 4 | **`theme.json` v3 vs. `Requires at least: 6.4`** | theme.json v3 benötigt WP 6.6 – auf 6.4/6.5 wären Einstellungen ignoriert worden | Header auf `Requires at least: 6.6` korrigiert |

### Toter Code (entfernt)

| Art | Gefunden | Maßnahme |
|---|---|---|
| Icons | `check`, `message` – nie verwendet | entfernt |
| CSS-Klassen | `.fs-footer--dark`, `.fs-input`, `.fs-locglass`, `.fs-lochero__deco`, `.fs-mt-2`, `.fs-narrow` | entfernt |
| Duplizierte Logik | **13×** identische `str_replace`-Konstruktion zum Hervorheben eines Wortes | durch **eine** Funktion `fs_highlight()` ersetzt |
| Markup-Klasse | `fs-site-main` (ohne CSS-Definition) | aus dem Markup entfernt |
| Helfer | `fs_img()` (im Verlauf der Überarbeitung überflüssig geworden) | entfernt |

### Qualitäts- und Sicherheitsverbesserungen

- **`innerHTML` vollständig eliminiert** – der Instagram-Slider baut seine Karten
  jetzt über DOM-Methoden mit `textContent`. Damit ist der Weg für XSS
  geschlossen, falls die Beitragsdaten später aus einer echten API kommen.
- **`fs_highlight()` escaped zuerst und sucht dann** im escapten Text. Vorher
  hätte ein Highlight-Wort mit `&`, `"` oder `'` nicht mehr gematcht.
- **Instagram-Beiträge werden serverseitig vorgerendert** (die ersten drei).
  Vorher war der Bereich im HTML leer – für Suchmaschinen unsichtbar und ohne
  JavaScript funktionslos.
- **Barrierefreiheit:** Skip-Link, `aria-expanded`/`aria-controls` an Menü und
  FAQ (inkl. JS-Synchronisierung), FAQ-Fragen als `<h3>` mit Button,
  Escape schließt das Menü, sichtbarer Fokus-Ring (`:focus-visible`).
- **Performance / Core Web Vitals:** alle Bilder mit `width`/`height` gegen
  Layout-Shift (CLS), `loading="lazy"` + `decoding="async"`, Logo mit
  `fetchpriority="high"`, Videos mit `preload`-Steuerung, `preconnect` zum
  Medien-Host.
- **`<noscript>`-Fallback**, damit animierte Inhalte ohne JavaScript sichtbar
  bleiben (wichtig für Crawler und Barrierefreiheit).
- **FAQ-Block und FAQ-Schema teilen sich eine Datenquelle**
  (`fahrschule_faq_data()`), damit sichtbarer Text und strukturierte Daten nicht
  auseinanderlaufen können – das wäre sonst ein Verstoß gegen Googles Richtlinien.

### Prüfergebnis nach den Fixes

```
PHP-Funktionen:      65 definiert – ungenutzt: keine
Icons:               23 definiert – ungenutzt: keine
CSS-Klassen:        154 definiert – ungenutzt: keine
JS-Hooks:            beidseitig vollständig (kein verwaistes data-Attribut)
Render-Callbacks:    31 referenziert – fehlend: keine
Syntax/Balance:      18 PHP-Dateien OK · 2 JS-Dateien OK (node --check) · theme.json OK
Doppelte Funktionen: keine
```

> **Einschränkung:** In dieser Umgebung stand keine PHP-Laufzeit und keine
> WordPress-Installation zur Verfügung. Geprüft wurde daher statisch
> (Syntaxbalance, Referenzen, Escaping, Konsistenz). Ein Funktionstest in einer
> echten WordPress-Instanz – idealerweise mit `WP_DEBUG = true` – bleibt der
> nächste sinnvolle Schritt.

---

## Teil 2 – SEO-Grundlage (`inc/seo.php`)

### Zentrale Stammdaten

Alle Geschäftsdaten (Name, Adressen, Telefonnummern, Öffnungszeiten, Koordinaten,
Social-Profile) liegen in **einer** Funktion `fahrschule_business_data()`.
NAP-Konsistenz (Name/Address/Phone) ist ein Local-SEO-Rankingfaktor – hier gibt
es genau eine Pflegestelle, erweiterbar über den Filter `fahrschule_business_data`.

### Was ausgeliefert wird

| Bereich | Umsetzung |
|---|---|
| **Meta-Description** | Automatisch aus Excerpt/Inhalt, mit kuratierten Texten je Kernseite; sauber auf ~160 Zeichen gekürzt (ohne Wörter zu zerschneiden) |
| **Titel** | `document_title_parts`-Filter, Startseite mit Standort-Zusatz, Trennzeichen `\|` |
| **Canonical** | Auf allen Seiten außer 404/Suche – verhindert Duplicate Content |
| **Open Graph** | `og:type/title/description/url/image/locale/site_name` + `image:alt` |
| **Twitter/X** | `summary_large_image` mit Titel, Beschreibung, Bild |
| **Geo-Meta** | `geo.region`, `geo.placename`, `geo.position`, `ICBM` auf den drei Standortseiten |
| **Robots-Meta** | `max-image-preview:large`, `max-snippet:-1`, `max-video-preview:-1`; `noindex` für Suche, 404 und Anhänge |
| **Sitemap** | WordPress-Kernsitemap, Anhänge ausgeschlossen |
| **Aufräumen** | `wp_generator`, RSD/WLW-Links, Shortlinks und Emoji-Skripte entfernt |
| **Weiterleitung** | Anhang-Seiten leiten per 301 auf die Datei (keine dünnen Indexseiten) |

### Strukturierte Daten (JSON-LD, `@graph`)

- **`DrivingSchool` + `LocalBusiness`** – Hauptorganisation mit Rechtsform,
  USt-ID, Gründungsjahr, Social-Profilen, `areaServed` (Duisburg, Essen,
  Gelsenkirchen, Ruhrgebiet) und Öffnungszeiten
- **Drei eigenständige `LocalBusiness`-Einträge** – je Standort mit eigener
  Adresse, Telefonnummer, E-Mail und Geokoordinaten, verknüpft über
  `parentOrganization`
- **`OfferCatalog`** – alle 13 Führerscheinklassen und Zusatzangebote
  (inkl. ASF/FES und Berufskraftfahrer) als `Service`-Angebote
- **`WebSite` + `SearchAction`** – ermöglicht die Sitelinks-Suchbox
- **`BreadcrumbList`** – auf allen Unterseiten
- **`FAQPage`** – auf der Startseite, gespeist aus derselben Datenquelle wie der
  sichtbare FAQ-Block

### Plugin-Verträglichkeit

Wird **Yoast, Rank Math, SEOPress oder AIOSEO** aktiviert, erkennt das Theme das
und stellt Meta-Tags, Open Graph und JSON-LD automatisch ein. So entstehen keine
doppelten Angaben. Die Performance-Hints und die robots.txt-Logik bleiben aktiv.

### Nach dem Deployment noch zu tun

1. **Domain prüfen:** Die Sitemap-Zeile in der physischen `robots.txt` (falls
   verwendet) auf die echte Domain anpassen.
2. **Google Search Console & Bing Webmaster Tools** einrichten, Sitemap
   (`/wp-sitemap.xml`) einreichen.
3. **Google Business Profile** für alle drei Standorte anlegen/pflegen – für eine
   lokale Fahrschule ist das der stärkste Sichtbarkeitshebel; die Daten dort
   müssen exakt zu `fahrschule_business_data()` passen.
4. **Beitragsbilder** (1200×630 px) für Über uns, Testimonials und die
   Standortseiten hinterlegen – sie werden automatisch für Open Graph genutzt.
5. **Rich Results Test** (`search.google.com/test/rich-results`) und
   **Schema-Validator** (`validator.schema.org`) auf den Live-URLs laufen lassen.
6. Koordinaten der Standorte gegenprüfen – aktuell sind es Näherungswerte auf
   Stadtebene.

---

## Teil 3 – robots.txt

### Wichtiger Hinweis zur Zielsetzung

„Bots aussperren" und „SEO-Sichtbarkeit" stehen im direkten Zielkonflikt:
Wer **alle** Bots sperrt, verschwindet aus Google. Die umgesetzte Lösung trennt
deshalb sauber:

| Gruppe | Behandlung | Begründung |
|---|---|---|
| **Suchmaschinen** (Google, Bing, DuckDuckGo …) | **erlaubt** | Grundlage der Sichtbarkeit |
| **KI-Trainings-Crawler** (GPTBot, CCBot, ClaudeBot, Google-Extended, PerplexityBot, Bytespider, Meta-ExternalAgent u. a. – 20 Stück) | **gesperrt** | Inhalte sollen nicht ins Modelltraining |
| **Aggressive SEO-/Scraping-Bots** (AhrefsBot, SemrushBot, MJ12bot, DotBot, PetalBot u. a. – 10 Stück) | **gesperrt** | Serverlast ohne Gegenwert |
| **Interne Pfade** (`/wp-admin/`, `wp-login.php`, `xmlrpc.php`, `/wp-json/`, Suchergebnisse) | **gesperrt** | keine Indexierung von Systemseiten; `admin-ajax.php` bleibt erlaubt |

CSS und JS bleiben ausdrücklich crawlbar – Google benötigt sie zum Rendern der
Seite; ein Ausschluss würde der Bewertung schaden.

### Zwei Wege zur Auslieferung

**Standard (empfohlen):** Das Theme befüllt die von WordPress ausgelieferte
virtuelle `/robots.txt` über den Filter `robots_txt`. Es muss **nichts** kopiert
werden – nach Aktivierung des Themes ist die Datei unter
`https://ihre-domain.de/robots.txt` erreichbar. Die Sitemap-URL wird automatisch
aus der WordPress-Adresse erzeugt.

**Optional:** Die mitgelieferte Datei `robots.txt` ist eine Kopiervorlage für das
Web-Root, falls eine physische Datei nötig ist. Sie überschreibt dann die
dynamische Ausgabe; die Sitemap-Zeile muss manuell angepasst werden.

### Komplettsperre (Staging/Test)

In `wp-config.php`:

```php
define( 'FAHRSCHULE_BLOCK_ALL_BOTS', true );
```

Ergibt `User-agent: * / Disallow: /`. Dasselbe passiert automatisch, wenn unter
*Einstellungen → Lesen* die Sichtbarkeit für Suchmaschinen deaktiviert ist.

> **Zur Einordnung:** `robots.txt` verhindert das *Crawlen*, nicht garantiert die
> *Indexierung* – verlinkte URLs können ohne Inhalt trotzdem in den Ergebnissen
> auftauchen, und unseriöse Bots ignorieren die Datei ohnehin. Für echte
> Abschottung einer Testumgebung ist HTTP-Basic-Auth das richtige Mittel.

---

## Teil 4 – Laufzeit-Nachprüfung (Folgeauftrag)

Der vorige Audit lief ohne PHP-Laufzeit (siehe Einschränkung in Teil 1) – dieser
Durchlauf hatte eine zur Verfügung und hat `page.php` für **alle 20 Seiten der
Registry** sowie `front-page.php`, `404.php` und `index.php` tatsächlich
ausgeführt (inkl. `render_block()` mit den echten deklarierten
Attribut-Defaults, nicht nur den Render-Callbacks isoliert). Ergebnis: 23 von
23 Durchläufen fehlerfrei, keine PHP-Fehler oder Warnings.

Dabei UND bei einer zusätzlichen automatisierten Feld-für-Feld-Prüfung
(blocks-config.php-Default gegen den tatsächlich in render-callbacks.php
verwendeten Default) kam zusätzlich zu Tage, was ein rein statischer Check
nicht sieht – benutzte vs. unbenutzte Attribute, nicht nur referenzierte vs.
fehlende Funktionen:

| # | Problem | Auswirkung | Fix |
|---|---|---|---|
| 5 | `404.php`: drei Links noch auf Onepage-Anker (`/#services`, `/#locations`, `/#contact`) | Wären ins Leere gelaufen – genau der Fall, den Abschnitt „Links & Erreichbarkeit" oben als behoben auswies | Auf echte Registry-Seiten umgestellt: `/fuehrerscheinklassen/`, `/standorte/`, `/kontakt/` |
| 6 | `404.php`: `color:var(--fs-maroon)` – diese Variable existiert nirgends im Stylesheet | Überschrift auf der Fehlerseite ohne definierte Farbe (Browser-Default statt Marke); zusätzlich ein Verstoß gegen die eigene Regel „keine Farbwerte direkt im PHP-Markup" (Abschnitt 4, FARBSYSTEM.md) | Inline-Style entfernt – die bestehende Regel `.fs-head h1 { color: var(--c-heading); }` greift automatisch korrekt für die Fläche |
| 7 | `blocks-config.php`: `video-hero`/`hero` (`btn1_url`/`btn2_url`) und `anmeldung` (`btn_url`) hatten dort andere Editor-Attribut-Defaults (`#calculator`, `#contact`, `#`) hinterlegt als die längst korrigierten Fallbacks in den zugehörigen Render-Funktionen (`@anmeldung`, `@klassen`, `@kontakt`) | Solange niemand den Block im Editor anfasst, rendert die Seite über den (korrekten) Funktions-Fallback. Sobald jemand den Block das erste Mal im Editor öffnet und speichert, schreibt Gutenberg die deklarierten Attribut-Defaults fest – ab dann hätten die Buttons auf tote Onepage-Anker gezeigt | `blocks-config.php`-Defaults auf dieselben Werte wie die Render-Funktionen gebracht – beide Quellen zeigen jetzt auf dieselben, echten Unterseiten |
| 8 | `location-hero`: Feld `grad` („Hintergrund: Navy / Verlauf") wird von `fs_render_location_hero()` gar nicht gelesen | Toter Editor-Regler ohne jede Wirkung – und selbst wirksam umgesetzt hätte er der eigenen „keine Farbverläufe"-Vorgabe (FARBSYSTEM.md Abschnitt 4) widersprochen | Feld ersatzlos entfernt (`blocks-config.php`, `render-callbacks.php`, alle drei Standort-Einträge in `inc/pages.php`) |
| 9 | `theme.json`: `customGradient: true` erlaubte in jedem gradientfähigen Kernblock (Buttons, Cover, Separator …) einen frei wählbaren Verlauf über den Editor-Farbwähler | Widerspruch zur dokumentierten, WCAG-geprüften „0 Farbverläufe"-Vorgabe – nicht durch Prozess, sondern nur dadurch verhindert, dass niemand den Regler benutzt | Auf `false` gesetzt – die Vorgabe ist jetzt auch im Editor selbst durchgesetzt, nicht nur eingehalten |
| 10 | `page.php`: Loop ohne `post_class()` auf dem Artikel-Element | Kein technischer Fehler, aber ein fehlender Standard-Hook (Theme-Detection, CSS-/JS-Targeting nach Post-Typ) | `<article id="post-…" <?php post_class(); ?>>` um den Seiteninhalt ergänzt |

Geprüft mit einer eigenen, nicht mitgelieferten Skript-Kombination (Feld-für-
Feld-Abgleich `blocks-config.php` ↔ `render-callbacks.php` + Ausführung jeder
Seite mit vollständigen WordPress-Attrappen). Ein zweiter automatisierter
Durchlauf danach: keine weiteren Abweichungen.
