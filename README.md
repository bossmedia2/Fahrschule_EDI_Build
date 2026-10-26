# Fahrschule edi — WordPress-Block-Theme (B2C)

Block-Theme (Full Site Editing, `theme.json` v3) für die Fahrschule &
Fahrlehrerfachschule edi (Duisburg, Essen, Gelsenkirchen) mit 49 zentral
registrierten, dynamischen Gutenberg-Blöcken.

## Aufbau

| Pfad | Inhalt |
|---|---|
| `fahrschule-edi/` | Das Theme → `dist/fahrschule-edi.zip` (unter 2 MB) |
| `fahrschule-edi/templates/`, `parts/` | Block-Vorlagen und Header/Footer – bearbeitbar unter *Design → Editor* |
| `fahrschule-edi/inc/blocks-config.php` | **ein** Array beschreibt alle Blöcke; `functions.php` registriert sie per `foreach` |
| `fahrschule-edi/inc/pages.php` | Seiten-Registry: URLs, Navigation, Vorlage, Inhalt bei der Seitenanlage |
| `fahrschule-edi/inc/pdf-texts.php` · `inc/seo.php` | Kundentexte (PDF) · Stammdaten (NAP, Geschäftsführung, Zeiten) |
| `edi-integrationen/` | Begleit-Plugin: Formulare in Contact Form 7, WP Job Manager, Matomo |
| `bin/gate.sh` · `bin/checks/` | Prüfkette (siehe unten) |
| `bin/edi-bilder.py` | benennt den Bildordner nach dem Mediathek-Schema um |
| `docs/` | Dokumentation (wird nicht ausgeliefert) |

## Installation

Kurzfassung, ausführlich in `docs/SETUP.md`, Abschnitt 0:

1. **Plugins** installieren und aktivieren: Contact Form 7, Flamingo,
   WP Job Manager, dann `dist/edi-integrationen.zip`. Das Begleit-Plugin
   legt die Formulare und die Stellenkategorien an.
2. **Theme** hochladen und aktivieren: *Design → Themes →
   Theme hochladen*, `dist/fahrschule-edi.zip`. Die Seiten entstehen samt
   Inhalt und Vorlage.
3. **Bilder** mit `bin/edi-bilder.py` umbenennen und nur aus `umbenannt/`
   importieren. Siehe `docs/assets_image_list.md`.

Ohne die Plugins läuft das Theme weiter: Es nutzt dann eingebaute Formulare
und zeigt keine Stellen. Ein Hinweis im Backend nennt, was fehlt.

## Regeln für Code

- **Farben** nur über Kontext-Tokens `--c-*`, nie `--fs-*` in einem
  Kontext. Prüfung: `bin/checks/tokens.py`. Details: `docs/FARBSYSTEM.md`.
- **Felder** nie direkt ausgeben, sondern über `fs_el()`, `fs_wrap()`,
  `fs_has()`. Ein geleertes Feld lässt dann nichts zurück; geprüft von
  `bin/checks/emptiable.php`.
- **Eine Quelle je Tatsache:**
  - Seiten in `fahrschule_page_map()`;
  - Blöcke in `fahrschule_blocks_config()`;
  - Medien in `fahrschule_media_map()`;
  - Texte in `inc/pdf-texts.php`;
  - Stammdaten in `inc/seo.php`;
  - Zeiten in `fahrschule_location_hours_text()`.
- **Kein erfundener Inhalt:** Leer ist besser. `refs.py` sperrt früher
  entfernte, unbelegte Behauptungen.
- **Keine Abrufe bei Dritten ohne Einwilligung:** Die Browserprüfung fängt
  jede externe Anfrage ab.

## Prüfen und bauen

```bash
bin/gate.sh                 # volle Prüfkette inkl. Browser (Playwright), aus jedem Verzeichnis
bin/gate.sh --schnell       # dasselbe ohne Browser
php bin/docs-tabellen.php   # Block-/Seitentabelle in docs/BLOCKS-PATTERNS.md erneuern
bin/build-zip.sh            # -> dist/fahrschule-edi.zip, dist/edi-integrationen.zip
```

Änderungen: `CHANGELOG.md`.
