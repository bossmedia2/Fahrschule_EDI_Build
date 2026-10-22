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
| Galerie | `edi-galerie-<kategorie>-<nnn>` | `edi-galerie-fahrstunde-012` |
| Teilen-Vorschau | `edi-og.jpg` | 1200×630 |

**Kursbilder laufen automatisch.** Der Teil nach `edi-kurs-` ist exakt der
Seiten-Slug. `/klasse-b` sucht `edi-kurs-klasse-b`, `/klasse-b197` sucht
`edi-kurs-klasse-b197`. Eine neue Klassenseite braucht damit keinen Eintrag
unten — Seite anlegen, Bild hochladen, fertig.

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
| `video_hero` | `edi-video-hero` | Startseite, Hero-Video |
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
  Doppelpunkte in Klassenangaben vor dem Import korrigieren – sonst wandern
  sie über die automatische Alt-Text-Erzeugung auf die Website.
