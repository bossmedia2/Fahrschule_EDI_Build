# Bild- & Video-Platzhalterliste – Fahrschule edi

Alle Medien dieses Themes laufen über **eine** zentrale Registry (`inc/media.php`,
Funktion `fahrschule_media_map()`) und werden ausschließlich über `fs_media( $key )`
ausgegeben. Diese Liste deckt jeden dort definierten Schlüssel ab: wo er auf der
Website erscheint, wie die endgültige Bilddatei heißen sollte, wofür sie steht
(inkl. Alt-Text-Vorschlag) und in welcher Auflösung sie mindestens vorliegen sollte.

**Benennungsschema:** Lokale Dateien liegen unter `assets/images/` (bzw. künftig
`assets/video/` für Videos) und folgen durchgängig dem Muster
`registry-schluessel-mit-strichen.endung` – z. B. wird der Registry-Schlüssel
`loc_duisburg` zur Datei `loc-duisburg.jpg`. Spalte 2 nennt für jede Zeile den
konkreten Zieldateinamen nach diesem Muster. Sobald eine Datei unter diesem Namen
in `assets/images/` liegt (oder stattdessen eine Attachment-ID in der Mediathek
hinterlegt wird), genügt ein Eintrag in `inc/media.php` – kein Template-Eingriff
nötig (siehe Kommentar am Kopf dieser Datei).

**Status-Spalte:** Markiert, welche Schlüssel aktuell noch auf externen
Prototyp-Speichern liegen (`vercel-storage.com` bzw. `unsplash.com`) und vor dem
Livegang ersetzt werden müssen – siehe die Admin-Warnung in
`fahrschule_media_notice()` (`inc/media.php:121-135`), die exakt dieselben
Schlüssel zählt.

---

| Seitenname/Sektion | Platzhalterbild-Benennungsschema | Verwendungszweck + Alt-Text-Vorschlag | benötigte Bildauflösung | Status Bildquelle |
|---|---|---|---|---|
| Header/Footer – Logo (`logo`) | `logo-edi.png` | Haupt-Logo in der Navigation (Startlink) und als Favicon-Basis. Alt: „Fahrschule & Fahrlehrerfachschule edi" | mind. 400×133 px, PNG mit Transparenz, Seitenverhältnis ca. 3:1 | Lokal vorhanden |
| Footer – Zertifikats-Badge (`logo_certqua`) | `logo-certqua.png` | CERTQUA-Zertifizierungs-Badge im Footer, neben „Zertifiziert"-Schriftzug. Alt: „CERTQUA-Zertifizierung" | mind. 240×96 px, PNG mit Transparenz | Lokal vorhanden |
| Footer – Partner-Logo (`logo_schalke`) | `logo-schalke.svg` | Schalke-Kooperationslogo im Footer, neben dem CERTQUA-Badge. Bewusst `alt=""` (dekorativ, Kontext steht im Fließtext daneben) | mind. 96×96 px, SVG bevorzugt (sonst PNG mit Transparenz) | Lokal vorhanden |
| Startseite – Video-Hero (`video_hero`) | `video-hero.mp4` | Vollflächiges Hintergrundvideo im Hero-Bereich der Startseite (`fs_render_video_hero`), stumm/autoplay/loop. Beschriftung über `aria-label`: „Imagevideo der Fahrschule edi" | mind. 1920×1080 px (Full HD), MP4/H.264, 16:9, Dateigröße möglichst < 15 MB | **Extern (Vercel Blob) – ersetzen** |
| Startseite – Team-Spirit-Video (`video_team`) | `video-team.mp4` | Video im Split-Layout der „Team Spirit"-Sektion (`fs_render_team_spirit`), 16:9-Rahmen, nicht vollflächig | mind. 1280×720 px, MP4/H.264, 16:9 | **Extern (Vercel Blob) – ersetzen** |
| Standorte – Foto Duisburg (`loc_duisburg`) | `loc-duisburg.jpg` | Außenansicht auf der Standorte-Übersicht + Standortbild auf `/duisburg/`. Alt: „Fahrschule edi Standort Duisburg – Außenansicht" | mind. 1200×800 px, Querformat 3:2 | **Extern (Vercel Blob) – ersetzen** (einziger Standort ohne lokales Originalfoto) |
| Standorte – Foto Essen (`loc_essen`) | `loc-essen.jpg` | Außenansicht auf der Standorte-Übersicht + Standortbild auf `/essen/`. Alt: „Fahrschule edi Standort Essen – Außenansicht" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden (aber: nur 1 Foto – Detailseite nutzt sonst ein Duisburg-Innenraumfoto als zweites Bild, siehe FARBSYSTEM.md §6) |
| Standorte – Foto Gelsenkirchen (`loc_gelsenkirchen`) | `loc-gelsenkirchen.jpg` | Außenansicht auf der Standorte-Übersicht + Standortbild auf `/gelsenkirchen/`. Alt: „Fahrschule edi Standort Gelsenkirchen – Außenansicht" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden (gleiche Einschränkung wie Essen) |
| Moderne Ausbildung – Flotte PKW/LKW (`fleet_pkw`) | `fleet-pkw.jpg` | Fotokachel „Fahrzeugflotte" in Flotte & Einrichtungen. Alt: „Fahrzeugflotte der Fahrschule edi – PKW & LKW" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Moderne Ausbildung – LKW-Ausbildung (`fleet_lkw`) | `fleet-lkw.jpg` | Fotokachel „Lkw-Ausbildung". Alt: „Lkw-Ausbildung der Fahrschule edi – C, C1, CE Klassen" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Moderne Ausbildung – Motorradschule (`fleet_motorrad`) | `fleet-motorrad.jpg` | Fotokachel „Motorradschule". Alt: „Motorradschule der Fahrschule edi – A, A1, AM Klassen" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Moderne Ausbildung – Schulungsbus (`fleet_bus`) | `fleet-bus.jpg` | Fotokachel „Schulungsbus". Alt: „Schulungsbus der Fahrschule edi – Theorieunterricht & Events" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Moderne Ausbildung – Klassenraum (`fac_klassenraum`) | `fac-klassenraum.jpg` | Fotokachel „Modernstes Klassenzimmer" in Einrichtungen. Alt: „Modernstes Klassenzimmer der Fahrschule edi – Technisch ausgestattet" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Moderne Ausbildung – Empfang (`fac_empfang`) | `fac-empfang.jpg` | Fotokachel „Willkommensbereich"; dient zusätzlich als Platzhalter-Zweitbild auf allen drei Standort-Detailseiten. Alt: „Willkommensbereich der Fahrschule edi – LED Begrüßungsbildschirm" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Moderne Ausbildung – Büro (`fac_buero`) | `fac-buero.jpg` | Fotokachel „Modernes Büro" in Einrichtungen. Alt: „Modernes Büro der Fahrschule edi – Mit Mitarbeiterzonen" | mind. 1200×800 px, Querformat 3:2 | Lokal vorhanden |
| Erfahrungen – Absolvent Auto (`grad_auto`) | `grad-auto.jpg` | Bildkachel in der Absolventen-Galerie. Alt: „Mit dem Führerschein zum Erfolg – Absolvent der Fahrschule edi" | mind. 1000×1000 px, quadratisch 1:1 | **Extern (Vercel Blob) – ersetzen** |
| Erfahrungen – Absolvent jung (`grad_jung`) | `grad-jung.jpg` | Bildkachel in der Absolventen-Galerie. Alt: „Gemeinsam zum Ziel – Absolvent der Fahrschule edi" | mind. 1000×1000 px, quadratisch 1:1 | **Extern (Vercel Blob) – ersetzen** |
| Erfahrungen – Absolvent Motorrad (`grad_motorrad`) | `grad-motorrad.jpg` | Bildkachel in der Absolventen-Galerie. Alt: „Auch Motorrad erfolgreich bestanden – Absolvent der Fahrschule edi" | mind. 1000×1000 px, quadratisch 1:1 | **Extern (Vercel Blob) – ersetzen** |
| Team – Büro & Ansprechpartner (`team_buero`) | `team-buero.jpg` | Foto der Gruppe „Büro & Ansprechpartner (Essen, Gelsenkirchen, Duisburg)" auf der Team-Seite (PDF-Abschnitt F9, dort mit „(Fotos)" markiert). Alt-Vorschlag: „Büro & Ansprechpartner der Fahrschule edi" | mind. 800×800 px, quadratisch 1:1 (Karte schneidet quadratisch zu) | **Fehlt komplett** – Initialen-Fallback „BA" (bewusst kein Stockfoto) |
| Team – Geschäftsleitung (`team_geschaeftsleitung`) | `team-geschaeftsleitung.jpg` | Foto der Gruppe „Geschäftsleitung (Essen, Gelsenkirchen, Duisburg)"; die Namen (Gülsüm Alkan, Robert Peter Fiedler, Rainer Fritz Erich Grunwald – laut Impressum) stehen bereits als Text in der Karte. Alt-Vorschlag: „Geschäftsleitung der Fahrschule edi" | mind. 800×800 px, quadratisch 1:1 | **Fehlt komplett** – Initialen-Fallback „G" |
| Team – Dozenten & Ausbilder (`team_dozenten`) | `team-dozenten.jpg` | Foto der Gruppe „Dozenten & Ausbilder (Essen, Gelsenkirchen, Duisburg)". Alt-Vorschlag: „Dozenten & Ausbilder der Fahrschule edi" | mind. 800×800 px, quadratisch 1:1 | **Fehlt komplett** – Initialen-Fallback „DA" |
| Startseite/Erfahrungen – Instagram-Slider (`ig_1` … `ig_6`, 6 Plätze) | `ig-1.jpg` … `ig-6.jpg` | 6 Bildkacheln im Instagram-Slider, je mit eigener Caption (z. B. „Endlich bestanden!…"). Alt-Text = jeweilige Caption (bereits im Code als Alt-Text verwendet) | mind. 1000×1000 px, quadratisch 1:1 (Instagram-Originalformat) | **Extern (Unsplash-Stockfotos) – keine echten Kanalinhalte, ersetzen** |

---

## Zusammenfassung „noch zu ersetzen"

Die Tabelle fasst 27 Registry-Schlüssel in 22 Zeilen zusammen (die 6
Instagram-Plätze `ig_1`–`ig_6` stehen als eine Zeile). Davon liegen aktuell
**15 Plätze** noch nicht final vor:

- **Extern, muss vor Livegang ersetzt werden (12 Plätze):** `video_hero`,
  `video_team`, `loc_duisburg`, `grad_auto`, `grad_jung`, `grad_motorrad`,
  `ig_1`–`ig_6`.
- **Fehlt komplett, bislang nur Initialen-Fallback (3 Plätze):** `team_buero`,
  `team_geschaeftsleitung`, `team_dozenten` (je ein Foto pro Team-Gruppe laut
  PDF-Abschnitt F9; ersetzen die früheren Einzelporträts `team_alkan`,
  `team_fiedler`, `team_grunwald`).

Alle übrigen Plätze liegen bereits lokal im Theme (`assets/images/`) und müssen
nicht ausgetauscht werden – sollen aber bei Gelegenheit gegen hochauflösendere
Originalfotos (siehe Auflösungsspalte) ausgetauscht werden, falls vorhanden.

Sobald echte Dateien vorliegen: einfach in die WordPress-Mediathek hochladen und
den jeweiligen Schlüssel in `inc/media.php` auf die neue URL oder Attachment-ID
umstellen (siehe Kommentar am Kopf der Datei) – kein weiterer Codeeingriff nötig.
