#!/usr/bin/env python3
"""Flächenfolge je Seite: nie dreimal Vollpink hintereinander.

Liest die gerenderten Seiten (pages.json aus smoke.php) und bestimmt für
jeden Abschnitt der obersten Ebene in <main> seine Fläche:

  V  vollpink   Signaturpink #d12487 (Vorgabe)
  H  hellpink   Rosé #fbdce9
  L  leiste     kräftiges Pink #b81d5e (schmale Bänder)
  F  foto       Bild mit Abdunkler (auch der Video-Hero)

Maßgeblich ist data-flaeche (Feld „Fläche“, gesetzt von fs_flaeche_anwenden);
Abschnitte fester Blöcke ohne das Attribut werden an ihrer Klasse erkannt.
Die Folge wird für jede Seite ausgegeben - so ist der Rhythmus sichtbar,
nicht nur das Ergebnis.

Aufruf: flaechen.py [<root> [<ausgabe-von-smoke>]]  - ohne Ausgabeverzeichnis
rendert das Skript die Seiten selbst.
"""
import json, os, pathlib, subprocess, sys, tempfile
from html.parser import HTMLParser

root = sys.argv[1] if len(sys.argv) > 1 else str(pathlib.Path(__file__).resolve().parents[2])
out = sys.argv[2] if len(sys.argv) > 2 else None
if out is None:
    out = tempfile.mkdtemp(prefix='fs-flaechen-')
    subprocess.run(['php', os.path.join(root, 'bin/checks/smoke.php'), os.path.join(root, 'fahrschule-edi')],
                   env={**os.environ, 'FS_OUT': out}, capture_output=True)
pages = json.load(open(os.path.join(out, 'pages.json'), encoding='utf-8'))

KURZ = {'vollpink': 'V', 'hellpink': 'H', 'leiste': 'L', 'foto': 'F'}


def flaeche(attrs):
    a = dict(attrs)
    if a.get('data-flaeche') in KURZ:
        return a['data-flaeche']
    cls = set((a.get('class') or '').split())
    if 'fs-vhero--ohne-medien' in cls:      # Hero ohne Video und Standbild
        return 'vollpink'
    if cls & {'fs-vhero', 'fs-surface-foto'}:
        return 'foto'
    if cls & {'fs-ctx-deep', 'fs-trustbar', 'fs-surface-leiste'}:
        return 'leiste'
    if cls & {'fs-surface-light', 'fs-ctx-light'}:
        return 'hellpink'
    return 'vollpink'


class Folge(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.in_main = 0
        self.tiefe = 0          # Verschachtelung von <section>
        self.folge = []

    def handle_starttag(self, tag, attrs):
        if tag == 'main':
            self.in_main += 1
        elif tag == 'section' and self.in_main:
            if self.tiefe == 0:
                self.folge.append(flaeche(attrs))
            self.tiefe += 1

    def handle_endtag(self, tag):
        if tag == 'main':
            self.in_main = max(0, self.in_main - 1)
        elif tag == 'section' and self.in_main:
            self.tiefe = max(0, self.tiefe - 1)


fails = []
print("  Legende: V Vollpink · H Hellpink · L Leiste · F Foto")
breite = max(len(k) for k in pages)
for key in sorted(pages, key=lambda k: (k.startswith('_'), k != 'home', k)):
    p = Folge()
    p.feed(pages[key])
    seq = ''.join(KURZ[f] for f in p.folge)
    mark = ''
    if 'VVV' in seq:
        fails.append(key)
        mark = '   <- dreimal Vollpink'
    print(f"  {key:<{breite}}  {seq or '–'}{mark}")

print()
if fails:
    print(f"  FEHLER: dreimal Vollpink in Folge auf {len(fails)} Seite(n): {', '.join(fails)}")
    print(f"\nFLÄCHENFOLGE: {len(fails)} Fehler")
    sys.exit(1)
print(f"  ok: {len(pages)} Seiten, nirgends dreimal Vollpink in Folge")
print("\nFLÄCHENFOLGE: bestanden")
