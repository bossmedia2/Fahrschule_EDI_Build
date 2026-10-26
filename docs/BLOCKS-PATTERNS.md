# Fahrschule edi – Blöcke, Vorlagen, Seiten

Stand Theme 3.2.0. Ergänzend: **FARBSYSTEM.md** (Farben, Kontraste),
**assets_image_list.md** (Bilder, Bild-Skript), **SETUP.md** (Einrichtung).

Die beiden Tabellen unten erzeugt `php bin/docs-tabellen.php` aus dem Code;
der Gate prüft, dass sie aktuell sind. Nicht von Hand bearbeiten.

---

## 1. Aufbau: Block-Theme

Seit 3.0 ist das Theme ein echtes Block-Theme (Full Site Editing). Header,
Footer und jede Vorlage lassen sich unter *Design → Editor* bearbeiten.

| Ebene | Ort | Inhalt |
|---|---|---|
| Parts | `parts/header.html`, `parts/footer.html` | Navigation · Footer, TikTok-Badge, Cookie-Banner |
| Vorlagen | `templates/*.html` | nur Rahmen: Brotkrumen und Seiteninhalt |
| Seiteninhalt | `post_content` jeder Seite | was die Redaktion pflegt |
| Blöcke | `inc/blocks-config.php` → `functions.php` | 49 dynamische Blöcke aus **einem** Array, per `foreach` registriert |

**Wer liefert was (seit 3.2).** Die Vorlage ist nur der Rahmen: Header,
Brotkrumen, Seiteninhalt, Footer. Alles Seitenspezifische steht im
Seiteninhalt (`post_content`): Seitenkopf, sämtliche Abschnitte, der
Handlungsaufruf. Deshalb ist im Seiten-Editor die ganze Seite sichtbar, und
jeder Abschnitt lässt sich verschieben, löschen und bearbeiten – nur für
diese Seite. `bin/checks/vorlagen.py` bricht den Gate, sobald wieder ein
Inhaltsblock in einer Seitenvorlage steht.

**Auswählbare Vorlagen** (Seite → Vorlage), eingetragen in `theme.json` →
`customTemplates`. Sie sind als Rahmen gleich. Die Vorlage bestimmt, welcher
**Seitenrahmen** beim Anlegen und beim Neuaufbau um den Inhalt gesetzt wird
(`fahrschule_vorlage_rahmen()`, `inc/pages.php`):

| Vorlage | Seitenrahmen im Inhalt (davor · danach) | für |
|---|---|---|
| Übersichtsseite (`page-uebersicht`) | Seitenkopf · Handlungsaufruf | Führerscheinklassen, Leistungen, Standorte, Über uns |
| Bereichsseite (`page-bereich`) | Seitenkopf · Kacheln aller Unterseiten (Hellpink), Handlungsaufruf | PKW, Motorrad, LKW & Bus |
| Kursseite (Führerscheinklasse) (`page-kurs`) | Seitenkopf · Geschwisterklassen (Hellpink), Handlungsaufruf | die 15 Klassenseiten |
| Beruf & Weiterbildung (`page-beruf`) | Seitenkopf · Handlungsaufruf | Berufskraftfahrer, Fahrlehrerausbildung, ASF & FES |
| Standortseite (`page-standort`) | – (Standort-Hero im Inhalt) · Karte (Zwei-Klick, Hellpink), Handlungsaufruf | Duisburg, Essen, Gelsenkirchen |
| Teamseite (`page-team`) | Seitenkopf · – | Team |
| Kontakt & Anmeldung (`page-kontakt`) | Seitenkopf · Standorte kompakt (Hellpink) | Kontakt, Anmeldung |
| FAQ (`page-faq`) | Seitenkopf · Handlungsaufruf | FAQ |
| Theorieunterricht (`page-theorie`) | Seitenkopf · Handlungsaufruf | Theorieunterricht |
| Rechtstext (`page-recht`) | Seitenkopf · – | Impressum, Datenschutz |

Dazu die Vorlagen ohne Auswahl: `front-page`, `page` (Standard), `404`,
`search`, `archive`, `single`, `index`, `single-job_listing`,
`archive-job_listing`.

**Seitenkopf.** `fahrschule/page-hero` steht im Seiteninhalt, als erster
Abschnitt nach den Brotkrumen. Seiten mit eigenem Hero (Standort,
Erfahrungen) bekommen keinen. Titel und Einleitung kommen aus der Registry
(`inc/pages.php`), sonst aus dem Seitentitel.

**Seiteninhalt bei der Anlage.** `fahrschule_create_pages()` legt beim
Aktivieren alle Registry-Seiten an, Elternseiten zuerst:

- `post_content` ist der Seitenrahmen der Vorlage um den Block-Stapel der
  Seite aus der Registry; hat die Seite keinen eigenen (Bereichsseiten), das
  **Startmuster ihrer Vorlage** (es enthält den Rahmen schon).
- Die Vorlage wird gesetzt (`_wp_page_template`, Registry-Schlüssel
  `template`), die Startseite als `page_on_front`.
- Ein Einmal-Hinweis im Admin listet die angelegten Seiten.
- Eine zweite Aktivierung ändert nichts: vorhandene Seiten bleiben, wie sie
  sind.

Danach gehört der Inhalt dem Editor, die Registry schreibt nie darüber.

**Aus Startmuster neu aufbauen.** Für Seiten aus älteren Installationen
(Inhalt dünn, Seitenkopf und Handlungsaufruf standen bis 3.1 in der Vorlage):
Seitenliste → Zeilenaktion oder Sammelaktion „Aus Startmuster neu aufbauen“.
Eine Bestätigungsseite warnt und nennt die Seiten; erst ihr Knopf ersetzt den
Inhalt (`inc/neu-aufbauen.php`). Die vorige Fassung bleibt als Revision.
Quelle wie bei der Anlage. Automatisch passiert das nie. Die
Migration (`fahrschule_migrate_content()`, Inhaltsstand 4) füllt nur Seiten
bestehender Installationen, deren Inhalt noch leer ist.

**Startmuster** (Muster-Dialog beim Anlegen einer Seite, Kategorie
„Fahrschule edi – Seiten“): elf Stück, eins je auswählbarer Vorlage plus
eine Landingpage. Inhalt in `fahrschule_startmuster()` (`inc/pages.php`) –
dieselbe Quelle wie die Seitenanlage; `patterns/seite-*.php` geben nur das
Markup aus.

| Startmuster | Vorlage | Blöcke |
|---|---|---|
| Übersichtsseite | `page-uebersicht` | page-teasers, services, testimonials |
| Bereichsseite | `page-bereich` | services |
| Kursseite (Führerscheinklasse) | `page-kurs` | kurs-details (Felder leer), faq |
| Beruf & Weiterbildung | `page-beruf` | faq, contact-form |
| Standortseite | `page-standort` | location-hero, location-content, location-calculator |
| Teamseite | `page-team` | about-team, fahrlehrer-finder |
| Theorieunterricht | `page-theorie` | theory-schedule |
| FAQ | `page-faq` | faq |
| Kontakt & Anmeldung | `page-kontakt` | contact-form |
| Rechtstext | `page-recht` | legal |
| Landingpage | `page` (Standard) | vertrauensleiste, klassen-kacheln, testimonials, anmeldung |

**Fläche je Abschnitt.** Jeder Inhaltsblock hat das Feld „Fläche“:
Vollpink (Vorgabe) · Hellpink · Leiste (nur schmale Bänder) · Foto (mit
Medien-Slug im Feld darunter). Das Feld setzt nur den Farbkontext, siehe
FARBSYSTEM.md. Seitenanlage und Startmuster verteilen die Flächen im
Rhythmus **Vollpink, Vollpink, Hellpink** über die ganze Seite, also mit
den Vorlagenblöcken (`fahrschule_rhythmus()`); nie stehen drei Vollpink
hintereinander. Ohne eigenen Abschnitt bzw. mit fester Fläche: Navigation,
Footer, Badges, Brotkrumen, Stellen-Einzelansicht, die Heroes.

---

## 2. Editierbarkeit

- **Jedes Feld lässt sich leeren**, der Abschnitt verschwindet dann restlos.
  Erzwungen von `bin/checks/emptiable.php`.
- **Listen sind Wiederholfelder:** Hinzufügen, Löschen, Sortieren. Sie werden
  als `array` registriert; ab 3.0 auch im Editor-Skript, vorher verwarf der
  Editor gespeicherte Listen beim Neuladen.
- **Leere Kontaktfelder bedeuten „aus den Stammdaten“:** Standort-Hero und
  Karte holen Adresse, Telefon, E-Mail und Öffnungszeiten über die Stadt.
  Ins Seitenmarkup wird keine Kopie geschrieben.
- **Editor-Vorschau:** Der Editor schickt die `post_id` mit, die Vorschau
  rendert also mit Seitenkontext. Blöcke ohne Ausgabe zeigen einen Hinweis
  statt eines leeren Kastens. `assets/css/editor.css` macht sichtbar, was im
  Frontend erst JavaScript einblendet.

---

## 3. Alle Blöcke

Jeder Block mit Pattern-Kennzeichen ist zugleich als Muster einfügbar
(`fahrschule-pattern/…`). „Leer = ausgeblendet“: Ohne Inhalt oder Daten
gibt der Block nichts aus, etwa die Galerie ohne Bilder.

<!-- tabelle:bloecke -->
| Block (`fahrschule/…`) | Titel | Felder | Leer = ausgeblendet | Verwendet in |
|---|---|---|---|---|
| `bereich-uebersicht` | Bereich: Klassen-Übersicht | Bereich (Seitenschlüssel, leer = aktuelle Seite) · Eyebrow · Überschrift · Untertitel · Icon für Karten ohne eigenes Icon · Linktext auf der Karte · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | nur als Pattern/Block einfügbar |
| `kurs-details` | Kurs: Eckdaten & Inhalte | Eyebrow · Überschrift · Mindestalter · Theorieunterricht · Pflichtfahrten · Fahrstunden (Richtwert) · Prüfung · Richtpreis · Preisverweis: Link · Preisverweis: Text · Voraussetzungen · Ablauf der Ausbildung · Förderung & Finanzierung · Abschließender Hinweis · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | /lkw-bus/klasse-c, /lkw-bus/klasse-c1, /lkw-bus/klasse-c1e, /lkw-bus/klasse-ce, /lkw-bus/klasse-d, /lkw-bus/klasse-de, /motorrad/klasse-a, /motorrad/klasse-a1, /motorrad/klasse-a2, /motorrad/klasse-am, /pkw/klasse-b, /pkw/klasse-b197, /pkw/klasse-b96, /pkw/klasse-be, /pkw/klasse-bf17 |
| `fahrlehrer-finder` | Fahrlehrer-Finder | Eyebrow · Überschrift · Untertitel · Text, wenn kein Treffer · Person (Liste) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | /ueber-uns/team |
| `galerie` | Foto-Galerie | Eyebrow · Überschrift · Untertitel · Bilder je Nachladeschritt · Kategorie (Liste) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | /ueber-uns |
| `navigation` | Navigation | Logo-URL | nein | parts/header.html |
| `footer` | Footer | Logo-URL | nein | parts/footer.html |
| `tiktok-badge` | TikTok Badge | TikTok-URL · Zeile 1 · Zeile 2 | nein | parts/footer.html |
| `cookie-banner` | Cookie-Banner | – | nein | parts/footer.html |
| `video-hero` | Video Hero | Badge · Überschrift · Hervorgehobenes Wort · Text (Absätze durch Leerzeile trennen) · Button 1 Text · Button 1 Link · Button 2 Text · Button 2 Link · Video-URL (Mediathek: edi-video-hero.mp4) · Standbild-URL (Poster, Editor, reduzierte Bewegung; Mediathek: edi-video-hero-standbild) | nein | / |
| `hero` | Hero (hell) | Badge · Überschrift · Hervorgehobenes Wort · Text · Button 1 Text · Button 1 Link · Button 2 Text · Button 2 Link | nein | nur als Pattern/Block einfügbar |
| `license-classes` | Führerscheinklassen (Karten) | Führerscheinklasse (Liste) · Eyebrow · Überschrift · Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /preise |
| `calculator` | Führerschein-Rechner | Eyebrow · Überschrift · Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /preise |
| `locations` | Standorte (Übersicht) | Eyebrow · Überschrift · Untertitel · Abschnittskopf (Label + Überschrift) anzeigen · Darstellung · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /, /standorte |
| `team-spirit` | Team Spirit | Wert (Liste) · Badge · Überschrift · Hervorgehobenes Wort · Text · Zitat · Zitat-Autor · Video-URL · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns/team |
| `fleet-facilities` | Flotte & Einrichtungen | Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /moderne-ausbildung |
| `testimonials` | Testimonials | Eyebrow · Überschrift · Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | / |
| `instagram-slider` | TikTok-Video (Player) | TikTok-Video-URL (z. B. https://www.tiktok.com/@edi_fahrschule/video/…) · Überschrift (leer = ausblenden) · Profil-Link für den Knopf (leer = ausblenden) · Kachel (alt) (Liste) · TikTok: @-Name (alt) · TikTok: Profil-Link (alt) · Hervorgehobenes Wort (alt) · Untertitel (alt) · Instagram: @-Name (alt) · Instagram: Profil-Link (alt) · Instagram-URL (alt) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | /ueber-uns/erfahrungen |
| `faq` | FAQ | Eyebrow · Überschrift · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /faq, /preise |
| `cta` | CTA / Kontakt | Kennzahl (Liste) · Überschrift · Hervorgehobenes Wort · Lead-Text · Telefon · E-Mail · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /kontakt |
| `about-intro` | Über uns – Intro | Eyebrow · Überschrift · Hervorgehobenes Wort · Absatz 1 · Absatz 2 · Absatz 3 · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns |
| `about-timeline` | Über uns – Timeline | Station (Liste) · Überschrift · Hervorgehobenes Wort · Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns |
| `about-philosophy` | Über uns – Philosophie | Grundsatz (Liste) · Überschrift · Hervorgehobenes Wort · Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns |
| `about-team` | Über uns – Team | Eyebrow · Überschrift · Absatz 1 · Absatz 2 · Abschlusstext · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns/team |
| `testimonials-hero` | Testimonials – Hero | Überschrift · Hervorgehobenes Wort · Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns/erfahrungen |
| `graduates-gallery` | Absolventen-Galerie | Überschrift · Hervorgehobenes Wort · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns/erfahrungen |
| `testimonials-detailed` | Testimonials – Detailraster | Überschrift · Hervorgehobenes Wort · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns/erfahrungen |
| `location-hero` | Standort – Hero | Stadt · Icon · Badge · Überschrift · Lead-Text · Adresse (leer = aus den Stammdaten der Stadt) · PLZ & Ort (leer = Stammdaten) · Telefon (leer = Stammdaten) · E-Mail (leer = Stammdaten) · Öffnungszeiten (leer = zentral hinterlegte Zeiten) · Ansprechpartner vor Ort (optional) | nein | /standorte/duisburg, /standorte/essen, /standorte/gelsenkirchen |
| `location-content` | Standort – Inhalt | Stadt · Bild 1 URL (leer = edi-standort-<stadt> aus der Mediathek) · Bild 1 Titel · Bild 1 Untertitel · Bild 2 URL · Bild 2 Titel · Bild 2 Untertitel · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /standorte/duisburg, /standorte/essen, /standorte/gelsenkirchen |
| `location-calculator` | Standort – Rechner | Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /standorte/duisburg, /standorte/essen, /standorte/gelsenkirchen |
| `license-overview` | Führerscheinklassen (Übersicht) | Eyebrow · Überschrift · Untertitel · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /fuehrerscheinklassen |
| `services` | Unsere Leistungen | Eyebrow · Überschrift · Untertitel · Listen-Einleitung · Abschlusstext · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /leistungen |
| `theory-schedule` | Theorieunterricht – Termine | Eyebrow · Überschrift · Lead-Text · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /theorieunterricht |
| `professional-drivers` | Berufskraftfahrer | Eyebrow · Überschrift · Absatz 1 · Absatz 2 · Listen-Einleitung · Abschlusstext · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /berufskraftfahrer |
| `seminars` | ASF & FES | Eyebrow · Überschrift · Untertitel · Listen-Einleitung · Abschlusstext · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /asf-fes |
| `modern-training` | Moderne Ausbildung | Eyebrow · Überschrift · Hervorgehobenes Wort · Absatz 1 · Absatz 2 · Absatz 3 · Abschlusstext · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /moderne-ausbildung |
| `anmeldung` | Anmeldung (CTA) | Eyebrow · Überschrift · Absatz 1 · Absatz 2 · Absatz 3 · Text vor dem Button · Button-Text · Button-Link · Abschnittskopf (Label + Überschrift) anzeigen · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | / |
| `contact-form` | Formular (Kontakt, Anmeldung, Bewerbung) | Überschrift · Lead-Text · Formular · Eigener Shortcode (optional, z. B. [contact-form-7 id="…"]) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /anmeldung, /kontakt, /ueber-uns/karriere |
| `page-hero` | Seitenkopf (Unterseite) | Eyebrow · Überschrift (leer = PDF-Abschnitt bzw. Seitentitel) · Hervorgehobenes Wort · Einleitung (leer = aus Registry) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | nur als Pattern/Block einfügbar |
| `breadcrumbs` | Brotkrumen | – | ja | templates/page-bereich.html, templates/page-beruf.html, templates/page-faq.html, templates/page-kontakt.html, templates/page-kurs.html, templates/page-recht.html, templates/page-standort.html, templates/page-team.html, templates/page-theorie.html, templates/page-uebersicht.html, templates/page.html, templates/single-job_listing.html, templates/single.html |
| `karte` | Karte (nach Einwilligung) | Standort (leer = Seite; sonst duisburg, essen, gelsenkirchen) · Überschrift · Hinweis vor dem Laden · Button-Text · Routen-Link (öffnet extern) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | nur als Pattern/Block einfügbar |
| `jobs` | Stellenangebote | Überschrift · Einleitung · Anzahl (höchstens) · Kategorie-Filter (leer = alle; fahrlehrer, buero-ansprechpartner, quereinstieg-ausbildung) · Button-Text je Stelle · Leer-Text (keine offene Stelle) · Text Initiativ-Knopf · Ziel-Link Initiativ-Knopf · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /ueber-uns/karriere, templates/archive-job_listing.html |
| `stelle` | Stelle (Einzelansicht) | Überschrift Bewerbung | ja | templates/single-job_listing.html |
| `fehlerseite` | Fehlerseite (404) | Eyebrow · Überschrift · Hervorgehobenes Wort · Text · Weg (Liste) · Suchfeld zeigen (1/0) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | templates/404.html |
| `vertrauensleiste` | Vertrauensleiste | Eintrag (Liste) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | / |
| `klassen-kacheln` | Klassen-Kacheln | Eyebrow · Überschrift · Kachel (Liste) · Button 1 Text (Link der Kachel) · Button 2 Text · Button 2 Link · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | / |
| `seiten-anriss` | Seiten-Anriss | Seite (Schlüssel, z. B. ueber-uns, team) · Eyebrow (leer = PDF-Label) · Überschrift (leer = PDF/Seitentitel) · Text (leer = erster PDF-Absatz/Einleitung) · Button-Text · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | ja | / |
| `page-teasers` | Themen-Teaser | Eyebrow (optional) · Überschrift (optional) · Untertitel (optional) · Teaser (Liste) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | / |
| `instructor-training` | Fahrlehrerausbildung | Eyebrow · Überschrift · Hervorgehobenes Wort · Absatz 1 · Absatz 2 · Abschlusstext · Button-Text · Button-Link (@seitenschlüssel) · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /fahrlehrerausbildung |
| `legal` | Rechtstext (Impressum/Datenschutz) | Inhalt · Fläche · Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001) | nein | /datenschutz, /impressum |
<!-- /tabelle:bloecke -->

---

## 4. Seiten der Registry

URL, Vorlage und der Inhalt, den die Seitenanlage schreibt.

<!-- tabelle:seiten -->
| Seite | URL | Vorlage | Seiteninhalt (bei Anlage geschrieben) |
|---|---|---|---|
| Startseite | `/` | `front-page` | video-hero, vertrauensleiste, klassen-kacheln, seiten-anriss, seiten-anriss, seiten-anriss, page-teasers, locations, seiten-anriss, seiten-anriss, testimonials, anmeldung |
| Führerscheinklassen | `/fuehrerscheinklassen/` | `page-uebersicht` | license-overview |
| Preise & Rechner | `/preise/` | `page` | license-classes, calculator, faq |
| Leistungen | `/leistungen/` | `page-uebersicht` | services |
| Moderne Ausbildung | `/moderne-ausbildung/` | `page` | modern-training, fleet-facilities |
| Theorieunterricht | `/theorieunterricht/` | `page-theorie` | theory-schedule |
| Berufskraftfahrer | `/berufskraftfahrer/` | `page-beruf` | professional-drivers |
| Fahrlehrerausbildung | `/fahrlehrerausbildung/` | `page-beruf` | instructor-training |
| ASF & FES | `/asf-fes/` | `page-beruf` | seminars |
| PKW | `/pkw/` | `page-bereich` | Startmuster „Bereichsseite“: services |
| Motorrad | `/motorrad/` | `page-bereich` | Startmuster „Bereichsseite“: services |
| LKW & Bus | `/lkw-bus/` | `page-bereich` | Startmuster „Bereichsseite“: services |
| Klasse BF17 | `/pkw/klasse-bf17/` | `page-kurs` | kurs-details |
| Klasse B | `/pkw/klasse-b/` | `page-kurs` | kurs-details |
| Klasse B197 | `/pkw/klasse-b197/` | `page-kurs` | kurs-details |
| Klasse BE | `/pkw/klasse-be/` | `page-kurs` | kurs-details |
| Klasse B96 | `/pkw/klasse-b96/` | `page-kurs` | kurs-details |
| Klasse AM | `/motorrad/klasse-am/` | `page-kurs` | kurs-details |
| Klasse A1 | `/motorrad/klasse-a1/` | `page-kurs` | kurs-details |
| Klasse A2 | `/motorrad/klasse-a2/` | `page-kurs` | kurs-details |
| Klasse A | `/motorrad/klasse-a/` | `page-kurs` | kurs-details |
| Klasse C1 | `/lkw-bus/klasse-c1/` | `page-kurs` | kurs-details |
| Klasse C1E | `/lkw-bus/klasse-c1e/` | `page-kurs` | kurs-details |
| Klasse C | `/lkw-bus/klasse-c/` | `page-kurs` | kurs-details |
| Klasse CE | `/lkw-bus/klasse-ce/` | `page-kurs` | kurs-details |
| Klasse D | `/lkw-bus/klasse-d/` | `page-kurs` | kurs-details |
| Klasse DE | `/lkw-bus/klasse-de/` | `page-kurs` | kurs-details |
| Standorte | `/standorte/` | `page-uebersicht` | locations |
| Duisburg | `/standorte/duisburg/` | `page-standort` | location-hero, location-content, location-calculator |
| Essen | `/standorte/essen/` | `page-standort` | location-hero, location-content, location-calculator |
| Gelsenkirchen | `/standorte/gelsenkirchen/` | `page-standort` | location-hero, location-content, location-calculator |
| Über uns | `/ueber-uns/` | `page-uebersicht` | about-intro, about-timeline, about-philosophy, galerie |
| Team | `/ueber-uns/team/` | `page-team` | about-team, fahrlehrer-finder, team-spirit |
| Erfahrungen | `/ueber-uns/erfahrungen/` | `page` | testimonials-hero, graduates-gallery, testimonials-detailed, instagram-slider |
| Karriere | `/ueber-uns/karriere/` | `page` | jobs, contact-form |
| FAQ | `/faq/` | `page-faq` | faq |
| Kontakt | `/kontakt/` | `page-kontakt` | cta, contact-form |
| Anmeldung | `/anmeldung/` | `page-kontakt` | contact-form |
| Impressum | `/impressum/` | `page-recht` | legal |
| Datenschutz | `/datenschutz/` | `page-recht` | legal |
<!-- /tabelle:seiten -->

**Startseite:** die zwölf Abschnitte des Briefings. Was eine eigene
Unterseite hat, steht dort voll und auf der Startseite als Anriss: Label,
Überschrift und erster Absatz wortgleich aus dem PDF. Die Marker
`data-pdf-section` (voll), `data-pdf-teaser` (Anriss) und `data-pdf-head`
(Seitenkopf) halten das für `bin/checks/startseite.py` auseinander.

---

## 5. Formulare und Stellen

- **Formulare:**
  - `fahrschule/contact-form` (Art: Kontakt, Anmeldung, Bewerbung) und die
    Schnellanmeldung im Standort-Hero laufen über `fs_formular()`.
  - Mit Contact Form 7 und dem Begleit-Plugin ist es das CF7-Formular der
    Art; ohne CF7 der eingebaute Rückfall (`inc/forms.php`).
  - Einzelheiten: SETUP.md, Abschnitt 0.
- **Stellen:**
  - `fahrschule/jobs` listet offene Stellen aus WP Job Manager.
  - `fahrschule/stelle` zeigt die Einzelansicht samt Bewerbungsformular.

---

## 6. Prüfkette

`bin/gate.sh` ist der einzige Einstieg; er läuft aus jedem Verzeichnis und
druckt am Ende eine Tabelle (Schritt, Ergebnis, Dauer). Jeder Schritt ist
**ok**, **übersprungen** (mit Grund) oder **FEHLER**; nur Fehler machen den
Exit-Code ungleich 0, übersprungene Schritte stehen am Ende als Liste.
`--schnell` überspringt den Browser.

0. Voraussetzungen: PHP ≥ 8.0, Python, Node, zip/unzip/bc, Playwright
   (Paket und Chromium getrennt). Was fehlt, steht mit dem Befehl zum
   Nachrüsten da; die abhängigen Schritte laufen nicht und gelten als
   übersprungen. Fehlendes mbstring ist ein Hinweis – das Theme braucht es
   nicht. Folgeschritte eines Fehlers ebenso: Startseite, Flächenfolge und
   Browser brauchen die Seiten aus dem Smoke-Test.
1. Syntax: `php -l`, `node --check`.
2. Inhalt und Struktur:
   - kein `editor_script`, Bild-Skript nicht in der Kette;
   - Smoke-Test: alle Blöcke und jede Seite über ihre Block-Vorlage
     (`bin/checks/fse.php` bildet `template-canvas` nach), 404-Folge,
     keine toten internen Links;
   - Startseite/NAP, Referenzen (auch: keine `mb_`-Funktion ohne Rückfall,
     Netzabrufe nur in `fs_social_abruf()`), Struktur (Vorlagen, 11
     Startmuster), Vorlagen nur Rahmen, Formulare, Editierbarkeit;
   - Seitenanlage (zwei Aktivierungen, Rahmen im Inhalt, Neuaufbau nur auf
     Bestätigung), Hero (Video/Standbild), Social-Feed (Abruf nachgebaut),
     Doku-Tabellen;
   - Theme ohne mbstring: alle Seiten noch einmal mit abgeschalteten
     `mb_`-Funktionen.
3. Farben: Kontext-Tokens (kein Pink Tief, Hover nur aus `--c-*`), Kontrast
   rechnerisch je Fläche, Flächenfolge je Seite.
4. Browser bei 390, 768, 1024, 1280, 1512 und 1920 px: Überlauf, Kontrast in
   allen Zuständen inkl. offener Menüs, Fokusring, Social-Raster, keine
   Abrufe bei Dritten.
5. Tailwind: entfällt (keine Quelle; das CSS ist handgeschrieben).
6. Build: Theme- und Plugin-ZIP, Theme unter 2 MB.

Jedes Prüfskript läuft auch allein, aus jedem Verzeichnis. Manuell, nicht
im Gate: `python3 bin/checks/test_edi_bilder.py` (Bild-Skript).
