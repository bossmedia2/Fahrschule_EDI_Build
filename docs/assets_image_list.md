# Bildliste & Namensschema – Fahrschule edi

Das Theme liefert **keine** Fotos mehr mit. Bilder kommen aus der Mediathek und
finden ihren Platz allein über den Dateinamen: WordPress leitet daraus den Slug
des Anhangs ab, und genau den schlägt `fs_media_by_slug()` (`inc/media.php`)
nach. Wer eine Datei nach Schema hochlädt, sieht sie an ihrem Platz — ohne sie
irgendwo zuzuordnen und ohne Eingriff im Code.

**Auflösungsreihenfolge** je Schlüssel:

1. Anhang mit diesem Slug in der Mediathek
2. gleichnamige Datei in `assets/images/` (nur Logo, Partnerlogos, Favicons)
3. nichts → der Block blendet sich aus, statt eine leere Kachel zu zeigen

**Regeln für alle Dateinamen:** nur Kleinbuchstaben, Ziffern und Bindestriche.
Umlaute umschreiben (`Ömer` → `oemer`), keine Leerzeichen, keine Doppelpunkte,
kein `_wp` am Ende. Ein Anhang lässt sich nachträglich **nicht** umbenennen —
erst lokal umbenennen, dann einmal importieren, sonst hängt WordPress `-1` an.

---

## Schema

| Einsatzort | Schema | Beispiel |
|---|---|---|
| Logo | `edi-logo.webp` | liegt im Theme |
| Partnerlogo | `edi-partner-<name>` | `edi-partner-certqua` |
| Favicon | `edi-favicon-512.png` | 512×512, quadratisch |
| Fahrlehrer | `edi-team-<vorname>` | `edi-team-baris` |
| Fahrlehrer, zweites Foto | `edi-team-<vorname>-b` | `edi-team-baris-b` |
| Kurs-/Klassenbild | `edi-kurs-<seiten-slug>` | `edi-kurs-klasse-b` |
| Standortbild | `edi-standort-<ort>` | `edi-standort-duisburg` |
| Fuhrpark | `edi-fuhrpark-<art>` | `edi-fuhrpark-motorrad` |
| Räumlichkeiten | `edi-raum-<name>` | `edi-raum-klassenraum` |
| Hero | `edi-hero-<nr>` | `edi-hero-1` |
| Galerie | `edi-galerie-<nnn>` oder `edi-galerie-<kategorie>-<nnn>` | `edi-galerie-012`, `edi-galerie-fuhrpark-012` |
| Social-Feed-Kachel | `edi-social-<nnn>` | `edi-social-001` – quadratisch, mind. 1080 px |
| Teilen-Vorschau | `edi-og.jpg` | 1200×630 |

**Kursbilder laufen automatisch.** Der Teil nach `edi-kurs-` ist exakt der
Seiten-Slug. `/klasse-b` sucht `edi-kurs-klasse-b`, `/klasse-b197` sucht
`edi-kurs-klasse-b197`. Eine neue Klassenseite braucht damit keinen Eintrag
unten — Seite anlegen, Bild hochladen, fertig.

**Warum `-b` und nicht `-2` für das Zweitfoto:** WordPress hängt selbst `-2`
an, wenn ein Slug schon belegt ist. Ein versehentlich doppelt hochgeladenes
Porträt erschiene dann still als Wechselbild. `-b` erzeugt WordPress nie.

**Galerie:** Der Galerie-Block zeigt jedes Bild, dessen Slug mit
`edi-galerie-` beginnt, ohne Eintrag im Code. Eine Kategorie
(`edi-galerie-fuhrpark-012`) ist optional. Die Filter-Pillen erscheinen nur für
Kategorien, die tatsächlich Bilder haben. Unkategorisierte Bilder stehen
unter „Alle“.

**Social-Feed** (Block „Social-Feed (Instagram/TikTok)“, Seite Erfahrungen):
- **Kachel:** Jede Kachel ist eine Zeile im Wiederholfeld mit Plattform
  (`tiktok`/`instagram`), Bild-Slug, Link zum Beitrag und
  **Bildbeschreibung**.
- **Ohne Beschreibung keine Kachel:** Ohne Beschreibung erscheint die Kachel
  nicht, denn das Bild bräuchte sonst einen Alt-Text. Die Beschreibung sagt,
  was auf dem Bild zu sehen ist, nicht „Beitrag 3“.
- **Coverbild selbst hochladen:** Das Coverbild eines TikToks oder
  Instagram-Beitrags lädt jemand selbst hoch. Die Website ruft nichts bei
  TikTok oder Instagram ab und bettet nichts ein; ein Klick öffnet den
  Beitrag in einem neuen Tab.
- **Quadratisch:** Hochkant-Cover werden mittig auf 1:1 zugeschnitten.

---

## Umbenennen mit `bin/edi-bilder.py`

Das Skript benennt den Bildordner nach diesem Schema um. Es liegt im Repository
unter `bin/` und braucht nur Python 3; für `--verkleinern` zusätzlich Pillow.

```
cd "<Bildordner>"
python3 /pfad/zu/bin/edi-bilder.py                # nur umbenennen
python3 /pfad/zu/bin/edi-bilder.py --verkleinern  # zusätzlich lange Kante 2560 px
```

| Datei im Ordner | wird zu | Hinweis |
|---|---|---|
| `Fahrschule Edi <Name> Fahrlehrer <Ort> <Klassen>_wp.jpg` | `edi-team-<vorname>.jpg`, zweites Foto `-b` | Standort und Klassen stehen in `zuordnung.csv`, nicht im Namen |
| `Fahrschule edi <nr>_wp.jpg`, `Fs edi <nr>_wp.jpg` | `edi-galerie-<nnn>.jpg` | Quellnummer = Galerienummer, stabil über mehrere Läufe |
| `Fahrschule edi _wp.jpg` (ohne Nummer) | `edi-galerie-901.jpg` ff. | im Bericht genannt |
| `KAZ …` | – | übersprungen, anderes Projekt |
| `FFs …` / `FFS …` | – | übersprungen, Fahrlehrerfachschule, nicht die edi-Fahrschule |
| `iStock …` | – | ausgeschlossen, Lizenz ungeklärt |
| `edi-…` | – | schon nach Schema, übersprungen |

**Das Skript …**

- **kopiert nur.** Die Originale bleiben, alles landet in `umbenannt/`. Der
  Ordner `Umbenannt ` (großes U, Leerzeichen am Ende) wird weder gelesen noch
  beschrieben.
- **ist idempotent.** Ein zweiter Lauf legt nichts doppelt an. Eine Zieldatei
  mit anderem Inhalt überschreibt es nicht, sondern meldet sie.
- **verträgt die realen Schreibvarianten:**
  - Tippfehler `Fahrehrer`, `Fahrlhererin`, `fahrlehrer`
  - Doppelpunkte in den Klassen: `B:BE` → B, BE; `B:E` → BE
  - Komma oder Bindestrich als Trenner
  - `Ali Ihsan` als zweiter Vorname, `(Apo)` als Rufname, `Ömer . 2`
  - Mac-Umlaute (NFD); Namensvergleiche laufen über NFC
- **übersetzt Standortkürzel:** GE, DU, Essen. Ohne Kürzel bleibt der Standort
  leer, das Skript rät nicht.
- **schreibt `umbenannt/zuordnung.csv`** mit den Spalten
  `zielname;quelldatei;person;rolle;klassen;standort;hinweis`. Darin trägt man
  die Galerie-Motive für `edi-fuhrpark-*`, `edi-raum-*`, `edi-standort-*` und
  `edi-kurs-*` von Hand nach. Die CSV ist zugleich Nachweis, falls jemand
  seine Einwilligung widerruft.
- **schreibt `umbenannt/bericht.txt`.** Er enthält:
  - die Anzahl je Gruppe und alles Nicht-Zugeordnete;
  - Lücken in der Nummernreihe und Ausreißer (`446` → vermutlich 46);
  - eine Prüfschleife, ob die Slugs in der Mediathek frei sind;
  - die kopierbare `wp media import`-Zeile.
- **verkleinert mit `--verkleinern`:**
  - wendet die EXIF-Drehung an, kodiert neu und verwirft dabei die EXIF-Daten
    (Kamera, ggf. GPS);
  - behält das Original, wenn die neue Datei größer wäre.

Geprüft wird das Skript mit `python3 bin/checks/test_edi_bilder.py` an einem
nachgebauten Ordner mit allen oben genannten Sonderfällen, zweimal
hintereinander. Der Test läuft manuell, nicht im Gate: Das Skript ist ein
Werkzeug für den Bildordner, kein Teil von Theme oder Plugin.

---

## Belegte Schlüssel

Alle Werte stehen in `fahrschule_media_map()` (`inc/media.php`) und lassen sich
von außen überschreiben:
`add_filter( 'fahrschule_media', fn( $m ) => $m + [ 'logo' => 42 ] );`

| Schlüssel | Dateiname | Wo es erscheint |
|---|---|---|
| `logo` | `edi-logo` | Kopfzeile, Fußzeile |
| `logo_certqua` | `edi-partner-certqua` | Fußzeile, Zertifizierung |
| `logo_schalke` | `edi-partner-schalke` | Fußzeile |
| `loc_duisburg` | `edi-standort-duisburg` | Standortseite Duisburg |
| `loc_essen` | `edi-standort-essen` | Standortseite Essen |
| `loc_gelsenkirchen` | `edi-standort-gelsenkirchen` | Standortseite Gelsenkirchen |
| `fleet_pkw` | `edi-fuhrpark-pkw` | Fuhrpark & Einrichtungen |
| `fleet_lkw` | `edi-fuhrpark-lkw` | Fuhrpark & Einrichtungen |
| `fleet_motorrad` | `edi-fuhrpark-motorrad` | Fuhrpark & Einrichtungen |
| `fleet_bus` | `edi-fuhrpark-bus` | Fuhrpark & Einrichtungen |
| `fac_klassenraum` | `edi-raum-klassenraum` | Fuhrpark & Einrichtungen |
| `fac_empfang` | `edi-raum-empfang` | Fuhrpark & Einrichtungen |
| `fac_buero` | `edi-raum-buero` | Fuhrpark & Einrichtungen |
| `video_hero` | `edi-video-hero` | Startseite, Hero-Video (MP4) |
| `video_hero_webm` | `edi-video-hero-webm` | Startseite, Hero-Video (WebM, optional) |
| `video_hero_bild` | `edi-video-hero-standbild` | Startseite, Standbild: Poster, Hintergrund, Editor, reduzierte Bewegung |
| `video_team` | `edi-video-team` | Team-Spirit-Abschnitt |
| `grad_auto` … `grad_motorrad` | `edi-galerie-absolventen-001` … `-003` | Absolventen-Galerie |

---

## Maße und Formate

Eine Datei je Platz in dieser Größe hochladen; die kleineren Varianten erzeugt
WordPress selbst.

| Einsatzort | Maße | Verhältnis | Hinweis |
|---|---|---|---|
| Hero | 1920×1080 | 16:9 | linke Hälfte ruhig halten, dort steht die Schrift |
| Fahrlehrer-Porträt | 800×1000 | 4:5 | Hochformat, Kopf im oberen Drittel |
| Kurs-/Klassenbild | 800×600 | 4:3 | Motiv mittig, die Karte beschneidet die Ränder |
| Standortbild | 1200×800 | 3:2 | Gebäude vollständig im Bild |
| Galerie-Kachel | 1200×1200 | 1:1 | Lightbox nutzt dieselbe Datei |
| Favicon | 512×512 | 1:1 | PNG, Mindestmaß von WordPress |
| Teilen-Vorschau | 1200×630 | 1,91:1 | Logo nicht an den Rand setzen |

WebP mit JPG als Rückfall, Qualität um 80. Hero unter 250 KB, alles andere
unter 120 KB. Keine Kameraoriginale hochladen.

Bei den 17 Fahrlehrer-Porträts auf **einheitlichen Bildausschnitt** achten —
nebeneinander im Raster fällt sofort auf, wenn mal Brustbild und mal
Ganzkörper steht.

---

## Alt-Texte

Beschreiben, was zu sehen ist, nicht dass es ein Bild ist. Bei einer Fahrschule
gehört der Ort dazu, weil die Suche fast immer lokal ist.

| Bildart | Muster | Beispiel |
|---|---|---|
| Fahrlehrer | Name, Rolle, Standort | Baris, Fahrlehrer Klasse B in Gelsenkirchen |
| Klassenbild | Tätigkeit und Situation | Fahrschüler bei der Rangierübung mit Anhänger |
| Standort | Was und wo | Fahrschule edi, Filiale Duisburg, Außenansicht |
| Galerie | kurze Beschreibung der Szene | Fahrschulwagen auf dem Übungsplatz |
| Logo | Firmenname | Fahrschule Edi |

---

## Vor dem Import klären

Diese Punkte liegen außerhalb des Themes und blockieren sonst die Bebilderung:

- **Einwilligungen für minderjährige Fahrschüler.** Deren eigene Zustimmung
  genügt nicht; es braucht die der Erziehungsberechtigten. Betrifft potenziell
  die Mehrheit der Impressionen. Ohne Nachweis bleiben Aufnahmen ohne
  erkennbare Gesichter: Fahrzeuge, Übungsplatz, Räume, Detailaufnahmen.
- **Vollständige Namen der 17 Fahrlehrer**, Standort von Abdullah und Mesut.
- **Motorrad- und Anhängermotive** – ob welche im Bestand sind, ist offen.
  Fehlen sie, fehlen Bilder für genau die Seiten, die edi von einer reinen
  Pkw-Fahrschule unterscheiden.
- **Tippfehler in den Dateinamen** (`Fahrehrer`, `Fahrlhererin`) und
  Doppelpunkte in Klassenangaben: Das erledigt `bin/edi-bilder.py`. Deshalb nur
  die Dateien aus `umbenannt/` importieren, nie die Originale, sonst wandern
  die Tippfehler über die automatische Titel-Erzeugung auf die Website.
- **Nummer 446:** vermutlich ein Tippfehler für 46. Das Skript übernimmt die
  Nummer unverändert und meldet sie; ob umbenannt wird, entscheidet ihr.
