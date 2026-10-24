#!/usr/bin/env python3
"""Test für bin/edi-bilder.py an einem nachgebauten Bildordner.

Die echten 243 Dateien liegen nicht im Repository. Die Fixture baut ihre
Namen aus dem Bild-Briefing nach (Oktober 2026), mit allen dort genannten
Sonderfällen: Tippfehler Fahrehrer/Fahrlhererin/fahrlehrer, Doppelpunkte in
den Klassen, Komma und Bindestrich bei Birol, Ali Ihsan, Abdullah (Apo),
Ömer ". 2" in NFD-Schreibweise (wie vom Mac), die Nummernlücken 5, 18, 46,
55, 59, 65, der Ausreißer 446, ein Foto ohne Nummer, 136 KAZ-Bilder, drei
iStock-Bilder, FFs edi 4 und der fremde Ordner "Umbenannt ".

Das Skript läuft zweimal. Erwartet: gleiche Zuordnung, keine neuen Dateien
im zweiten Lauf, nichts außerhalb von umbenannt/ angefasst.
"""
import csv, hashlib, os, subprocess, sys, tempfile, unicodedata

ROOT = os.path.abspath(sys.argv[1] if len(sys.argv) > 1 else '.')
SKRIPT = os.path.join(ROOT, 'bin', 'edi-bilder.py')
fails = []


def fail(m):
    fails.append(m)
    print('  FEHLER: ' + m)


def ok(m):
    print('  ok: ' + m)


P = 'Fahrschule Edi '
PORTRAETS = [
    # (Dateiname-Mitte, erwarteter Zielname, Person, Klassen, Standort)
    ('Aslan Leiter Fahrehrer GE A B', 'edi-team-aslan', 'Aslan', 'A, B', 'gelsenkirchen'),
    ('Aslan Leiter Fahrehrer GE A B 2', 'edi-team-aslan-b', 'Aslan', 'A, B', 'gelsenkirchen'),
    ('Yalcin Leiter Fahrlehrer DU B B:E : A', 'edi-team-yalcin', 'Yalcin', 'A, B, BE', 'duisburg'),
    ('Adis Fahrlehrer GE A', 'edi-team-adis', 'Adis', 'A', 'gelsenkirchen'),
    ('Ali Ihsan Fahrlehrer GE B BE', 'edi-team-ali-ihsan', 'Ali Ihsan', 'B, BE', 'gelsenkirchen'),
    ('Ali Ihsan Fahrlehrer GE B, BE 2', 'edi-team-ali-ihsan-b', 'Ali Ihsan', 'B, BE', 'gelsenkirchen'),
    ('Baris fahrlehrer GE B', 'edi-team-baris', 'Baris', 'B', 'gelsenkirchen'),
    ('Baris fahrlehrer GE B 2', 'edi-team-baris-b', 'Baris', 'B', 'gelsenkirchen'),
    ('Birol, Fahrlehrer GE B', 'edi-team-birol', 'Birol', 'B', 'gelsenkirchen'),
    ('Birol - Fahrlehrer GE B', 'edi-team-birol-b', 'Birol', 'B', 'gelsenkirchen'),
    ('Fergan Fahrlehrer GE A B', 'edi-team-fergan', 'Fergan', 'A, B', 'gelsenkirchen'),
    ('Fergan Fahrlehrer GE A B 2', 'edi-team-fergan-b', 'Fergan', 'A, B', 'gelsenkirchen'),
    ('Nebahat Fahrlehrerin GE B', 'edi-team-nebahat', 'Nebahat', 'B', 'gelsenkirchen'),
    ('Ramazan Fahrlehrer GE B', 'edi-team-ramazan', 'Ramazan', 'B', 'gelsenkirchen'),
    ('Ramazan Fahrlehrer GE B 2', 'edi-team-ramazan-b', 'Ramazan', 'B', 'gelsenkirchen'),
    ('Sakit Fahrlehrer GE B', 'edi-team-sakit', 'Sakit', 'B', 'gelsenkirchen'),
    ('Sakit Fahrlehrer GE B 2', 'edi-team-sakit-b', 'Sakit', 'B', 'gelsenkirchen'),
    ('Ömer Fahrlehrer DU B', 'edi-team-oemer', 'Ömer', 'B', 'duisburg'),
    ('Ömer . 2 Fahrlehrer DU B', 'edi-team-oemer-b', 'Ömer', 'B', 'duisburg'),
    ('Rahel Fahrlhererin DU B:BE', 'edi-team-rahel', 'Rahel', 'B, BE', 'duisburg'),
    ('Rahel Fahrlhererin DU B : BE 2', 'edi-team-rahel-b', 'Rahel', 'B, BE', 'duisburg'),
    ('Tammam Fahrlehrer DU B:BE', 'edi-team-tammam', 'Tammam', 'B, BE', 'duisburg'),
    ('Tammam Fahrlehrer DU B : BE 2', 'edi-team-tammam-b', 'Tammam', 'B, BE', 'duisburg'),
    ('Devran Fahrlehrer Essen B', 'edi-team-devran', 'Devran', 'B', 'essen'),
    ('Devran Fahrlehrer Essen B 2', 'edi-team-devran-b', 'Devran', 'B', 'essen'),
    ('Mustafa Fahrehrer Essen B', 'edi-team-mustafa', 'Mustafa', 'B', 'essen'),
    ('Mustafa Fahrehrer Essen B 2', 'edi-team-mustafa-b', 'Mustafa', 'B', 'essen'),
    ('Abdullah (Apo) Fahrlehrer B', 'edi-team-abdullah', 'Abdullah', 'B', ''),
    ('Abdullah (Apo) Fahrlehrer B 2', 'edi-team-abdullah-b', 'Abdullah', 'B', ''),
    ('Mesut Fahrlehrer B', 'edi-team-mesut', 'Mesut', 'B', ''),
]
LUECKEN = {5, 18, 46, 55, 59, 65}
FS_NUMMERN = {57, 60}                 # als "Fs edi 57" geliefert


def baue(d):
    def leg(name, inhalt=None):
        with open(os.path.join(d, name), 'wb') as f:
            f.write(inhalt if inhalt is not None else hashlib.sha256(name.encode()).digest() * 8)
    for mitte, *_ in PORTRAETS:
        # Der Mac liefert Umlaute zerlegt (NFD) - das Skript muss Ömer trotzdem finden.
        leg(unicodedata.normalize('NFD', P + mitte + '_wp.jpg'))
    for n in range(1, 78):
        if n in LUECKEN:
            continue
        leg((f'Fs edi {n}_wp.jpg') if n in FS_NUMMERN else f'Fahrschule edi {n}_wp.jpg')
    leg('Fahrschule edi 446_wp.jpg')
    leg('Fahrschule edi _wp.jpg')
    leg('FFs edi 4_wp.jpg')
    for i in range(1, 137):
        leg(f'KAZ Weiterbildung {i:03d}.jpg')
    for i in range(1, 4):
        leg(f'iStock-{1000 + i}.jpg')
    leg('edi-logo.png')                    # schon nach Schema benannt
    os.mkdir(os.path.join(d, 'Umbenannt '))
    leg(os.path.join('Umbenannt ', 'Fahrschule edi 1_wp.jpg'), b'fremd')


def schnappschuss(d):
    out = {}
    for base, _, files in os.walk(d):
        for f in files:
            p = os.path.join(base, f)
            with open(p, 'rb') as fh:
                out[os.path.relpath(p, d)] = hashlib.sha256(fh.read()).hexdigest()
    return out


def lauf(d):
    r = subprocess.run([sys.executable, '-I', SKRIPT], cwd=d, capture_output=True, text=True)
    if r.returncode:
        fail('Skript bricht ab: ' + r.stderr.strip()[-400:])
    return r.stdout


with tempfile.TemporaryDirectory() as d:
    baue(d)
    vorher = schnappschuss(d)
    bericht = lauf(d)
    nach1 = schnappschuss(d)
    with open(os.path.join(d, 'umbenannt', 'zuordnung.csv'), encoding='utf-8-sig') as f:
        rows = list(csv.DictReader(f, delimiter=';'))
    bericht2 = lauf(d)
    nach2 = schnappschuss(d)
    with open(os.path.join(d, 'umbenannt', 'zuordnung.csv'), encoding='utf-8-sig') as f:
        rows2 = list(csv.DictReader(f, delimiter=';'))

    # 1) Originale und fremder Ordner unberührt, nur umbenannt/ neu
    geaendert = [k for k in vorher if nach1.get(k) != vorher[k]]
    fremd = [k for k in nach1 if k not in vorher and not k.startswith('umbenannt' + os.sep)]
    if geaendert or fremd:
        fail(f'außerhalb von umbenannt/ verändert: {(geaendert + fremd)[:5]}')
    else:
        ok('Originale und Ordner "Umbenannt " unverändert, Ausgabe nur in umbenannt/')

    # 2) Porträts: alle 30, mit Person, Klassen, Standort
    byname = {r['zielname'].rsplit('.', 1)[0]: r for r in rows}
    bad = []
    for mitte, ziel, person, klassen, ort in PORTRAETS:
        r = byname.get(ziel)
        if not r:
            bad.append(f'{ziel} fehlt (Quelle „{mitte}“)')
        elif (r['person'], r['klassen'], r['standort']) != (person, klassen, ort):
            bad.append(f'{ziel}: {r["person"]!r}/{r["klassen"]!r}/{r["standort"]!r} statt {person!r}/{klassen!r}/{ort!r}')
    team = [k for k in byname if k.startswith('edi-team-')]
    if len(team) != 30:
        bad.append(f'{len(team)} Porträts statt 30')
    for x in bad:
        fail(x)
    if not bad:
        ok('30 Porträts von 17 Personen erkannt, inkl. Tippfehlern, Doppelpunkten, Ömer in NFD, (Apo), Ali Ihsan')

    # 3) Galerie: 73 Bilder, Nummern stabil, Ausreißer und Lücken berichtet
    gal = sorted(k for k in byname if k.startswith('edi-galerie-'))
    want = sorted([f'edi-galerie-{n:03d}' for n in range(1, 78) if n not in LUECKEN] + ['edi-galerie-446', 'edi-galerie-901'])
    if gal != want:
        fail(f'Galerie: {len(gal)} Bilder, erwartet {len(want)}; Differenz {sorted(set(gal) ^ set(want))[:6]}')
    else:
        ok('73 Galeriebilder, Quellnummer = Galerienummer, Foto ohne Nummer -> 901')
    for muss in ('fehlende Nummern: 5, 18, 46, 55, 59, 65', 'Ausreißer 446 - vermutlich Tippfehler für 46'):
        if muss not in bericht:
            fail(f'Bericht nennt nicht: „{muss}“')
    if 'fehlende Nummern: 5, 18, 46, 55, 59, 65' in bericht and 'Tippfehler für 46' in bericht:
        ok('Bericht nennt Lücken 5, 18, 46, 55, 59, 65 und 446 als vermuteten Tippfehler')

    # 4) Übersprungen / ausgeschlossen
    for muss in ('136  KAZ', '   1  FFs/FFS', '   3  iStock', '   1  schon nach Schema', 'nicht zugeordnet  0'):
        if muss not in bericht:
            fail(f'Zählung fehlt im Bericht: „{muss}“')
    quellen = {unicodedata.normalize('NFC', r['quelldatei']) for r in rows}
    if any(q.startswith(('KAZ', 'FFs', 'iStock')) for q in quellen):
        fail('KAZ-, FFs- oder iStock-Datei wurde umbenannt')
    else:
        ok('136 KAZ übersprungen, FFs edi 4 übersprungen (kein Duplikat von Nr. 4), 3 iStock ausgeschlossen')

    # 5) Keine doppelten Zielnamen, keine edi-Datei unbenannt
    ziele = [r['zielname'] for r in rows]
    if len(ziele) != len(set(ziele)):
        fail('doppelte Zielnamen in zuordnung.csv')
    edi_quellen = [k for k in vorher if os.sep not in k and k.lower().startswith(('fahrschule edi', 'fs edi'))]
    unbenannt = [k for k in edi_quellen if unicodedata.normalize('NFC', k) not in quellen]
    if unbenannt:
        fail(f'edi-Dateien ohne Zielnamen: {unbenannt[:5]}')
    elif len(ziele) == len(set(ziele)):
        ok(f'{len(edi_quellen)} edi-Dateien, alle mit eindeutigem Zielnamen')

    # 6) Zielnamen nach Schema: nur a-z, 0-9, Bindestrich
    import re
    schief = [z for z in ziele if not re.fullmatch(r'edi-[a-z0-9-]+\.(jpg|png|webp)', z)]
    if schief:
        fail(f'Zielnamen außerhalb des Schemas: {schief[:5]}')

    # 7) Zweiter Lauf: idempotent
    if nach2 != nach1 and {k: v for k, v in nach2.items() if not k.endswith(('bericht.txt',))} != {k: v for k, v in nach1.items() if not k.endswith(('bericht.txt',))}:
        fail('zweiter Lauf hat Dateien verändert oder neu angelegt')
    elif rows2 != rows:
        fail('zweiter Lauf liefert andere Zuordnung')
    elif '0 neu kopiert, 103 schon vorhanden, 0 Konflikte' not in bericht2:
        fail('zweiter Lauf meldet nicht "0 neu kopiert, 103 schon vorhanden"')
    else:
        ok('zweiter Lauf: 0 neu, 103 vorhanden, identische Zuordnung')

    # 8) Import-Zeile ist shell-sicher gequotet
    imp = [l for l in bericht.splitlines() if 'wp media import' in l]
    if not imp or "'" not in imp[0] and ' ' in d:
        fail('Importzeile fehlt oder ist nicht gequotet')

# 9) --verkleinern an echten JPEGs (nur mit Pillow)
try:
    from PIL import Image
except ImportError:
    Image = None
    print('  übersprungen: --verkleinern (Pillow fehlt)')
if Image:
    import random
    with tempfile.TemporaryDirectory() as d:
        rnd = random.Random(1)
        gross = Image.frombytes('RGB', (3600, 2400), bytes(rnd.getrandbits(8) for _ in range(3600 * 2400 * 3)))
        exif = gross.getexif(); exif[0x0112] = 6          # Orientation: 90° gedreht
        gross.save(os.path.join(d, 'Fahrschule edi 1_wp.jpg'), quality=95, exif=exif)
        Image.frombytes('RGB', (400, 300), bytes(rnd.getrandbits(8) for _ in range(400 * 300 * 3))).save(os.path.join(d, 'Fahrschule edi 2_wp.jpg'), quality=30)
        klein_orig = os.path.getsize(os.path.join(d, 'Fahrschule edi 2_wp.jpg'))
        r = subprocess.run([sys.executable, '-I', SKRIPT, '--verkleinern'], cwd=d, capture_output=True, text=True)
        if r.returncode:
            fail('--verkleinern bricht ab: ' + r.stderr.strip()[-300:])
        else:
            with Image.open(os.path.join(d, 'umbenannt', 'edi-galerie-001.jpg')) as im:
                g = im.size
            k = os.path.getsize(os.path.join(d, 'umbenannt', 'edi-galerie-002.jpg'))
            if g != (1707, 2560):
                fail(f'--verkleinern: {g} statt (1707, 2560) - lange Kante 2560 und EXIF-Drehung angewendet?')
            elif k != klein_orig:
                fail('--verkleinern: kleine Datei neu kodiert, obwohl das Original kleiner war')
            elif 'Original behalten' not in r.stdout and 'Original behalten' not in open(os.path.join(d, 'umbenannt', 'zuordnung.csv'), encoding='utf-8-sig').read():
                fail('--verkleinern: "Original behalten" nicht vermerkt')
            else:
                ok('--verkleinern: 3600x2400 mit EXIF-Drehung -> 1707x2560, kleineres Original behalten und vermerkt')

print()
if fails:
    print(f'BILD-SKRIPT: {len(fails)} Fehler')
    sys.exit(1)
print('BILD-SKRIPT: bestanden')
