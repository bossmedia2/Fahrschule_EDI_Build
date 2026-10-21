#!/usr/bin/env python3
"""Struktur-Prüfung: Seitenvorlagen, Start-Patterns und Seitenhierarchie.

Diese Fehler fallen weder beim Lint noch beim Rendern auf, schlagen aber im
Backend zu: eine Vorlage ohne Datei erscheint nicht im Auswahlfeld, ein
Pattern mit kaputtem Block-Markup legt den Muster-Dialog lahm, und eine
Klassenseite ohne gültigen Bereich erzeugt eine Brotkrume ins Leere.
"""
import json, os, re, subprocess, sys

root = sys.argv[1] if len(sys.argv) > 1 else '.'
theme = os.path.join(root, 'fahrschule-edi')
rd = lambda p: open(p, encoding='utf-8', errors='replace').read()
fails = []
def fail(m): fails.append(m); print("  FEHLER: " + m)
def ok(m):   print("  ok: " + m)

# --- 1) Seitenvorlagen ----------------------------------------------------
tpls = {}
for f in sorted(os.listdir(theme)):
    if not f.endswith('.php'):
        continue
    head = rd(os.path.join(theme, f))[:400]
    m = re.search(r'Template Name:\s*(.+)', head)
    if m:
        tpls[f] = m.group(1).strip()
if not tpls:
    fail("keine Seitenvorlage gefunden - beim Anlegen einer Seite erscheint kein Vorlagenfeld")
else:
    for f, name in tpls.items():
        if not os.path.exists(os.path.join(theme, f)):
            fail(f"Vorlage '{name}' verweist auf fehlende Datei {f}")
    ok(f"{len(tpls)} Seitenvorlagen: " + ", ".join(sorted(tpls.values())))

# --- 2) Registrierte Blöcke einsammeln (aus der Konfiguration) ------------
cfg = rd(os.path.join(theme, 'inc/blocks-config.php'))
known = set(re.findall(r"'(fahrschule/[a-z0-9-]+)'\s*=>\s*array\(", cfg))

# --- 3) Start-Patterns ----------------------------------------------------
pdir = os.path.join(theme, 'patterns')
if not os.path.isdir(pdir):
    fail("kein patterns/-Verzeichnis - der Muster-Dialog beim Seitenanlegen bleibt leer")
else:
    pats = [f for f in sorted(os.listdir(pdir)) if f.endswith('.php')]
    if not pats:
        fail("patterns/ ist leer")
    starters = 0
    for f in pats:
        src = rd(os.path.join(pdir, f))
        head = src[:900]
        for key in ('Title:', 'Slug:'):
            if key not in head:
                fail(f"patterns/{f}: Kopfzeile '{key}' fehlt")
        if 'Block Types:' in head and 'core/post-content' in head:
            starters += 1
        # Block-Markup prüfen: Name bekannt, Attribut-JSON gültig
        for m in re.finditer(r'<!--\s*wp:([a-z0-9-]+/[a-z0-9-]+)(\s+(\{.*?\}))?\s*/?-->', src, re.S):
            name, attrs = m.group(1), m.group(3)
            if name.startswith('fahrschule/') and name not in known:
                fail(f"patterns/{f}: Block {name} ist nicht registriert")
            if attrs:
                try:
                    json.loads(attrs)
                except Exception as e:
                    fail(f"patterns/{f}: Attribut-JSON von {name} ist ungültig ({e})")
    if starters == 0:
        fail("kein Pattern mit 'Block Types: core/post-content' - WordPress zeigt beim "
             "Anlegen einer Seite dann keinen Muster-Dialog")
    else:
        ok(f"{len(pats)} Patterns, davon {starters} als Startmuster für neue Seiten")

# --- 4) Hierarchie --------------------------------------------------------
php = r'''
$GLOBALS["__theme"]=$argv[1];
require $argv[2]."/wp-stub.php";
define("FAHRSCHULE_DIR",$argv[1]); define("FAHRSCHULE_URI","/u"); define("FAHRSCHULE_VERSION","1");
foreach(["helpers","pdf-texts","media","pages"] as $f) require $argv[1]."/inc/".$f.".php";
$m=fahrschule_page_map(); $out=[];
foreach($m as $k=>$p){ $out[$k]=["parent"=>$p["parent"]??"","slug"=>$p["slug"]??"","nav"=>$p["nav"]??""]; }
echo json_encode($out);
'''
res = subprocess.run(['php', '-r', php, theme, os.path.join(root, 'bin/checks')],
                     capture_output=True, text=True)
if res.returncode != 0:
    fail("Seitenregistry nicht lesbar: " + res.stderr.strip()[:200])
else:
    pages = json.loads(res.stdout)
    orphans = [k for k, v in pages.items() if v['parent'] and v['parent'] not in pages]
    if orphans:
        fail("Seite mit unbekanntem Elternteil: " + ", ".join(orphans))
    parents = {v['parent'] for v in pages.values() if v['parent']}
    childless = [p for p in parents if not any(v['parent'] == p for v in pages.values())]
    if childless:
        fail("Bereich ohne Unterseiten: " + ", ".join(childless))
    slugs = {}
    for k, v in pages.items():
        slugs.setdefault(v['slug'], []).append(k)
    dupes = {s: ks for s, ks in slugs.items() if len(ks) > 1}
    if dupes:
        fail("doppelte Slugs: " + "; ".join(f"/{s} <- {', '.join(ks)}" for s, ks in dupes.items()))
    if not orphans and not childless and not dupes:
        ok(f"{len(pages)} Seiten, {len(parents)} Bereiche, keine Waisen, keine doppelten Slugs")

print()
if fails:
    print(f"STRUKTURPRÜFUNG: {len(fails)} Fehler")
    sys.exit(1)
print("STRUKTURPRÜFUNG: bestanden")
