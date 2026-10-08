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
| `docs/` | Projektdokumentation. Wird **nicht** mit ausgeliefert. |

Blöcke werden nicht je Verzeichnis angelegt, sondern aus **einem** Array
(`fahrschule-edi/inc/blocks-config.php`) per `foreach` registriert — siehe
`fahrschule_register_blocks_and_patterns()` in `functions.php`.

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
