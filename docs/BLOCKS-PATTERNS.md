# Fahrschule edi – Blöcke & Patterns (Dokumentation)

> Ergänzende Dokumentation:
> **FARBSYSTEM.md** (Farb-Tokens, Kontraste, Medien) · **SEO-UND-AUDIT.md** (SEO, robots.txt, Code-Audit)

Migration des React/Next.js-Prototyps in ein WordPress-Theme mit **zentral
registrierten Gutenberg-Blöcken & Block-Patterns**.

- **Theme-Ordner:** `fahrschule-edi/`
- **Blöcke gesamt:** 34 · **Patterns gesamt:** 34 (jeder Block ist zugleich ein Pattern)
- **Unterseiten:** 20 (echte URLs, keine Onepage-Sektionen)
- **Block-Namespace:** `fahrschule/…` · **Pattern-Namespace:** `fahrschule-pattern/…`
- **Eigene Kategorie:** „Fahrschule edi“ (Blöcke **und** Patterns)

---

## 1. Farbkorrektur (Briefing-Punkt 2)

Der pinke **Hintergrundton** wurde global angepasst:

| Vorher (React) | Nachher (WordPress) | Verwendung |
|---|---|---|
| `#fce4ed` | **`#d12487`** | Seiten-/Sektionshintergründe, Karten-Overlays, Verläufe |

Gesteuert über eine einzige CSS-Variable in `assets/css/main.css`:
`--fs-pink-bg: #d12487;` sowie über `theme.json` (`styles.color.background` und die
Palette-Farbe „Pink Hintergrund“). Die Marken-/Akzentfarbe `#e62775` bleibt unverändert.

---

## 2. Architektur: zentrale Registrierung (Briefing-Punkt 3)

Es gibt **keine Einzelverzeichnisse pro Block/Pattern**. Stattdessen:

1. `inc/blocks-config.php` – **ein großes, strukturiertes Array** (`fahrschule_blocks_config()`),
   das jeden Block samt Titel, Icon, Keywords, editierbaren Feldern und Render-Callback beschreibt.
2. `functions.php` → `fahrschule_register_blocks_and_patterns()` – **eine einzige Funktion**
   mit **einer `foreach`-Schleife**, die pro Eintrag
   `register_block_type()` **und** `register_block_pattern()` aufruft.
3. `inc/render-callbacks.php` – die serverseitigen Render-Funktionen (dynamische Blöcke).
4. `assets/js/editor.js` – **eine** generische Datei, die die Editor-Oberfläche **aller** Blöcke
   registriert (Live-Vorschau via `ServerSideRender`, Textfelder via `InspectorControls`).

Vorteil: schlank, wartbar, keine Datei-Flut – neuer Block = ein Array-Eintrag.

---

## 3. Übersicht aller Blöcke & Patterns

Jede Zeile ist als **Block** (editierbar) **und** als **Pattern** (vordefiniertes Layout) verfügbar.
„Quelle“ = ursprüngliche React-Komponente bzw. **PDF** = neuer, aus dem PDF abgeleiteter Custom-Block.

### Global

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 1 | `navigation` | Navigation | `navigation.tsx` | Logo-URL |
| 2 | `footer` | Footer | `page.tsx` (Footer) | Logo-URL |
| 3 | `tiktok-badge` | TikTok Badge | `tiktok-floating-badge.tsx` | URL, Zeile 1, Zeile 2 |

### Startseite

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 4 | `video-hero` | Video Hero | `video-hero-section.tsx` | Badge, Überschrift, Highlight, Text, 2× Button (Text/Link), Video-URL |
| 5 | `hero` | Hero (hell) | `hero-section.tsx` | Badge, Überschrift, Highlight, Text, 2× Button (Text/Link) |
| 6 | `license-classes` | Führerscheinklassen (Karten) | `license-classes-section.tsx` | Eyebrow, Überschrift, Untertitel |
| 7 | `calculator` | Führerschein-Rechner | `calculator-section.tsx` | Eyebrow, Überschrift, Untertitel |
| 8 | `locations` | Standorte (Übersicht) | `locations-section.tsx` + **PDF-Adressen** | Eyebrow, Überschrift, Untertitel |
| 9 | `team-spirit` | Team Spirit | `team-spirit-section.tsx` | Badge, Überschrift, Highlight, Text, Zitat, Zitat-Autor, Video-URL |
| 10 | `fleet-facilities` | Flotte & Einrichtungen | `fleet-facilities-section.tsx` | — (Layout-Block) |
| 11 | `testimonials` | Testimonials | `testimonial-section.tsx` | Eyebrow, Überschrift, Untertitel |
| 12 | `instagram-slider` | Instagram Slider | `instagram-slider-section.tsx` | Überschrift, Highlight, Untertitel, Instagram-URL |
| 13 | `faq` | FAQ | `faq-section.tsx` | Eyebrow, Überschrift |
| 14 | `cta` | CTA / Kontakt | `cta-section.tsx` + **PDF-Kontakt** | Überschrift, Highlight, Lead-Text, Telefon, E-Mail |

### Über uns

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 15 | `about-intro` | Über uns – Intro | **PDF** „Ihre Fahrschule im Ruhrgebiet“ | Eyebrow, Überschrift, Highlight, Absatz 1–3 |
| 16 | `about-timeline` | Über uns – Timeline | `uber-uns/page.tsx` | Überschrift, Highlight, Untertitel |
| 17 | `about-philosophy` | Über uns – Philosophie | `uber-uns/page.tsx` | Überschrift, Highlight, Untertitel |
| 18 | `about-team` | Über uns – Team | `uber-uns/page.tsx` + **PDF** „Unser Team“ | Überschrift, Highlight, Untertitel |

### Testimonials-Seite

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 19 | `testimonials-hero` | Testimonials – Hero | `testimonials/page.tsx` | Überschrift, Highlight, Untertitel |
| 20 | `graduates-gallery` | Absolventen-Galerie | `testimonials/page.tsx` | Überschrift, Highlight |
| 21 | `testimonials-detailed` | Testimonials – Detailraster | `testimonials/page.tsx` | Überschrift, Highlight |

### Standort-Detailseiten

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 22 | `location-hero` | Standort – Hero | `location-{duisburg,essen,gelsenkirchen}.tsx` | Stadt, Icon, Badge, Überschrift, Lead, Adresse, PLZ/Ort, Telefon, E-Mail, Hintergrund |
| 23 | `location-content` | Standort – Inhalt | `location-*.tsx` (Bilder, Distanz, Bewertungen) | Stadt, Bild 1+2 (URL/Titel/Untertitel) |
| 24 | `location-calculator` | Standort – Rechner | `location-calculator.tsx` | — (Layout-/Logik-Block) |

### PDF-Custom-Blöcke (Briefing-Punkt 4)

Inhalte des PDFs, die **keiner** bestehenden React-Komponente entsprachen → neue Blöcke:

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 25 | `license-overview` | Führerscheinklassen (Übersicht) | **PDF** S.2 (AM…DE komplett) | Eyebrow, Überschrift, Untertitel |
| 26 | `services` | Unsere Leistungen | **PDF** S.3 | Eyebrow, Überschrift, Untertitel, Abschlusstext |
| 27 | `professional-drivers` | Berufskraftfahrer | **PDF** S.3 *(neu)* | Eyebrow, Überschrift, Absatz 1–2, Abschlusstext |
| 28 | `seminars` | ASF & FES | **PDF** S.4 *(neu)* | Eyebrow, Überschrift, Untertitel, Abschlusstext |
| 29 | `modern-training` | Moderne Ausbildung | **PDF** S.4 *(neu)* | Eyebrow, Überschrift, Highlight, Absatz 1–3, Abschlusstext |
| 30 | `anmeldung` | Anmeldung (CTA) | **PDF** S.6 *(neu)* | Eyebrow, Überschrift, Absatz 1–3, Text vor dem Button, Button-Text, Button-Link, Abschnittskopf |

### Unterseiten-Architektur

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 32 | `page-hero` | Seitenkopf (Unterseite) | neu | Eyebrow, Überschrift, Highlight, Einleitung (leer = aus Registry) |
| 33 | `page-teasers` | Themen-Teaser | neu – Label, Überschrift und erster Satz der PDF-Abschnitte F3–F7, F9 | Eyebrow, Überschrift, Untertitel (optional, Standard leer) |
| 34 | `instructor-training` | Fahrlehrerausbildung | neu – Fahrlehrerfachschule | Eyebrow, Überschrift, Highlight, Absatz 1–2, Abschlusstext, Button |

### Rechtliches

| # | Block (`fahrschule/…`) | Titel | Quelle | Editierbare Felder |
|---|---|---|---|---|
| 31 | `legal` | Rechtstext (Impressum/Datenschutz) | `impressum/`, `datenschutz/` + echte Impressumsdaten | Inhalt (Auswahl Impressum/Datenschutz) |

> Hinweis zu „Layout-Blöcken“: Karten-/Listendaten (z. B. die 6 Klassenkarten, 6 FAQs,
> Bewertungen) sind als gepflegte Standardinhalte im Render-Callback hinterlegt. Editierbar
> sind Überschriften, Eyebrows, Lead-Texte und konfigurierbare Felder. Wiederkehrende
> Datensätze lassen sich zentral in `inc/render-callbacks.php` anpassen.

---

## 4. Eingepflegte PDF-Inhalte (Briefing-Punkt 4)

> **Stand Runde „PDF-Abschnitte":** Alle zehn Abschnitte stehen wortgleich in
> `inc/pdf-texts.php` (eine Quelle für Ausgabe und Editor-Standardwerte), jede
> Sektion trägt `data-pdf-section="F1"` … `"F10"`. Zuordnung zu Startseite und
> Unterseiten: `docs/SETUP.md`, Abschnitt „Kundentexte (PDF) als Abschnitte".
> PDF-Blöcke haben zusätzlich das Feld **„Abschnittskopf anzeigen"** (`head`) –
> auf ihrer Unterseite aus, weil Label und Überschrift dort im Seitenkopf stehen.

- **Hero-Text** der Startseite (Video-Hero).
- **Über uns / Ruhrgebiet** → Block `about-intro`.
- **Vollständige Führerscheinklassen** (Motorrad AM/A1/A2/A · PKW BF17/B/B197/BE · LKW C1/C1E/C/CE · Bus D/DE) → `license-overview`.
- **Echte Standortdaten** (Adresse, Telefon, E-Mail je Filiale) → `locations` & `location-hero`.
- **Unser Team** (Persönlich. Geduldig. Erfahren.) → `about-team`.
- **Anmeldung** → `anmeldung`.
- **Neue Themen** (im React-Prototyp nicht vorhanden): **Berufskraftfahrer**, **ASF & FES**,
  **Moderne Ausbildung** → eigene Custom-Blöcke.
- **Impressum** mit echten Registerdaten (HRB 7858, USt DE195505422, Aufsichtsbehörde Duisburg).

---

## 4a. Seitenstruktur (Unterseiten statt Onepage)

Die Website besteht aus **20 echten Unterseiten** mit eigenen URLs – nicht aus
Sektionen einer Landingpage. Zentrale Definition: `inc/pages.php`.

```
/                              Startseite (F1, F2, F8, F10 vollständig; Teaser F3–F7, F9)
/fuehrerscheinklassen/         Führerscheinklassen
/preise/                       Preise & Rechner
/leistungen/                   Leistungen
/moderne-ausbildung/           Moderne Ausbildung
/berufskraftfahrer/            Berufskraftfahrer
/fahrlehrerausbildung/         Fahrlehrerfachschule  (neu)
/asf-fes/                      ASF & FES
/standorte/                    Standort-Übersicht
   /standorte/duisburg/
   /standorte/essen/
   /standorte/gelsenkirchen/
/ueber-uns/                    Über uns
   /ueber-uns/team/            Team
   /ueber-uns/erfahrungen/     Erfahrungen & Bewertungen
/faq/                          Häufige Fragen
/kontakt/                      Kontakt
/anmeldung/                    Anmeldung
/impressum/  /datenschutz/     Rechtliches
```

**Navigation** (automatisch aus der Registry, mit Dropdowns):
Ausbildung · Beruf & Weiterbildung · Standorte · Über uns · FAQ · Kontakt

**Warum eine Registry?** Seiten, URLs, Navigation, Breadcrumbs und Block-Stapel
stammen aus *einer* Datenquelle. Interne Links entstehen ausschließlich über
`fs_url( 'schlüssel' )` – ein toter interner Link ist damit strukturell
ausgeschlossen. Ein Seitentitel oder Slug wird an genau einer Stelle geändert.

**Router statt Einzeltemplates:** `page.php` ermittelt die Seite über die
Registry und rendert deren Block-Stapel. Die sieben früheren `page-*.php`
entfallen. Wird im Editor eigener Inhalt gepflegt, hat dieser Vorrang.

---

## 5. Installation & Nutzung

1. Ordner `fahrschule-edi/` nach `wp-content/themes/` kopieren und Theme aktivieren.
2. **Permalinks** einmal speichern (Einstellungen → Permalinks).
3. Seiten anlegen (Titel → daraus ergibt sich der Slug):
   - **Startseite** (beliebiger Titel) → unter *Einstellungen → Lesen* als statische Startseite setzen.
     Sie nutzt automatisch `front-page.php`.
   - **Über uns** → Slug `uber-uns`
   - **Testimonials** → Slug `testimonials`
   - **Impressum** → Slug `impressum`
   - **Datenschutz** → Slug `datenschutz`
   - **Standorte** (Übersichtsseite, optional) → darunter je eine Seite mit Slug
     `duisburg`, `essen`, `gelsenkirchen`.
   Die passenden Templates greifen automatisch über den Slug. Alternativ lässt sich im
   Seiten-Editor rechts unter **Vorlage** das jeweilige Template manuell zuweisen
   (z. B. „Standort Duisburg“).
4. Alternativ **Block-für-Block im Editor**: Blöcke der Kategorie „Fahrschule edi“ einfügen
   oder die gleichnamigen **Patterns** als fertiges Layout übernehmen und Texte anpassen.

---

## 6. Nicht migriert (außerhalb des Projektumfangs)

Der Prototyp enthielt **v0-Template-Reste** einer fremden Felgen-/Powder-Coating-Vorlage
(englisch/niederländisch, ohne Verlinkung): `app/contact`, `app/diensten`, `app/offerte`,
`app/over-ons`, `app/projecten`. Diese gehören **nicht** zur Fahrschule-Website (anderes
Geschäft, nicht verlinkt) und wurden daher bewusst **nicht** übernommen.

---

## 7. Dateistruktur

```
fahrschule-edi/
├── style.css                  Theme-Header
├── theme.json                 Palette (inkl. #d12487), Typografie, Layout
├── functions.php              Setup, Assets, ZENTRALE Block-/Pattern-Registrierung
├── robots.txt                 Kopiervorlage fürs Web-Root (optional)
├── inc/
│   ├── helpers.php            Inline-SVG-Icons, fs_block(), fs_highlight()
│   ├── blocks-config.php      EIN Array: Definition aller Blöcke/Patterns
│   ├── render-callbacks.php   Serverseitige Render-Funktionen (dynamische Blöcke)
│   ├── pages.php              Seiten-Registry: URLs, Navigation, Breadcrumbs
│   ├── media.php              Medien-Registry: alle Bild-/Video-Quellen
│   └── seo.php                Meta/OG/JSON-LD, robots.txt, Stammdaten
├── assets/
│   ├── css/main.css           Komplettes Design (1:1), --fs-pink-bg: #d12487
│   └── js/
│       ├── editor.js          Generische Editor-Registrierung (ServerSideRender)
│       └── frontend.js        Navigation, Rechner, Slider, Akkordeon, Badge
└── header.php  footer.php  index.php  404.php
    front-page.php             Startseite (Teaser auf die Unterseiten)
    page.php                   ZENTRALER ROUTER für alle Unterseiten
```
