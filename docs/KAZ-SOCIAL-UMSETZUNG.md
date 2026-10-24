# KAZ: Social-Grid – Umsetzungsnotiz

Für die Sitzung mit dem KAZ-Repository. In edi ist dasselbe Muster umgesetzt
(Block `fahrschule/instagram-slider`, `fs_render_instagram_slider()`).
Feldnamen und Verhalten sind identisch zu halten.

## Vorher lesen

1. `kaz_render_social()` in `inc/sections.php`: Was macht es heute aus
   `tiktok1`…`tiktok3`?
   - Bindet es ein TikTok-Embed oder `embed.js` ein, fällt das weg.
   - Dann muss auch der Absatz in der Datenschutzerklärung raus.
2. Die Registrierungsschleife in `inc/blocks.php`
   (`$attributes[ $f['key'] ] = array( 'type' => 'string' )`) und den
   Kommentar zum Wiederholer mit `seed`.

## Felder (`kaz_block_fields()`, Block `kaz/social` – Slug bleibt)

| Feld | Typ | Hinweis |
|---|---|---|
| `heading` | text | Überschrift, kein @handle mehr; leer = aus |
| `instaHandle` / `instaUrl` | text | leerer Handle = kein Knopf |
| `tiktokHandle` / `tiktokUrl` | text | leerer Handle = kein Knopf |
| `items` | repeater, `seed` = `kaz_social_items` | Unterfelder `platform` (select tiktok/instagram, Vorgabe tiktok), `image` (image), `url` (text, leer = Profil), `caption` (text, Pflicht: Alt + title) |

`kaz_social_items()` kommt nach `inc/data.php`. Ohne `seed` löscht der erste
Klick auf „+ Eintrag“ die sichtbaren Kacheln.

## Migrationsfalle

- **Altschlüssel nicht abmelden:** `post1`…`post6`, `post1Url`…`post6Url` und
  `tiktok1`…`tiktok3` bleiben registriert. Sonst verwirft Gutenberg die Werte
  beim nächsten „Aktualisieren“.
- **`kaz_block_legacy_fields()`:** gleicher Aufbau wie `kaz_block_fields()`.
  - Fließt in der Registrierungsschleife in `$attributes` ein.
  - Landet **nicht** in `window.KAZ_BLOCKS`.
  - In edi gelöst über `'legacy' => true` am Feld, übersprungen in
    `fahrschule_editor_assets()`.
- **Lesemigration in `kaz_render_social()`:**
  - Ist `items` nicht gesetzt oder leer und existiert ein `postN` oder
    `tiktokN`, wird die Kachelliste daraus gebaut.
  - `postN` → instagram mit Bild; `tiktokN` → tiktok mit `url`, ohne Bild.
  - Kacheln ohne Bild entfallen.
  - Der Redaktion sagen: Für jeden der drei TikToks ein Coverbild hochladen.
- **Wann die Altschlüssel weg dürfen:** erst, wenn
  `SELECT ID FROM wp_posts WHERE post_content LIKE '%wp:kaz/social%' AND (post_content LIKE '%"post1%' OR post_content LIKE '%"tiktok1%')`
  nichts mehr liefert.

## Darstellung

- Raster unverändert, mobil 2 Spalten.
- Kachel als `<a target="_blank" rel="noopener">` um das Bild, mit
  `aspect-ratio: 1/1` und `object-fit: cover`.
- Plattform-Icon als Inline-SVG auf eigener Fläche (mindestens 3:1).
- `.kaz-reveal` in `editor.css` neutralisieren.
- Leere Liste `[]` → Sektion entfällt; nie gepflegte Liste → `seed`.

## Abnahme

Die Kriterien des Briefings. In edi automatisiert in `bin/checks/smoke.php`
(Prüfseite `_social`) und `bin/checks/browser.mjs` (Abschnitt Social-Feed).
