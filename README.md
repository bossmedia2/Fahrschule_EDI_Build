# Fahrschule edi — WordPress-Theme (B2C)

Hybrides WordPress-Theme (Classic-Templates + `theme.json` v3) mit zentral
registrierten Gutenberg-Blöcken und Block-Patterns für die Fahrschule &
Fahrlehrerfachschule edi (Duisburg, Essen, Gelsenkirchen).

## Aufbau

| Pfad | Inhalt |
|---|---|
| `fahrschule-edi/` | Das Theme. Wird zu `dist/fahrschule-edi.zip` gepackt. |
| `edi-integrationen/` | Optionales Begleit-Plugin (Odoo-Lead-Webhook, Matomo). |
| `bin/build-zip.sh` | Baut die Auslieferungs-ZIPs nach `dist/`. |
| `bin/gate.sh` | Prüfkette: Lint, Smoke-Test, Referenzen, Farben, Kontrast, Layout, ZIP-Größe. |
| `bin/checks/` | Die Einzelprüfungen, die `gate.sh` aufruft. |
| `fahrschule-edi/patterns/` | Start-Patterns für den Muster-Dialog beim Seitenanlegen. |
| `docs/` | Projektdokumentation. Wird **nicht** mit ausgeliefert. |

Blöcke werden nicht je Verzeichnis angelegt, sondern aus **einem** Array
(`fahrschule-edi/inc/blocks-config.php`) per `foreach` registriert — siehe
`fahrschule_register_blocks_and_patterns()` in `functions.php`.

## Seitenvorlagen

Beim Anlegen einer Seite erscheint unter *Seiten-Attribute → Vorlage* eine
Auswahl; der Muster-Dialog füllt die Seite anschließend mit editierbarem
Block-Markup.

| Vorlage | Datei | Ergänzt automatisch |
|---|---|---|
| Übersicht / Landingpage | `page-uebersicht.php` | Abschluss-Banner |
| Bereich | `page-bereich.php` | Karten aller untergeordneten Seiten |
| Kurs / Führerscheinklasse | `page-kurs.php` | Geschwisterklassen + Anmeldung |

Der gemeinsame Rumpf steht in `inc/template-parts.php`. Die Hierarchie kommt
aus `inc/pages.php`: wer eine Klassenseite anlegt und ihr `parent` setzt,
bekommt Karte, Menüeintrag und Brotkrume ohne weiteres Zutun.

## Editierbarkeit

Jedes Feld lässt sich im Editor leeren — der zugehörige Abschnitt verschwindet
dann im Frontend restlos, statt eine leere Überschrift zu hinterlassen. Ein
Knopf stellt die Vorgabe wieder her. Listen (Führerscheinklassen, Team,
Kennzahlen) sind Wiederholfelder mit Hinzufügen, Löschen und Sortieren.

> **Regel für Rendercode:** Felder nie direkt ausgeben, sondern über `fs_el()`,
> `fs_wrap()`, `fs_has()` und `fs_button()` aus `inc/helpers.php`. Nur so fällt
> mit dem Wert auch sein Element weg. `bin/checks/emptiable.php` erzwingt das.

## Bilder

Das Theme liefert **keine** Fotos mit. `fs_media()` schlägt sie über ihren
Dateinamen in der Mediathek nach — Datei nach Schema hochladen genügt, siehe
`docs/assets_image_list.md`. Fehlt ein Bild, blendet sich der Block aus.

## Build

```bash
bin/gate.sh        # Prüfkette; bricht beim ersten Fehler ab
bin/build-zip.sh   # -> dist/fahrschule-edi.zip, dist/edi-integrationen.zip
```

Das Theme-ZIP muss unter 2 MB bleiben; `build-zip.sh` bricht sonst ab.

## Installation

*Design → Themes → Theme hinzufügen → Theme hochladen* → `dist/fahrschule-edi.zip`.
Das Begleit-Plugin ist optional und nur für Odoo/Matomo nötig; ohne es
versenden die Formulare weiterhin per `wp_mail()`.

## Farbsystem (Kurzfassung)

Pink / Weiß / Dunkelakzent. Die Rohfarben stehen ausschließlich im
`:root`-Block von `assets/css/main.css`. Komponenten benutzen nie die
Rohfarben, sondern die Kontextvariablen `--c-text`, `--c-heading`,
`--c-accent`, `--c-card*`, `--c-btn-*`, die jede Fläche (`.fs-surface-*`)
neu belegt.

> **Regel:** Wer an einer Komponente ein eigenes `background:` setzt, muss die
> Kontextvariablen gleich mit neu belegen. Sonst erbt die Komponente die
> Textfarben der *umgebenden* Fläche — genau daraus entstanden die
> Weiß-auf-Weiß- und Dunkel-auf-Dunkel-Fehler, die `bin/checks/contrast.mjs`
> jetzt dauerhaft abfängt.

Details: `docs/FARBSYSTEM.md`.
