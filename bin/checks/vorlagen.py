#!/usr/bin/env python3
"""Seitenvorlagen tragen nur den Rahmen.

Der Seiten-Editor zeigt ausschließlich post_content. Ein Abschnitt, der in
templates/page-*.html steht, ist dort unsichtbar und nur im Website-Editor
änderbar - und dann für jede Seite derselben Vorlage zugleich. Genau daran
lag es, dass Unterseiten bis 3.1 nur zur Hälfte bearbeitbar waren.

Erlaubt in templates/page.html und templates/page-*.html:
  core/template-part (Header, Footer), core/group (das <main>),
  core/post-content, fahrschule/breadcrumbs.
Alles andere gehört in den Seiteninhalt (fahrschule_vorlage_rahmen()).

Aufruf: vorlagen.py [<root>]
"""
import os, pathlib, re, sys

root = sys.argv[1] if len(sys.argv) > 1 else str(pathlib.Path(__file__).resolve().parents[2])
tdir = os.path.join(root, 'fahrschule-edi', 'templates')
ERLAUBT = {'core/template-part', 'core/group', 'core/post-content', 'fahrschule/breadcrumbs'}
TEILE = {'header', 'footer'}

fails = []
dateien = sorted(f for f in os.listdir(tdir) if re.fullmatch(r'page(-[a-z0-9-]+)?\.html', f))
for f in dateien:
    src = open(os.path.join(tdir, f), encoding='utf-8').read()
    hat_inhalt = False
    for m in re.finditer(r'<!--\s+wp:([a-z0-9-]+/)?([a-z0-9-]+)(\s+\{.*?\})?\s+/?-->', src):
        name = (m.group(1) or 'core/') + m.group(2)
        if name == 'core/post-content':
            hat_inhalt = True
        if name not in ERLAUBT:
            wo = src.count('\n', 0, m.start()) + 1
            fails.append(f"templates/{f}:{wo}: {name} – gehört in den Seiteninhalt (fahrschule_vorlage_rahmen), "
                         "sonst ist er im Seiten-Editor nicht bearbeitbar")
        elif name == 'core/template-part':
            slug = re.search(r'"slug"\s*:\s*"([^"]+)"', m.group(3) or '')
            if not slug or slug.group(1) not in TEILE:
                fails.append(f"templates/{f}: Template-Part {slug.group(1) if slug else '?'} – erlaubt sind nur header und footer")
    if not hat_inhalt:
        fails.append(f"templates/{f}: ohne core/post-content – erwartet: der Seiteninhalt im <main>")

for x in fails:
    print("  FEHLER: " + x)
print()
if fails:
    print(f"VORLAGEN: {len(fails)} Fehler")
    sys.exit(1)
print(f"  ok: {len(dateien)} Seitenvorlagen tragen nur Header, Brotkrumen, Seiteninhalt und Footer")
print("\nVORLAGEN: bestanden")
