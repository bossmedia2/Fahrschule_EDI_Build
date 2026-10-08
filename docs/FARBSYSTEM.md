# Farbsystem – Fahrschule edi

Vollständige Übersicht aller Farb-Variablen, der Flächen-Logik und der
Kontrastnachweise. Ergebnis der Vereinheitlichung: **keine Farbverläufe,
keine Glaseffekte, 13 Basisfarben, alle Text/Hintergrund-Paare WCAG-AA-konform.**

---

## 1. Warum der Umbau nötig war

Der Prototyp nutzte `#fce4ed` – ein sehr **helles** Rosa – als Hintergrund.
Sämtliche Textfarben (Bordeaux-Überschriften `#8a1245`, hellblauer Fließtext
`#D5E8F0`) waren für diesen hellen Grund gedacht.

Die Vorgabe, den pinken Hintergrundton global auf **`#d12487`** zu ändern, hat
diese Grundlage umgedreht: `#d12487` ist ein **dunkler, satter** Ton. Die
Textfarben blieben, der Untergrund wurde dunkel – mit messbaren Folgen:

| Kombination | Kontrast (alt) | Bewertung |
|---|---|---|
| Eyebrow `#e62775` auf `#d12487` | **1,14 : 1** | praktisch unsichtbar |
| Footer-Text `#D5E8F0` auf Weiß | **1,26 : 1** | unlesbar |
| Karten-Subtext auf heller Karte | **1,30 : 1** | unlesbar |
| Überschrift `#8a1245` auf Navy | **1,89 : 1** | unlesbar |
| Sektionsüberschrift auf `#d12487` | **1,91 : 1** | unlesbar |
| Primär-Button-Beschriftung | **2,18 : 1** | zu schwach |

Insgesamt **14 von 18 geprüften Kombinationen** lagen unter dem
AA-Mindestwert von 4,5 : 1. Dazu kamen 10 Farbverläufe, 74 verschiedene
`rgba()`-Werte und mehrere `backdrop-filter`-Glaseffekte, die dieselbe
Komponente je nach Platzierung unterschiedlich einfärbten.

---

## 2. Das neue System: Flächen bestimmen die Textfarbe

Statt Farben pro Komponente festzulegen, definiert die **Fläche** (Surface)
ihren Hintergrund *und* die dazu passenden Textfarben. Komponenten erben diese
über Kontext-Variablen und sind dadurch in jedem Umfeld automatisch lesbar.

```
Basis-Palette  (12 feste Farbwerte, --fs-*)
      ↓
Flächen        (.fs-surface-brand | -light | -baby)  belegen --c-* neu
      ↓
Komponenten    nutzen ausschließlich --c-*  (nie die Basis-Palette)
      ↓
Karten         schalten den Kontext erneut um (heller Block auf farbiger Fläche)
```

> **Stand nach dem Phase-1-Umbau** (Abschnitt 8): keine dunkle/blaue Fläche
> mehr. Die ursprüngliche dunkle Fläche (`.fs-surface-dark`, Navy `#0a192f`)
> ist entfernt; an ihrer Stelle gibt es `.fs-surface-baby` – eine **helle**
> vierte Fläche in einem sanften Blauton aus dem echten EDI-Logo.

### Basis-Palette (`--fs-*`)

| Variable | Wert | Verwendung |
|---|---|---|
| `--fs-brand` | `#d12487` | Signatur-Hintergrund (Kundenvorgabe) |
| `--fs-brand-accent` | `#e62775` | Akzentflächen, Icons, Rahmen – **nie** kleiner Text |
| `--fs-brand-strong` | `#b81d5e` | Button-Füllung, Akzenttext auf hellem Grund, zweite dunkle UI-Stufe |
| `--fs-brand-deep` | `#6d0d45` | Text/Füllung auf hellen Pinkflächen, dunkelste UI-Stufe |
| `--fs-heading` | `#8a1245` | Überschriften auf hellem Grund |
| `--fs-light` | `#ffffff` | helle Fläche |
| `--fs-tint` | `#fdeaf3` | zarte Pinkfläche (Karten auf Hell), Hover-Akzent auf dunklen UI-Elementen |
| `--fs-baby` | `#deecf6` | sanfte Fläche aus dem Logo-Blau (Abschnitt 8) |
| `--fs-baby-accent` | `#257fc2` | echtes Logo-Blau – Icons/Rahmen auf Baby-Blau, **nie** kleiner Text |
| `--fs-on-light` | `#3a3a44` | Fließtext auf Hell und auf Baby-Blau |
| `--fs-scrim` | `rgba(0,0,0,.55)` | einheitlicher Abdunkler über Fotos |

### Kontext-Variablen (`--c-*`, 13 Stück)

`--c-bg` · `--c-text` · `--c-heading` · `--c-accent` · `--c-border`
`--c-card` · `--c-card-text` · `--c-card-heading` · `--c-card-accent` · `--c-card-border`
`--c-btn-bg` · `--c-btn-text` · `--c-btn-bg-hover`

### Die vier Flächen

| Klasse | Hintergrund | Text | Überschrift | Akzent | Karte |
|---|---|---|---|---|---|
| `.fs-surface-brand` | `#d12487` | Weiß | Weiß | Weiß | Weiß, Text `#3a3a44` |
| `.fs-surface-light` | `#ffffff` | `#3a3a44` | `#8a1245` | `#b81d5e` | `#fdeaf3` |
| `.fs-surface-baby` | `#deecf6` | `#3a3a44` | `#8a1245` | `#257fc2` | Weiß, Text `#3a3a44` |

> **Hinweis zur Brand-Fläche:** Auf `#d12487` erreicht selbst reines Weiß nur
> 4,87 : 1. Damit ist AA erfüllt, aber ohne Reserve – deshalb gibt es dort
> bewusst **nur eine Textfarbe** (Weiß). Eine „gedämpfte" Stufe wäre nicht mehr
> konform. Für längere Fließtexte sind die hellen Flächen die bessere Wahl.

---

## 3. Kontrastnachweis (aus dem CSS ausgelesen, nicht geschätzt)

| Fläche | Element | Kontrast | AA |
|---|---|---|---|
| **brand** | Fließtext / Überschrift / Akzent | 4,87 : 1 | ✓ |
| brand | Karten-Text | 11,24 : 1 | ✓ |
| brand | Karten-Überschrift | 9,32 : 1 | ✓ |
| brand | Button-Label | 11,71 : 1 | ✓ |
| **light** | Fließtext | 11,24 : 1 | ✓ |
| light | Überschrift | 9,32 : 1 | ✓ |
| light | Akzent | 6,22 : 1 | ✓ |
| light | Karten-Akzent | 5,40 : 1 | ✓ |
| light | Button-Label | 6,22 : 1 | ✓ |
| **baby** | Fließtext | 9,33 : 1 | ✓ |
| baby | Überschrift | 7,74 : 1 | ✓ |
| baby | Karten-Text | 11,24 : 1 | ✓ |
| baby | Button-Label | 6,22 : 1 | ✓ |

**21 von 21 Paaren erfüllen WCAG 2.1 AA (≥ 4,5 : 1).** Details zur
Baby-Fläche und zur Entfernung der dunklen Fläche: Abschnitt 8.

---

## 4. Entfernt

| Was | Vorher | Jetzt |
|---|---|---|
| Farbverläufe (CSS) | 10 | **0** |
| Farbverlauf (JavaScript, Slider) | 1 | **0** – `accent-color` |
| `backdrop-filter` (Glaseffekt) | 9 | **0** |
| Dekorative Blur-Kreise (`.fs-blob`) | 14 im Markup | **0** |
| Verschiedene `rgba()`-Werte | 37 | 6 (nur Schatten + Foto-Scrim) |
| Farbwerte direkt im PHP-Markup | 20 | **0** |
| Basis-Farbvariablen | 17 (3 ungenutzt) | **14, alle genutzt** |

Die Blur-Kreise und Glaseffekte sind bewusst entfallen: Sie legten je nach
Sektion andere Tönungen über dieselbe Komponente – genau das Gegenteil eines
einheitlichen Schemas. Fotos erhalten stattdessen einen einzigen, überall
gleichen Abdunkler (`--fs-scrim`).

---

## 5. Farbe ändern – so geht's

**Signaturfarbe tauschen:** einen Wert in `assets/css/main.css` (`:root`) ändern.
Alle Flächen, Karten, Buttons und Rahmen ziehen automatisch nach.

**Wichtig:** Wird `--fs-brand` deutlich heller gesetzt, muss auf der
Brand-Fläche die Textfarbe von Weiß auf einen dunklen Ton wechseln – die
entsprechende Stelle ist im CSS kommentiert (`.fs-surface-brand`).

Ergänzend liegt die Palette in `theme.json`, damit die Farben auch im
Gutenberg-Editor als Auswahl erscheinen.

---

## 6. Medien: offener Punkt vor dem Livegang

Beim Abgleich fiel auf, dass **alle 30 Bilder und Videos** noch auf dem
Speicher des v0-/Vercel-Prototyps bzw. auf Unsplash liegen. Diese Adressen
gehören nicht der Fahrschule edi und können jederzeit verschwinden; zusätzlich
laden sie Daten von Dritt-Servern (DSGVO).

Alle Medien laufen jetzt über **eine** Registry: `inc/media.php`. Zum Umstellen
genügt es, dort je Schlüssel die neue URL oder die Attachment-ID einzutragen –
kein Eingriff in Templates nötig. Solange externe Adressen hinterlegt sind,
zeigt WordPress im Adminbereich einen Warnhinweis.

**Fehlende Medien:**

| Medium | Status |
|---|---|
| Team-Fotos (Büro, Geschäftsleitung, Dozenten) | fehlen – laut PDF vorgesehen. Solange nichts hinterlegt ist, zeigt der Team-Block die Initialen (kein fremdes Stockfoto). |
| Instagram-Beiträge | aktuell Stockfotos von Unsplash, keine echten Kanalinhalte |
| Standort-Fotos Essen/Gelsenkirchen | nur je ein Bild; die Detailseiten nutzen ersatzweise ein Innenraumfoto aus Duisburg |

---

## 7. Nachprüfung (unabhängiger Kontroll-Lauf)

Das gesamte System wurde noch einmal automatisiert gegengeprüft – Variablen,
Farbwerte, Flächen, Kontraste, Links und Medien. Dabei gefunden und behoben:

| Befund | Auswirkung | Behebung |
|---|---|---|
| `#ffd0e4` stand zweimal roh im CSS (Button-Hover auf Dunkel) | letzter Farbwert außerhalb der Palette – hätte sich bei einem Farbwechsel nicht mitgeändert | neues Token `--fs-on-dark-accent-hi` |
| `404.php` nutzte die Flächenklasse `fs-sec--bg`, die es nicht mehr gibt | Fehlerseite ohne definierte Fläche – Textfarben nur zufällig korrekt | auf `fs-surface-brand` umgestellt |
| `#ffffff` stand 13× direkt im Stylesheet | Weiß hätte sich bei einem Palettenwechsel nicht mitgeändert | durchgängig auf `var(--fs-light)` umgestellt |

Bestätigt ohne Beanstandung:

- **Kontraste** – alle 21 Paare nachgerechnet (WCAG-Formel, nicht geschätzt);
  Werte identisch mit der Tabelle in Abschnitt 3, alle ≥ AA.
- **Farbverläufe** – 0 im gesamten Stylesheet.
- **Hartkodierte Farben außerhalb `:root`** – **0**. Jeder Farbwert des Themes
  steht ausschließlich in der Basis-Palette.
- **Inline-Styles im PHP** – 25 Stück, ausnahmslos über `var(--c-*)`.
- **Flächenklassen** – CSS und PHP deckungsgleich (`brand`, `light`, `dark`),
  keine definierte Fläche ungenutzt, keine benutzte Fläche undefiniert.
- **Ungenutzte CSS-Variablen** – keine.

### Links & Erreichbarkeit

- 20 Seiten in der Registry, **16 `fs_url()`-Ziele – alle gültig**.
- **Keine Onepage-Anker mehr** (einzige Ausnahme: der Skip-Link `#content`).
- **Keine verwaiste Seite**: jede der 20 Seiten ist über das Menü oder eine
  direkte Verlinkung erreichbar. `anmeldung`, `impressum` und `datenschutz`
  stehen bewusst nicht im Hauptmenü, sind aber 5- bzw. 1-fach verlinkt.
- Menüstruktur: Ausbildung (4) · Beruf & Weiterbildung (3) · Standorte (4) ·
  Über uns (3) · FAQ · Kontakt.
- 34 Blöcke registriert, 34 referenziert – keine Referenz ins Leere.

### Bilder

- **Kein Bild ohne Alt-Text.** Ein dekoratives Logo trägt bewusst `alt=""`.
- Alle 23 Bildausgaben laufen über `fs_media()`; im Markup steht keine URL mehr.
- Die drei Team-Fotos sind bewusst leer – bis echte Fotos vorliegen, zeigt die
  Karte die Initialen. Kein defektes Bild, sondern ein sauberer Fallback.

---

## 8. Phase-1-Umbau: Navy entfernt, Baby-Blau eingeführt (Folgeauftrag)

> **Nachtrag (späterer Folgeauftrag):** Die in diesem Abschnitt beschriebene
> Baby-Blau-Fläche wurde auf ausdrücklichen, bestätigten Kundenwunsch wieder
> vollständig entfernt – die Vorgabe lautet jetzt strikt **nur noch
> Pink/Pink-Weiß, keine Blautöne, keine Ausnahme**. Die Klasse
> `.fs-surface-baby` sowie die Tokens `--fs-baby`, `--fs-baby-card` und
> `--fs-baby-accent` existieren im Code nicht mehr; die drei unten genannten
> Anwendungsstellen (Fahrzeugflotte/Einrichtungen, Instagram-Slider,
> Standort-Rechner) laufen jetzt über `.fs-surface-light` bzw.
> `.fs-surface-brand`. Dieser Abschnitt bleibt nur als historische
> Dokumentation des Phase-1-Umbaus erhalten und beschreibt bewusst nicht
> mehr den aktuellen Stand.
>
> Ebenfalls im selben Folgeauftrag behoben: `rgba(10,25,47,…)` (alte
> Navy-Farbe `#0a192f`, dezimal) stand noch 7× unentdeckt in
> `assets/css/main.css` (Schatten/Rahmen) – umgestellt auf die Dezimalform
> von `--fs-brand-deep` (`rgba(109,13,69,…)`).

Zwei Vorgaben aus dem Folgeauftrag: (1) verbliebene blaue/dunkelblaue Flächen
entfernen, ersetzt durch Weiß oder Pink; (2) ein sanftes Baby-Blau **aus dem
echten EDI-Logo** als weiche Highlight-Fläche einführen – als Alternative zu
reinem Weiß, nicht als Ersatz für die entfernte dunkle Fläche.

### Herkunft des Baby-Blaus

Das Logo (`logo_edi-1.png`) wurde pixelweise ausgewertet (Python/Pillow, alle
deckenden Pixel gezählt) statt die Farbe nach Auge zu schätzen:

```
#ffffff  13.687 Pixel  (Weiß)
#257fc2  10.144 Pixel  (Logo-Blau, die Flagge hinter dem Schriftzug)
#dd147b/#de147c   …    (Pink-Cluster, deckungsgleich mit --fs-brand/-accent)
```

`#257fc2` ist selbst kein Pastellton, sondern ein sattes Signalblau – als
Flächenhintergrund zu kräftig, als Fließtext-Grundlage aber ideal für einen
**Tint** (denselben Ansatz nutzt das System bereits bei `--fs-tint`, einem
Tint von `--fs-brand`). Verrechnet zu 15 % Logo-Blau / 85 % Weiß:

```
--fs-baby: #deecf6   (t = 0.15 · #257fc2 + 0.85 · #ffffff)
```

Das reine Logo-Blau bleibt als `--fs-baby-accent: #257fc2` erhalten – für
Icons, Rahmen und größere Akzentflächen auf der Baby-Fläche, **nie für
Fließtext** (4,29 : 1 auf Weiß, 3,56 : 1 auf Baby-Blau – beides unter dem
Text-Minimum von 4,5 : 1, aber über dem UI-Komponenten-Minimum von 3 : 1;
gleiche Einschränkung gilt bereits für `--fs-brand-accent`).

### Die vierte Fläche: `.fs-surface-baby`

Ersetzt **keine** der drei bisherigen Flächen, sondern ergänzt sie. Wie
`.fs-surface-light` eine **helle** Fläche (dunkler Text), nur mit einem
anderen Unterton – und einem eigenen Akzent (`--fs-baby-accent` statt
`--fs-brand-strong`), damit sie sich auch optisch von der reinen Weißfläche
abhebt.

| Klasse | Hintergrund | Text | Überschrift | Akzent | Karte |
|---|---|---|---|---|---|
| `.fs-surface-baby` | `#deecf6` | `#3a3a44` | `#8a1245` (Bordeaux, wie überall) | `#257fc2` | Weiß, Text `#3a3a44` |

Kontraste (WCAG-Formel, nicht geschätzt):

| Element | Kontrast | AA |
|---|---|---|
| Fließtext auf Baby | 9,33 : 1 | ✓ |
| Überschrift auf Baby | 7,74 : 1 | ✓ |
| Karten-Text (Karte ist Weiß) | 11,24 : 1 | ✓ |
| Button-Label (Button bleibt Pink-Kräftig/Weiß, wie auf `.fs-surface-light`) | 6,22 : 1 | ✓ |

### `.fs-surface-dark` entfernt – wohin die sechs Abschnitte gewandert sind

Es gab keinen pauschalen Ersatz „dark → eine neue Farbe", sondern pro
Abschnitt die inhaltlich passendere von Weiß/Pink/Baby-Blau:

| Abschnitt | Vorher | Jetzt | Warum |
|---|---|---|---|
| Fahrzeugflotte & Einrichtungen (Fotokacheln) | dark | **baby** | ruhiger Untergrund, lässt Fotos wirken |
| Instagram-Slider (Fotokacheln) | dark | **baby** | wie oben |
| Standort-Hero (Duisburg/Essen/Gelsenkirchen) | dark | **brand** | größte Bühne der Seite – volle Markenpräsenz |
| Standort-Inhalt → eingebettete Bewertungen | dark | **light** | deckt sich mit den Bewertungen auf der Startseite (dort bereits `light`) |
| Standort-Rechner (Slider-Tool) | dark | **baby** | ruhige, fokussierte Fläche für ein Eingabe-Werkzeug, optische Abgrenzung zum Pink-Hero direkt darüber |
| Berufskraftfahrer (Lauftext, PDF-Custom-Block) | dark | **light** | deckt sich mit „Moderne Ausbildung", dem inhaltlich nächsten Custom-Block (dort bereits `light`) |

### Komponenten-Ebene: wo „dunkel" nur als UI-Akzent diente

Sechs Abschnitte ändern die Fläche – daneben griffen rund zwanzig einzelne
Komponentenregeln (mobiles Menü, Breadcrumbs, Footer-Trennlinie, Timeline-Punkt,
Team-Karten, Slider-Regler, TikTok-Button u. a.) direkt auf `--fs-dark`/
`--fs-dark-2`/`--fs-on-dark*` zu, unabhängig von den Flächen-Klassen. Diese
Fundstellen wurden **nicht** übersehen, sondern bewusst auf die
dunkelste/zweitdunkelste Stufe der bestehenden Pink-Palette umgehängt, statt
einen weiteren, ähnlich klingenden Farbtopf einzuführen:

| Alt | Neu | Kontrast (Text, wo zutreffend) |
|---|---|---|
| `--fs-dark` (`#0a192f`) | `--fs-brand-deep` (`#6d0d45`) | – (Flächenfarbe) |
| `--fs-dark-2` (`#13283e`) | `--fs-brand-strong` (`#b81d5e`) | – (Flächenfarbe) |
| `--fs-on-dark` (`#d5e8f0`) | `--fs-light` (`#ffffff`) | 11,71 : 1 auf `--fs-brand-deep` |
| `--fs-on-dark-accent` / `-hi` (`#ff8fc4` / `#ffd0e4`) | `--fs-tint` (`#fdeaf3`) | 10,17 : 1 auf `--fs-brand-deep` |

Betroffen u. a.: `.fs-drawer` (mobiles Menü), `.fs-crumbs` (Breadcrumbs),
`.fs-lochero`/`.fs-loccalc` (Basis-Hintergrund, unabhängig von der
Flächen-Klasse obendrüber), `.fs-teamcard`, `.fs-darktesti`, `.fs-cta-band`,
`.fs-footer__bottom`, `.fs-tiktok__close`, `.fs-timeline__dot`, `.fs-range`,
`.fs-distance__out`, `.fs-ig__post`, `.fs-nav__burger:hover`.
`--fs-scrim` (Abdunkler über Fotos) wechselt von `rgba(10,25,47,.62)`
(Navy-getönt) auf `rgba(0,0,0,.55)` (neutral Schwarz) – funktional identisch,
ohne Blau-Stich auf beliebigen Fotos.

### Ergebnis

- **0** Vorkommen von `fs-surface-dark`, `--fs-dark`, `--fs-dark-2`,
  `--fs-on-dark*` im gesamten Theme (PHP, CSS, `theme.json`) – geprüft per
  Volltextsuche nach der Umstellung.
- Basis-Palette: von 12 auf **12** Rohfarben (2 entfernt: `dark`, `dark-2`
  als eigenständige Werte; 2 neu: `baby`, `baby-accent`; `on-dark`/
  `on-dark-accent`/`on-dark-accent-hi` ersatzlos entfernt, da auf bestehende
  Pink-/Weiß-Werte umgehängt statt neu definiert).
- Neue Kontrastpaare: alle ≥ 4,5 : 1 für Text, alle ≥ 3 : 1 für die beiden
  ausschließlich dekorativ genutzten Akzentfarben (`--fs-baby-accent`,
  `--fs-brand-accent`).

