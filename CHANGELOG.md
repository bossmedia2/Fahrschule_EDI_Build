# Änderungsverlauf

## 3.0.0 – Theme · 2.0.0 – Begleit-Plugin (Oktober 2026)

**Block-Theme**
- Echte FSE-Umstellung:
  - `parts/`, 19 Vorlagen in `templates/`, `customTemplates` in `theme.json`;
  - alle PHP-Vorlagen entfernt;
  - die Seitenanlage schreibt den Inhalt als Block-Markup und setzt die Vorlage;
  - eine Einmal-Migration füllt leere Seiten aus 2.x.
- Editor:
  - `editor.js` lädt über den Enqueue-Hook statt über `editor_script`;
  - `editor.css` über `add_editor_style`;
  - die Vorschau bekommt den Seitenkontext;
  - leere Blöcke zeigen einen Hinweis;
  - gespeicherte Listen (Wiederholfelder) gingen beim Neuladen verloren, behoben.
- Neue Blöcke: Brotkrumen, Fehlerseite, Karte (OpenStreetMap, Zwei-Klick),
  Vertrauensleiste, Klassen-Kacheln, Seiten-Anriss, Stellenangebote, Stelle.

**Inhalt**
- 15 Klassenseiten: BF17, C1, C1E und DE neu, B96 zur Bestätigung.
- Kurzdaten mit Quelle (FeV, FahrschAusbO, ursprünglicher Build).
- Erfundene Preise und Aussagen aus Runde 3 entfernt.
- Startseite in zwölf Abschnitten nach Briefing; kein PDF-Abschnitt voll auf
  Start- und Unterseite zugleich.
- Team nach den drei PDF-Gruppen; Geschäftsführung und Ansprechpersonen aus
  den Stammdaten.
- Impressum, Standorte und Formulare lesen Adresse, Telefon, E-Mail und
  Zeiten aus `inc/seo.php` bzw. PDF statt aus Kopien.
- Unbelegte Zahlen und Superlative entfernt (10.000+ Absolventen, 5000+
  Prüfungen, „beste“, „Größte“).

**Social-Feed**
- Der Instagram-Slider ist ein Social-Feed:
  - **Kacheln:** ein Wiederholer, jede Kachel trägt ihre Plattform (TikTok
    oder Instagram), dazu Bild-Slug, Beitragslink und Pflicht-Bildbeschreibung
    als Alt-Text.
  - **Profile:** Profil-Knöpfe je Plattform aus den Stammdaten.
  - **Darstellung:** Alle Kacheln stehen im HTML (Editor zeigt alle); das
    Karussell blendet ein Fenster von drei ein.
  - **Bild und Link:** einheitlich 1:1; jede Kachel ist ein Link in einem
    neuen Tab.
  - **Kein Embed, kein Abruf** bei TikTok oder Instagram.
  - **Behoben:** Die Karussell-Pfeile (Pink auf Pink, 1,28:1) lesen jetzt die
    Knopf-Tokens; das Instagram-Icon wurde als volles Quadrat gezeichnet.
  - **Entfernt:** `ig_1`…`ig_6`. Das Altfeld `url` bleibt registriert (gegen
    Datenverlust beim Speichern), aber unsichtbar im Editor.

**Formulare und Stellen**
- Formulare:
  - Contact Form 7 + Flamingo über das Begleit-Plugin;
  - Empfänger aus den Stammdaten, Honeypot statt reCAPTCHA;
  - Pflicht-Einwilligungen inklusive Art. 8 DSGVO;
  - Bewerbung mit PDF bis 10 MB.
- WP Job Manager: Kategorien, Karriereseite, Einzelansicht mit Bewerbung.
- **Odoo-Anbindung entfernt** (`edi-integrationen/inc/odoo.php`, Einstellungen,
  Aufrufe in `inc/forms.php`). Der Lead-Webhook war nie gegen eine echte
  Odoo-Instanz getestet; Formulare laufen über CF7. Matomo unverändert.

**Farben und Zugänglichkeit**
- Aktiver Menüeintrag war unsichtbar (1,65:1).
- Menüzustände, Fokusring und Formularfelder lesen neue Kontext-Tokens.
- Brotkrumen lagen auf Seiten ohne Seitenkopf unter der Leiste.
- Geschlossenes Schubfach war per Tab erreichbar.
- Suchseite: Weiß auf Rosé unter der Leiste.

**Bilder**
- `bin/edi-bilder.py`: benennt den Bildordner nach dem Mediathek-Schema um.
- Galerie mit echter Präfixabfrage, auch ohne Kategorie.

**Prüfkette (`bin/gate.sh`)**
- Neue Prüfungen:
  - Kontext-Tokens;
  - Startseite/NAP;
  - Formulare und Stellen;
  - Bild-Skript;
  - Doku-Tabellen;
  - FSE-Struktur.
- Smoke rendert über die Block-Vorlagen (`bin/checks/fse.php`).
- Browser: Flyout und Schubfach auf jeder Seite, Leiste gegen den Inhalt
  darunter, keine Abrufe bei Dritten.

## 2.0.0 – Runde 3

Auswählbare Seitenvorlagen, Bereiche und Klassen, Editierbarkeit (leerbare
Felder, Wiederholfelder), Bild-Strategie über Mediathek-Slugs.
