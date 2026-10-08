# System-Report – Fahrschule edi

Dieser Report ist der zentrale Einstiegspunkt für den aktuellen Ausbaustand des Projekts
(Theme `fahrschule-edi` + companion-plugin `edi-integrationen`, Repo
`bossmedia2/EDI_B2C_Fahrschule`). Er fasst zusammen, was fertig ist, was dabei optimiert
wurde und was vor dem Livegang noch offen ist. Für tiefere technische Details verweist
er auf die drei bestehenden Fachdokumente:

- **FARBSYSTEM.md** – vollständige Herleitung und Kontrastnachweise des Farbsystems
- **BLOCKS-PATTERNS.md** – jeder Block einzeln mit Feldern, Icons, Zielseite
- **SEO-UND-AUDIT.md** – der ursprüngliche Code-Audit und die SEO-Grundlagen im Detail

Stand: laufende Weiterentwicklung (Farbwelt-Umbau, Altseiten-Auswertung, Formulare/Odoo/
Matomo, siehe Abschnitt 1–3 unten).

---

## 1. Etablierte Komponenten & Features

### 1.1 Seitenstruktur (21 Seiten, klassische Unterseiten – keine Onepage)

Einzige Quelle: `inc/pages.php` → `fahrschule_page_map()`. Navigation, Breadcrumbs,
interne Links und die automatische Seitenanlage bei Theme-Aktivierung leiten sich
vollautomatisch aus dieser Registry ab.

| Gruppe | Seiten |
|---|---|
| Start | Startseite |
| Ausbildung | Führerscheinklassen, Preise & Rechner, Leistungen, Moderne Ausbildung, **Theorieunterricht** *(neu, s. 1.4)* |
| Beruf & Weiterbildung | Berufskraftfahrer, Fahrlehrerausbildung, ASF & FES |
| Standorte | Übersicht, Duisburg, Essen, Gelsenkirchen |
| Über uns | Übersicht, Team, Erfahrungen |
| Service | FAQ, Kontakt, Anmeldung |
| Rechtliches | Impressum, Datenschutz |

### 1.2 Blöcke & Patterns (37 registrierte Blöcke)

Zentral in `inc/blocks-config.php` beschrieben, per einer einzigen `foreach`-Schleife in
`functions.php` sowohl als Gutenberg-Block als auch als Block-Pattern registriert – volle
Editor-Kompatibilität (ServerSideRender-Vorschau, Inspector-Controls aus denselben
Feld-Definitionen). Vollständige Einzelaufstellung: **BLOCKS-PATTERNS.md**. Diese Runde neu
hinzugekommen:

- `fahrschule/contact-form` – echtes Kontakt-/Anmeldeformular (Name, E-Mail, Telefon,
  Standort, Nachricht, DSGVO-Checkbox), eingebunden auf `/kontakt/` und `/anmeldung/`.
- `fahrschule/theory-schedule` – wöchentliche Theorieunterricht-Termine je Standort-Gruppe.
- `fahrschule/cookie-banner` – DSGVO-Cookie-Hinweis (s. 1.7).

### 1.3 Formulare (diese Runde: von dekorativ auf echt umgestellt)

Vorher waren die „Schnelle Anmeldung"-Felder auf den drei Standort-Seiten reine
`<input>`-Elemente ohne `<form>`-Wrapper und ohne `name`-Attribute – jede Eingabe ging
verloren. Jetzt (`inc/forms.php`, neu):

- **Standort-Schnellanmeldung** (3× Duisburg/Essen/Gelsenkirchen, `fs_render_location_hero()`):
  echtes `<form>`, Nonce, Honeypot, Versand per `wp_mail()` an die jeweilige Standort-Adresse,
  Erfolgs-/Fehler-Rückmeldung über `?fs_schnell=ok|error`.
- **Allgemeines Kontakt-/Anmeldeformular** (`fahrschule/contact-form`, auf `/kontakt/` und
  `/anmeldung/`): dieselbe Sicherheitslogik, zusätzlich Pflicht-Checkbox zur
  Datenschutzerklärung (`fs_privacy`), Standort-Auswahl steuert die Zieladresse,
  Rückmeldung über `?fs_kontakt=ok|error`.
- Beide Handler rufen optional `fahrschule_odoo_send_lead()` auf (s. 1.4) – ohne das
  companion-plugin läuft der Versand unverändert nur per E-Mail weiter.

### 1.4 Companion-Plugin `edi-integrationen` (neu, diese Runde)

Eigenständiges Plugin (`/edi-integrationen/`, Repo-Root neben dem Theme), nach demselben
Prinzip wie beim Schwesterprojekt KAZ: Aussehen bleibt im Theme, Anbindungen an fremde
Systeme leben im Plugin, damit ein späterer Theme-Wechsel sie nicht stillschweigend abschaltet.

- **Odoo-Lead-Webhook** (`inc/odoo.php`): JSON-POST an eine hinterlegte Webhook-URL,
  optionaler Bearer-Token, fire-and-forget (blockiert nie den Formular-Versand). Ohne
  hinterlegte URL: no-op.
- **Matomo-Statistik** (`inc/matomo.php`): Einbindung nur bei hinterlegter URL + Site-ID.
  Standardmäßig „cookielos" (keine Tracking-Cookies, Do-Not-Track wird respektiert) plus
  Opt-out-Baustein (`[edi_matomo_optout]`, automatisch auf der Datenschutzseite eingebunden,
  sobald Matomo konfiguriert ist).
- **Einstellungsseite** (`inc/settings.php`): eigene Seite unter Einstellungen →
  edi-integrationen (das Theme hat noch keine eigene Options-Infrastruktur, siehe 3.3).

### 1.5 SEO-Grundlage (`inc/seo.php`, verifiziert – s. Abschnitt 2.2)

- Zentrale NAP-Stammdaten (Name/Adresse/Telefon) als Single Source of Truth.
- JSON-LD: Organization/DrivingSchool, 3× LocalBusiness (mit den jetzt realen
  Öffnungszeiten je Standort, s. 1.6), WebSite+SearchAction, BreadcrumbList, FAQPage.
- robots.txt (Filter, keine physische Datei nötig): Suchmaschinen erwünscht, KI-Trainings-
  und aggressive SEO-Crawler ausgesperrt, Sitemap-Verweis.
- Automatische Deaktivierung der eigenen Meta-/OG-/JSON-LD-Ausgabe, sobald Yoast SEO,
  Rank Math, SEOPress oder AIOSEO aktiv ist (keine Duplikate).
- Erweitertes strukturiertes Markup (diese Runde): Branche/Dienstleistung/Land/Bundesland
  waren bereits vollständig abgedeckt (`DrivingSchool`/`EducationalOrganization`,
  `hasOfferCatalog`, `addressCountry: DE`, `addressRegion: Nordrhein-Westfalen`) – zusätzlich
  ergänzt um `knowsLanguage` (inkl. Türkisch für Gelsenkirchen, s. 1.6),
  `hasCredential` (CERTQUA-Zertifizierung strukturiert statt nur als Bild), `currenciesAccepted`
  (EUR), `knowsAbout` (Themenfelder) und `Course`-Typisierung mit `provider`-Referenz für
  jede Führerscheinklasse statt des generischen `Service`.

### 1.6 Reale Inhalte aus der alten Website übernommen (diese Runde)

Gemäß Vorgabe „keinen PHP-/CSS-Code, nur Medien-Assets und reale Texte" aus
`Fahrschule_EDI_B2C.zip` extrahiert:

- 15 lokale Bild-Assets (Logo, Zertifizierungen, Fuhrpark, Räumlichkeiten, 2 Standortfotos) –
  siehe `assets/images/`, eingebunden über `inc/media.php`.
- Reale Öffnungszeiten je Standort (vorher: ein generischer, erfundener Zeitraum – inkl.
  einer frei erfundenen Samstagsöffnung – für alle drei Standorte gleich, an gleich drei
  unabhängigen Stellen im Code hinterlegt): Duisburg/Essen Mo–Do 13–20 Uhr & Fr 13–18 Uhr,
  Gelsenkirchen Mo–Mi 8–20 Uhr, Do 13–20 Uhr & Fr 13–18 Uhr – jetzt sichtbar auf den
  Standort-Seiten (Hero **und** Standort-Kachel-Übersicht) und korrekt im JSON-LD je
  Standort. Der Text stammt jetzt aus einer einzigen Funktion
  (`fahrschule_location_hours_text()` in `inc/helpers.php`), damit eine künftige Korrektur
  nicht wieder eine von mehreren Kopien übersieht.
- Reale Ansprechpartner vor Ort: Aslan Seymen (Gelsenkirchen), Betül Akar (Essen); für
  Duisburg war auf der alten Seite keine Person hinterlegt – dort bewusst keine Angabe
  statt einer erfundenen.
- Fehlende Unterseite **Theorieunterricht** ergänzt (auf der alten Website ein eigener
  Hauptnavigationspunkt mit wöchentlichen Terminen je Standort-Gruppe, inkl. türkisch-
  sprachigem Termin in Gelsenkirchen) – URL-Segment `/theorieunterricht/` unverändert
  übernommen, in der neuen Struktur unter „Ausbildung" einsortiert.
- DSGVO-Zustimmungs-Checkbox im Kontaktformular ergänzt (die alte Website hatte eine
  solche Checkbox; das neue Formular übernimmt dieselbe Absicherung).

### 1.7 Cookie-Consent-Fenster (neu, diese Runde)

`fahrschule/cookie-banner` (`fs_render_cookie_banner()`) – ein am ersten Besuch
eingeblendetes Fenster mit „Nur notwendige" und „Alle akzeptieren", Link zur
Datenschutzerklärung sowie einem dauerhaften „Cookie-Einstellungen"-Link im Footer zum
späteren Ändern der Wahl. Bewusst nur zwei Optionen statt einer Kategorie-Liste mit
Reglern, die für nicht vorhandene Kategorien nichts bewirken würden.

- Die Wahl landet in einem Cookie (`fs_consent`, 180 Tage), serverseitig gelesen –
  das Banner erscheint dadurch nach der Entscheidung nicht erneut, ohne dass JavaScript
  vor dem ersten Rendern etwas nachträglich ausblenden müsste.
- Steuert konkret: den nicht-cookielosen Matomo-Modus (`edi-integrationen`) – lädt nur
  nach „Alle akzeptieren". Der cookielose Modus (Standardeinstellung) bleibt bewusst
  ungegatet, siehe die rechtliche Begründung in `inc/matomo.php`.
- Sicherheitsgeprüft: ein manipulierter Cookie-Wert wird weder reflektiert noch
  fälschlich als „bereits entschieden" gewertet (`sanitize_key()` + strikte Whitelist
  gegen `all`/`essential`).

### 1.8 Kundentexte (PDF) als eigene Abschnitte (Runde „PDF-Abschnitte")

Kundenvorgabe: Die Inhalte aus „Texte_Webseite_Fahrschule" gelten als Abschnitte der
Seite und der Unterseiten, mit den neuen Texten.

- **Eine Quelle:** `inc/pdf-texts.php` hält alle zehn Abschnitte wortgleich; Render-
  Callbacks und Editor-Standardwerte (`inc/blocks-config.php`) lesen beide daraus
  (vorher getrennt gepflegt und teils umformuliert). Zuordnung: `docs/SETUP.md`.
- **Startseite:** F1 (alle vier Absätze), F2, Teaser F3–F7/F9 (Label, Überschrift,
  erster Satz – keine eigenen Teasertexte mehr), F8, F10 als Abschluss.
- **Unterseiten:** Seitenkopf = Label + Überschrift des Abschnitts (`pdf` in
  `inc/pages.php`), nachformulierte Einleitungen entfernt; Listen-Einleitungen und
  Abschlusssätze vollständig; F9 mit den drei Gruppen.
- **Sie-Form** in allen sichtbaren Texten (Team Spirit, Flotte, Instagram, CTA, TikTok,
  Rechner, Gelsenkirchen-Hero).
- **Darstellungsfehler mitbehoben:** Primär-Buttons in Karten auf pinker Fläche (weiß auf
  weiß), Überschrift der Standort-Schnellanmeldung (weiß auf weiß), Team-Karten (Pink auf
  Pink) – alles innerhalb der Pink-Palette, ohne neue Farbwerte.
- **Geprüft** mit einem Render-Harness über alle 21 Registry-Seiten + Startseite:
  217 PDF-Zeilen wortgleich auf den zugeordneten Seiten, keine du-Form, keine
  Überschriften-Sprünge, kein horizontaler Überlauf (Chromium, 390/768/1280 px).

---

## 2. Optimierungen

### 2.1 Farbwelt (Details: FARBSYSTEM.md)

Verbleibende Navy-/Dunkelblau-Flächen vollständig entfernt. Als neue, vierte Fläche
`--fs-baby` (aus dem echten, pixel-gesampelten Logo-Blau `#257fc2` hergeleitet, zu 15 % mit
Weiß getönt) für Foto-lastige Abschnitte eingeführt; Pink bleibt der Hauptakzent. Alle vier
Flächen (Baby-Blau, Pink, Weiß, Dunkel-Text-auf-Hell) sind rechnerisch auf WCAG-AA-Kontrast
geprüft, nicht geschätzt (echte Luminanz-/Kontrastformel, siehe FARBSYSTEM.md Abschnitt 3+8).
`theme.json` und die Block-Farbpalette im Editor sind entsprechend aktualisiert.

### 2.2 Code-Qualität & Laufzeit-Verifikation

- `php -l` über alle neuen/geänderten Dateien (Theme **und** Plugin) – fehlerfrei.
- Render-Check-Harness (simuliert `render_block()` inkl. echter Attribut-Defaults) über
  **24 Seiten/Templates** (alle 21 Registry-Seiten + front-page + 404 + Editor-Inhalt-
  Fallback) – **0 Fehler**.
- Eigens ergänzte SEO-Verifikation (nicht nur Sichtprüfung, sondern ausgeführt):
  robots.txt liefert Inhalt inkl. Sitemap-Zeile; JSON-LD ist gültiges, vollständiges JSON
  (8 Graph-Knoten auf einer Unterseite mit FAQ-Schema); Gelsenkirchens Öffnungszeiten im
  Schema zeigen korrekt 3 reale Zeitfenster statt der vorigen generischen Angabe; bei
  simuliertem aktivem Yoast SEO (`WPSEO_VERSION` gesetzt) unterdrückt das Theme
  zuverlässig seine eigene Meta-Description **und** sein eigenes JSON-LD.
- Responsiveness real getestet statt nur am Code abgelesen: alle Seiten wurden mit dem
  echten `main.css` in Chromium (Playwright) bei Mobile (390px), iPad Hoch-/Querformat
  (768/1024px), MacBook Air (1280px), MacBook Pro 14" (1512px) und Desktop (1920px)
  gerendert und automatisiert auf horizontales Überlaufen geprüft – **0 Überläufe** über
  alle geprüften Seiten × Breiten. Eine gemeinsame `main.css` bedient dabei bewusst
  sowohl Mobile als auch Desktop über Media Queries, keine getrennten Stylesheets.
- Barrierefreiheit stichprobenartig nachgeprüft: eine globale `:focus-visible`-Regel
  gibt jedem fokussierbaren Element einen sichtbaren Rahmen (nichts Neues nötig für die
  neuen Formulare/das Cookie-Banner); dabei zwei echte Lücken gefunden und behoben – der
  Fehlerfall der Standort-Schnellanmeldung zeigte bisher keine Rückmeldung, und das
  Cookie-Banner hatte `role="dialog"` ohne `aria-modal="false"` (es blockiert die Seite
  bewusst nicht).

### 2.3 Asset-Bereinigung

- 15 Bild-Assets von externen Vercel-Blob-URLs auf lokale, im Theme mitgelieferte Dateien
  umgestellt (`inc/media.php`).
- Lokales, DSGVO-konformes Self-Hosting für die Schriftart Geist vorbereitet
  (`assets/fonts/fonts.css`, `file_exists()`-abgesichert – ohne die Binärdateien fällt das
  Theme automatisch auf den System-Font-Stack zurück, kein Fehler). Begründung: dynamisches
  Nachladen von einem Drittserver überträgt die IP-Adresse vor jeder Einwilligung (LG
  München I, 20.01.2022, Az. 3 O 17493/20).
- Verbleibende externe Medien (Videos, drei Team-Fotos, Duisburg-Standortfoto,
  Instagram-Platzhalter) sind **nicht versteckt**, sondern über einen Admin-Hinweis
  (`fahrschule_media_notice()`) im Backend sichtbar gezählt – siehe Abschnitt 3.1.
- CD-Farbregel durchgezogen: 18 rohe `#fff`-Werte in `main.css` (funktional bereits
  korrektes Weiß, aber an der Basis-Palette vorbei hinterlegt) durch `var(--fs-light)`
  ersetzt – Pink (Hauptfarbe) und Baby-Blau (Hintergrund aus dem Logo) bleiben damit die
  einzigen tatsächlichen Rohfarben im gesamten Stylesheet, jede weitere Farbe leitet sich
  nachweisbar aus der `:root`-Palette ab. Rein redaktionelle Änderung ohne optischen
  Unterschied (identischer Wert), erneut per Render-Check bestätigt.

### 2.4 Inhaltliche Ehrlichkeit („erfundene Inhalte wären schlimmer als keine")

Bei der Content-Durchsicht (verschärft durch die visuelle Prüfung in 2.2, die auf der
Startseite eine bis dahin übersehene weitere Stelle sichtbar machte) wurden insgesamt
**vier** unabhängige Stellen mit vollständig erfundenen Kundendaten gefunden und
korrigiert (Prinzip identisch zum Schwesterprojekt KAZ):

- **Sechs erfundene Kundenstimmen** samt Namen und Zitaten in der Detailansicht der
  Erfahrungen-Seite – die Liste ist jetzt bewusst leer, die Sektion blendet sich
  automatisch aus, solange keine echten Zitate vorliegen.
- **Vier weitere erfundene Kundenstimmen** in einer eigenen, separaten Startseiten-Sektion
  („Kundenbewertungen") – derselbe Fehler wie oben, unabhängig davon nochmals gemacht;
  ebenfalls geleert, die Sektion entfällt jetzt automatisch.
- **Vier erfundene Kundenstimmen**, identisch (nur mit anderem Stadtteil-Namenszusatz)
  auf allen drei Standort-Seiten wiederverwendet – ebenfalls geleert, der Abschnitt
  „Bewertungen aus {Stadt}" entfällt jetzt automatisch.
- Die unbelegte Behauptung „Über 10.000+ glückliche Fahrschüler" (in keiner der
  gelieferten Unterlagen oder der alten Website belegt) wurde durch einen wahrheitsgemäßen,
  belegten Fakt (30+ Jahre Erfahrung, gegründet 1993) plus einen echten, auf die reale
  Google-Suche verlinkenden „Bewertungen ansehen"-Button ersetzt, statt eine eigene
  Sternebewertung zu behaupten, die niemand verifizieren kann.

Zusätzlich (kein erfundener Name, aber eine erfundene Tatsachenbehauptung): die
Standort-Kachel-Übersicht (`fs_render_locations()`, sichtbar auf Standorte/Kontakt/
Anmeldung) zeigte für alle drei Filialen dieselben generischen Öffnungszeiten inklusive
einer frei erfundenen Samstagsöffnung, unabhängig von der bereits in 1.6 korrigierten
Version im Standort-Hero. Beide Stellen greifen jetzt auf dieselbe Funktion zu
(`fahrschule_location_hours_text()`), damit eine erneute, an einer Stelle vergessene
Korrektur ausgeschlossen ist.

---

## 3. Noch offene Aufgaben / To-Dos

### 3.1 Fehlende Kundenassets (im Backend sichtbar über den Admin-Hinweis)

| Medium | Aktueller Stand |
|---|---|
| 3× Team-Foto je Gruppe (Büro & Ansprechpartner, Geschäftsleitung, Dozenten & Ausbilder) | Fehlt – zeigt Initialen-Platzhalter statt Fremdfoto |
| Standortfoto Duisburg | Extern (Prototyp-Speicher) – kein Originalfoto in der Bestandsaufnahme vorhanden |
| Hero-Video, Team-Video | Extern (Prototyp-Speicher) |
| 3× Absolventenfoto | Extern (Prototyp-Speicher) |
| 6× Instagram-Kachel | Unsplash-Stockfotos (Platzhalter) |
| Geist-Schriftdateien (`geist-400.woff2`, `geist-700.woff2`) | Fehlen noch – Lizenz-Download nötig (SIL Open Font License, selbst hostbar); bis dahin greift automatisch der System-Font-Stack |

Sobald echtes Material vorliegt: Dateien in die Mediathek hochladen, Schlüssel/ID in
`inc/media.php` eintragen bzw. `.woff2`-Dateien nach `assets/fonts/` legen – kein weiterer
Codeeingriff nötig.

### 3.2 Offene API-Anbindungen (companion-plugin edi-integrationen)

- **Odoo-Webhook-URL + API-Key** – aktuell leer (Einstellungen → edi-integrationen). Die
  Anbindung ist **ungetestet gegen eine echte Odoo-Instanz**, mangels Zugangsdaten – vor
  dem produktiven Einsatz einmal mit der echten Webhook-URL gegenprüfen (z. B. über
  webhook.site als Zwischenstopp).
- **Matomo-URL + Site-ID** – aktuell leer, keine Statistik-Erfassung, bis beides
  hinterlegt ist.
- Beide Formulare (Standort-Schnellanmeldung, allgemeines Kontaktformular) funktionieren
  bereits vollständig **ohne** diese Anbindungen – E-Mail-Versand ist und bleibt der
  verlässliche Hauptkanal, Odoo/Matomo sind rein additiv.

### 3.3 Bewusste Architekturentscheidungen, die eine spätere Prüfung verdienen

- Die Matomo-Einschätzung „cookielos benötigt in der Regel keinen Cookie-Banner" folgt
  gängiger fachlicher Praxis, ersetzt aber **keine Rechtsberatung im Einzelfall** –
  insbesondere falls später von der Cookielos-Voreinstellung abgewichen wird. Das
  Cookie-Banner (s. 1.7) existiert unabhängig davon bereits und übernimmt in diesem Fall
  automatisch das Gating – keine weitere Codeänderung nötig, nur die Einstellung
  „Cookielos tracken" abwählen.
- Das Theme selbst hat (anders als KAZ) noch keine eigene Options-Infrastruktur; die
  Einstellungsseite lebt deshalb komplett im Plugin. Sollte das Theme künftig eigene
  Einstellungen brauchen, bietet sich ein Hook analog zu KAZ's `kaz_settings_rows` an.
- Alte Fax-Nummern der drei Standorte wurden bewusst **nicht** übernommen (nicht mehr
  zeitgemäß) – reine Vollständigkeitsnotiz, keine offene Aufgabe.

### 3.4 Finale Freigaben vor Livegang

- ~~Farbentscheidung „Baby-Blau" gegenprüfen~~ – erledigt: Blautöne wurden auf
  Kundenwunsch vollständig entfernt (strikt Pink/Pink-Weiß, siehe FARBSYSTEM.md).
- Neue Formulartexte, die DSGVO-Einwilligungsformulierung im Kontaktformular und der
  Cookie-Banner-Text gegenlesen lassen.
- Termine auf der neuen Theorieunterricht-Seite stammen von der alten Website – vor
  Livegang beim Kunden bestätigen lassen, dass sie weiterhin aktuell sind.
- Sobald echte, mit Einwilligung veröffentlichte Kundenstimmen vorliegen: an den in
  `inc/render-callbacks.php` kommentierten Stellen eintragen (Erfahrungen-Detailseite und
  je Standort-Seite).
