#!/usr/bin/env python3
"""Benennt die Fotos der Fahrschule edi nach dem Mediathek-Schema um.

Aufruf im Bildordner, ohne Argumente:

    python3 edi-bilder.py                 nur umbenennen (Kopie nach umbenannt/)
    python3 edi-bilder.py --verkleinern   zusätzlich lange Kante 2560 px
    python3 edi-bilder.py --ordner PFAD   anderen Ordner bearbeiten

Was passiert:
  - Porträts ("Fahrschule Edi <Name> Fahrlehrer <Ort> <Klassen>_wp.jpg")
    werden zu edi-team-<vorname>.jpg, das zweite Foto derselben Person zu
    edi-team-<vorname>-b.jpg. Standort und Klassen kommen NICHT in den Namen,
    sondern in zuordnung.csv - im Theme pflegt man sie im Fahrlehrer-Finder.
  - Nummerierte Fotos ("Fahrschule edi 12_wp.jpg") werden zu
    edi-galerie-012.jpg. Was darauf zu sehen ist, steht nirgends - die
    Zuordnung zu edi-fuhrpark-*, edi-raum-*, edi-standort-*, edi-kurs-*
    trägt man in zuordnung.csv von Hand nach. Das Skript rät nicht.
  - Übersprungen: KAZ-Bilder (anderes Projekt), FFs/FFS (Fahrlehrer-
    fachschule, nicht die edi-Fahrschule), Dateien, die schon mit edi-
    beginnen. Ausgeschlossen: iStock (Lizenz ungeklärt).

Die Originale bleiben unangetastet; alles landet als Kopie in umbenannt/.
Der Ordner "Umbenannt " (großes U, Leerzeichen am Ende) wird weder gelesen
noch beschrieben. Ein zweiter Lauf legt nichts doppelt an.

Warum -b und nicht -2 für das Zweitfoto: WordPress hängt selbst -2 an,
wenn ein Slug schon belegt ist. Ein versehentlich doppelt hochgeladenes
Bild erschiene dann still als Zweitfoto. -b kann WordPress nicht erzeugen.
"""
import argparse, csv, hashlib, os, re, shlex, shutil, sys, unicodedata

AUSGABE = 'umbenannt'
GESPERRT = 'Umbenannt '          # fremder Ordner mit Leerzeichen am Ende
BILD_ENDUNGEN = {'.jpg', '.jpeg', '.png', '.webp'}

# Gültige Führerscheinklassen (FeV § 6) - ein Token aus dieser Menge ist eine
# Klasse, kein Namensteil.
KLASSEN = ['AM', 'A1', 'A2', 'A', 'B', 'BF17', 'B96', 'B197', 'BE',
           'C1', 'C1E', 'C', 'CE', 'D1', 'D1E', 'D', 'DE', 'L', 'T']
STANDORTE = {'ge': 'gelsenkirchen', 'gelsenkirchen': 'gelsenkirchen',
             'du': 'duisburg', 'duisburg': 'duisburg',
             'essen': 'essen'}
NR_SUFFIX = 'bcdefgh'           # zweites, drittes ... Foto derselben Person
FREI_AB = 901                   # Galerie-Nummern für Fotos ohne Nummer


def nfc(s):
    return unicodedata.normalize('NFC', s)


def slugify(s):
    s = nfc(s).lower()
    for a, b in (('ä', 'ae'), ('ö', 'oe'), ('ü', 'ue'), ('ß', 'ss'), ('ı', 'i')):
        s = s.replace(a, b)
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')


def stamm(name):
    """Dateiname ohne Endung und ohne das _wp der Mediathek-Exporte."""
    base, ext = os.path.splitext(name)
    return re.sub(r'[\s_-]*wp$', '', base, flags=re.I).strip(), ext.lower()


def ist_rolle(tok):
    t = tok.lower()
    # Fahrlehrer, Fahrlehrerin und die realen Tippfehler Fahrehrer, Fahrlhererin
    return t.startswith('fahr') and t != 'fahrschule' and re.fullmatch(r'fahr[a-z]*er(in)?', t) is not None


def klassen_aus(text):
    """B:BE -> B, BE · B:E -> BE · B : BE -> B, BE. Doppelpunkt ist hier kein
    Pfadtrenner, sondern ein Tippfehler (der Mac zeigt ihn als / an)."""
    def join(m):
        a, b = m.group(1), m.group(2)
        return f' {a + b} ' if (a + b) in KLASSEN else f' {a} {b} '
    return re.sub(r'\b([A-Z]+\d*)\s*:\s*([A-Z]+\d*)\b', join, text)


def lese_portraet(rest):
    """Zerlegt den Teil nach "Fahrschule Edi" - unabhängig von der Reihenfolge."""
    hinweise = []
    m = re.search(r'\(([^)]*)\)', rest)
    if m:
        hinweise.append(f'Rufname „{m.group(1).strip()}“ nicht im Dateinamen übernommen')
        rest = rest[:m.start()] + ' ' + rest[m.end():]
    rest = klassen_aus(rest)
    toks = [t for t in re.split(r'[\s,;:._\-–]+', rest) if t]
    name, klassen, rolle, leiter, ort, marker = [], [], '', False, '', None
    for t in toks:
        if ist_rolle(t):
            rolle = 'Fahrlehrerin' if t.lower().endswith('in') else 'Fahrlehrer'
        elif t.lower() == 'leiter' or t.lower() == 'leiterin':
            leiter = True
        elif t.lower() in STANDORTE:
            ort = STANDORTE[t.lower()]
        elif t in KLASSEN:
            if t not in klassen:
                klassen.append(t)
        elif t.isdigit():
            marker = int(t)
        elif re.fullmatch(r'[^\W\d_]+', t):
            name.append(t)
        else:
            hinweise.append(f'Teil „{t}“ nicht erkannt')
    if leiter:
        rolle = 'Leiter + ' + (rolle or 'Fahrlehrer')
    klassen.sort(key=KLASSEN.index)
    if not ort:
        hinweise.append('Standort nicht im Dateinamen - im Fahrlehrer-Finder nachtragen')
    return {'person': ' '.join(name), 'rolle': rolle, 'klassen': ', '.join(klassen),
            'standort': ort, 'marker': marker, 'hinweise': hinweise}


def einordnen(name):
    """-> (gruppe, daten). Gruppen: team, galerie, edi, kaz, ffs, istock, rest."""
    n = nfc(name)
    base, ext = stamm(n)
    low = n.lower()
    if ext not in BILD_ENDUNGEN:
        return 'rest', {'grund': 'keine Bilddatei'}
    if low.startswith('edi-'):
        return 'edi', {}
    if re.search(r'(?<![a-z])kaz(?![a-z])', low):
        return 'kaz', {}
    if re.match(r'ffs\b', low):
        return 'ffs', {}
    if 'istock' in low:
        return 'istock', {}
    m = re.match(r'(fahrschule|fs)\s+edi\b\s*(.*)$', base, flags=re.I)
    if not m:
        return 'rest', {'grund': 'kein bekanntes Muster'}
    rest = m.group(2).strip()
    if any(ist_rolle(t) for t in re.split(r'[\s,;:._\-–()]+', rest) if t):
        d = lese_portraet(rest)
        if not d['person']:
            return 'rest', {'grund': 'Porträt ohne erkennbaren Namen'}
        return 'team', d
    if re.fullmatch(r'\d*', rest):
        return 'galerie', {'nr': int(rest) if rest else None}
    return 'rest', {'grund': 'kein bekanntes Muster'}


def ausreisser(nummern):
    """Nummern, die weit hinter der Reihe liegen (446 nach 77)."""
    s = sorted(set(nummern))
    for i in range(1, len(s)):
        if s[i] - s[i - 1] > 10:
            return s[:i], s[i:]
    return s, []


def tippfehler_kandidat(n, fehlend):
    """446 -> 46, wenn 46 fehlt: eine Ziffer zu viel."""
    t = str(n)
    return sorted({int(t[:i] + t[i + 1:]) for i in range(len(t))} & set(fehlend))


def datei_hash(p):
    h = hashlib.sha256()
    with open(p, 'rb') as f:
        for block in iter(lambda: f.read(1 << 20), b''):
            h.update(block)
    return h.hexdigest()


def verkleinern(quelle, ziel):
    """Lange Kante 2560 px, Qualitätsleiter. -> Hinweistext oder ''."""
    try:
        from PIL import Image, ImageOps
    except ImportError:
        shutil.copy2(quelle, ziel)
        return 'Pillow fehlt (pip install pillow) - Original kopiert'
    with Image.open(quelle) as im:
        im = ImageOps.exif_transpose(im)        # Hochformat bleibt Hochformat
        if im.mode not in ('RGB', 'L'):
            im = im.convert('RGB')
        im.thumbnail((2560, 2560), Image.LANCZOS)
        orig = os.path.getsize(quelle)
        tmp = ziel + '.tmp'
        for q in (86, 80, 74, 68):              # absteigend, bis unter 900 KB
            im.save(tmp, 'JPEG', quality=q, optimize=True, progressive=True)
            if os.path.getsize(tmp) <= 900 * 1024:
                break
    if os.path.getsize(tmp) >= orig:
        os.remove(tmp)
        shutil.copy2(quelle, ziel)
        return 'neu kodiert größer als das Original - Original behalten'
    os.replace(tmp, ziel)
    return ''


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    ap.add_argument('--ordner', default='.', help='Bildordner (Standard: aktueller Ordner)')
    ap.add_argument('--verkleinern', action='store_true', help='lange Kante 2560 px, JPEG neu kodieren')
    args = ap.parse_args()

    root = os.path.abspath(args.ordner)
    out = os.path.join(root, AUSGABE)
    # Nur Dateien direkt im Ordner. Unterordner - "Umbenannt " und umbenannt/
    # eingeschlossen - werden nie gelesen.
    dateien = sorted((e.name for e in os.scandir(root) if e.is_file()), key=nfc)

    gruppen = {k: [] for k in ('team', 'galerie', 'edi', 'kaz', 'ffs', 'istock', 'rest')}
    for name in dateien:
        g, d = einordnen(name)
        gruppen[g].append((name, d))

    zeilen, doppelt = [], []

    # --- Team: je Person sortiert - ohne Marker vor mit Marker, dann Name ---
    personen = {}
    for name, d in gruppen['team']:
        personen.setdefault(slugify(d['person']), []).append((name, d))
    for slug, fotos in sorted(personen.items()):
        fotos.sort(key=lambda x: (x[1]['marker'] is not None, x[1]['marker'] or 0, nfc(x[0])))
        for i, (name, d) in enumerate(fotos):
            if i > len(NR_SUFFIX):
                doppelt.append((name, f'edi-team-{slug}', 'mehr Fotos als Suffixe'))
                continue
            ziel = f'edi-team-{slug}' + ('' if i == 0 else '-' + NR_SUFFIX[i - 1])
            h = list(d['hinweise'])
            if i:
                h.insert(0, f'{i + 1}. Foto der Person (Wechselbild)')
            zeilen.append([ziel, name, d['person'], d['rolle'], d['klassen'], d['standort'], '; '.join(h)])

    # --- Galerie: Quellnummer wird Galerienummer, stabil über Läufe ---
    nummern = [d['nr'] for _, d in gruppen['galerie'] if d['nr'] is not None]
    reihe, raus = ausreisser(nummern)
    fehlend = [n for n in range(1, (max(reihe) if reihe else 0) + 1) if n not in set(nummern)]
    belegt, frei = {}, FREI_AB
    galerie = sorted(gruppen['galerie'], key=lambda x: (x[1]['nr'] is None, x[1]['nr'] or 0, nfc(x[0])))
    for name, d in galerie:
        hinweis = 'Motiv sichten; bei Bedarf als edi-fuhrpark-*, edi-raum-*, edi-standort-* oder edi-kurs-* verwenden'
        nr = d['nr']
        if nr is None:
            nr = frei; frei += 1
            hinweis = f'ohne Nummer im Dateinamen, Nummer {nr} vergeben; ' + hinweis
        elif nr in raus:
            k = tippfehler_kandidat(nr, fehlend)
            hinweis = (f'fällt aus der Reihe 1–{max(reihe)}' + (f', vermutlich Tippfehler für {k[0]} (fehlt)' if k else '')
                       + '; Nummer unverändert übernommen; ' + hinweis)
        ziel = f'edi-galerie-{nr:03d}'
        if ziel in belegt:
            doppelt.append((name, ziel, f'Nummer {nr} schon durch „{belegt[ziel]}“ belegt'))
            continue
        belegt[ziel] = name
        zeilen.append([ziel, name, '', '', '', '', hinweis])

    # --- Kopieren -------------------------------------------------------------
    os.makedirs(out, exist_ok=True)
    neu = vorhanden = konflikt = 0
    endung = {}
    for z in zeilen:
        quelle = os.path.join(root, z[1])
        ext = stamm(z[1])[1]
        ext = '.jpg' if (ext == '.jpeg' or args.verkleinern) else ext
        ziel = os.path.join(out, z[0] + ext)
        endung[z[0]] = ext
        if os.path.exists(ziel):
            # Zweiter Lauf: nichts doppelt anlegen, nichts still überschreiben.
            if args.verkleinern or datei_hash(ziel) == datei_hash(quelle):
                vorhanden += 1
            else:
                konflikt += 1
                z[6] = (z[6] + '; ' if z[6] else '') + 'Ziel existiert mit anderem Inhalt - nicht überschrieben'
            continue
        if args.verkleinern:
            n = verkleinern(quelle, ziel)
            if n:
                z[6] = (z[6] + '; ' if z[6] else '') + n
        else:
            shutil.copy2(quelle, ziel)
        neu += 1

    zeilen.sort(key=lambda z: z[0])
    with open(os.path.join(out, 'zuordnung.csv'), 'w', newline='', encoding='utf-8-sig') as f:
        w = csv.writer(f, delimiter=';')
        w.writerow(['zielname', 'quelldatei', 'person', 'rolle', 'klassen', 'standort', 'hinweis'])
        for z in zeilen:
            w.writerow([z[0] + endung.get(z[0], '.jpg')] + [nfc(x) for x in z[1:]])

    # --- Bericht --------------------------------------------------------------
    team = [z for z in zeilen if z[0].startswith('edi-team-')]
    gal = [z for z in zeilen if z[0].startswith('edi-galerie-')]
    L = []
    p = L.append
    p('BILD-UMBENENNUNG FAHRSCHULE EDI')
    p(f'Ordner: {root}')
    p('')
    p(f'  Porträts      {len(team):4d}  -> edi-team-*  ({len(personen)} Personen)')
    p(f'  Galerie       {len(gal):4d}  -> edi-galerie-*')
    p(f'  übersprungen  {len(gruppen["kaz"]):4d}  KAZ (anderes Projekt)')
    p(f'  übersprungen  {len(gruppen["ffs"]):4d}  FFs/FFS (Fahrlehrerfachschule, nicht edi)')
    p(f'  übersprungen  {len(gruppen["edi"]):4d}  schon nach Schema benannt (edi-*)')
    p(f'  ausgeschlossen{len(gruppen["istock"]):4d}  iStock (Lizenz ungeklärt)')
    p(f'  nicht zugeordnet {len(gruppen["rest"]) + len(doppelt):2d}')
    p(f'  Dateien: {neu} neu kopiert, {vorhanden} schon vorhanden, {konflikt} Konflikte')
    if reihe:
        p('')
        p(f'Nummernreihe Galerie: 1–{max(reihe)}')
        p('  fehlende Nummern: ' + (', '.join(map(str, fehlend)) or 'keine'))
        for n in raus:
            k = tippfehler_kandidat(n, fehlend)
            p(f'  Ausreißer {n}' + (f' - vermutlich Tippfehler für {k[0]}' if k else ''))
    ohne = [z for z in team if not z[5]]
    if ohne:
        p('')
        p('Porträts ohne Standort im Dateinamen (im Fahrlehrer-Finder nachtragen):')
        for z in ohne:
            p(f'  {z[0]}  <- {nfc(z[1])}')
    if gruppen['rest'] or doppelt:
        p('')
        p('NICHT ZUGEORDNET - bleibt unbenannt, bitte prüfen:')
        for name, d in gruppen['rest']:
            p(f'  {nfc(name)}  ({d["grund"]})')
        for name, ziel, grund in doppelt:
            p(f'  {nfc(name)}  (doppelt: {ziel} - {grund})')
    slugs = ' '.join(z[0] for z in zeilen)
    p('')
    p('VOR DEM IMPORT: Slugs in der Mediathek frei? (sonst hängt WordPress -2 an)')
    p(f'  for s in {slugs}; do [ "$(wp post list --post_type=attachment --name="$s" --format=count)" != 0 ] && echo "belegt: $s"; done')
    p('')
    p('IMPORT (einmal, aus dem WordPress-Verzeichnis):')
    p(f'  wp media import {shlex.quote(out)}/edi-*')
    p('')
    p('Hinweis: Ohne --verkleinern sind die Dateien Byte-Kopien und behalten ihre')
    p('EXIF-Daten (Kamera, ggf. GPS). --verkleinern kodiert neu und verwirft sie -')
    p('außer bei Dateien, deren Original behalten wurde (siehe hinweis in der CSV).')
    text = '\n'.join(L) + '\n'
    with open(os.path.join(out, 'bericht.txt'), 'w', encoding='utf-8') as f:
        f.write(text)
    sys.stdout.write(text)
    return 0


if __name__ == '__main__':
    sys.exit(main())
