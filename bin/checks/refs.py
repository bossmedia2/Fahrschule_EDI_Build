#!/usr/bin/env python3
"""Referenzprüfung: Blöcke, Icons, Assets, CSS-Klassen, Keyframes, Farben.

Findet die Fehlerklassen, die weder PHP-Lint noch der Smoke-Test sehen:
ein Block ohne Render-Funktion, ein Icon-Name mit Tippfehler (rendert ein
leeres <svg>), ein Bildpfad ins Leere, eine CSS-Klasse ohne Regel, eine
animation: auf nicht existierende @keyframes - und Blau/Teal im Produktionscode.
"""
import os, re, sys, colorsys

root = sys.argv[1] if len(sys.argv) > 1 else '.'
theme = os.path.join(root, 'fahrschule-edi')
rd = lambda p: open(p, encoding='utf-8', errors='replace').read()
fails = []
def fail(msg): fails.append(msg); print("  FEHLER: " + msg)
def ok(msg):   print("  ok: " + msg)

php_files = [os.path.join(dp, f) for dp, dn, fn in os.walk(root)
             for f in fn if f.endswith('.php') and '.git' not in dp and '/bin/' not in dp.replace(os.sep,'/')]
allphp = "\n".join(rd(p) for p in php_files)
maincss = rd(os.path.join(theme, 'assets/css/main.css'))
cfg = rd(os.path.join(theme, 'inc/blocks-config.php'))
helpers = rd(os.path.join(theme, 'inc/helpers.php'))

# --- Blöcke --------------------------------------------------------------
declared = re.findall(r"'(fahrschule/[a-z0-9-]+)'\s*=>\s*array\(", cfg)
renders = dict(re.findall(r"'(fahrschule/[a-z0-9-]+)'\s*=>\s*array\((?:.|\n)*?'render'\s*=>\s*'([a-z_0-9]+)'", cfg))
defined = set(re.findall(r"^function\s+([a-z_0-9]+)\s*\(", allphp, re.M))
missing = [f"{b} -> {f}()" for b, f in renders.items() if f not in defined]
if missing: fail("Render-Funktion fehlt: " + ", ".join(missing))
norender = [b for b in declared if b not in renders]
if norender: fail("Block ohne 'render': " + ", ".join(norender))
used = set(re.findall(r"fs_block\(\s*'(fahrschule/[a-z0-9-]+)'", allphp))
unknown = sorted(used - set(declared))
if unknown: fail("fs_block() auf nicht registrierten Block: " + ", ".join(unknown))
if not missing and not norender and not unknown:
    ok(f"{len(declared)} Blöcke, alle mit vorhandener Render-Funktion")

# --- Icons ---------------------------------------------------------------
icon_def = set(re.findall(r"'([a-z-]+)'\s*=>\s*'<(?:line|polygon|path|rect|circle|polyline)", helpers))
icon_used = set(re.findall(r"fs_icon\(\s*'([a-z0-9-]+)'", allphp))
bad = sorted(icon_used - icon_def)
if bad: fail("fs_icon() auf undefiniertes Icon (rendert leeres <svg>): " + ", ".join(bad))
else:   ok(f"{len(icon_used)} verwendete Icons, alle definiert")

# --- Assets --------------------------------------------------------------
ondisk = set()
for dp, dn, fn in os.walk(theme):
    for f in fn:
        ondisk.add(os.path.relpath(os.path.join(dp, f), theme).replace(os.sep, '/'))
blob = allphp + maincss
for p in (os.path.join(theme,'assets/js/frontend.js'), os.path.join(theme,'assets/fonts/fonts.css')):
    if os.path.exists(p): blob += rd(p)
# Direkte Pfade ...
refs = set(re.findall(r"assets/(?:images|css|js|fonts)/[A-Za-z0-9_.\-]+", blob))
# ... und die Medien-Registry. Deren Werte sind seit Oktober 2026 keine
# Dateinamen mehr, sondern Schlüssel, die fs_media_by_slug() zuerst in der
# Mediathek und erst danach im Theme sucht. Ein Schlüssel darf also ohne
# mitgelieferte Datei dastehen - die Datei lädt der Kunde hoch. Umgekehrt
# gilt weiterhin: eine Datei im Theme, die kein Schlüssel anspricht, ist tot.
media = rd(os.path.join(theme, 'inc/media.php'))
refs |= {"assets/images/" + m for m in re.findall(r"\$local\s*\.\s*'([A-Za-z0-9_.\-]+)'", media)}
# Wichtig: ein Schlüssel DARF ohne Datei im Theme dastehen - dann kommt das
# Bild aus der Mediathek. Die Kandidaten gehören deshalb NICHT in die Menge
# der Pflichtdateien, sondern nur in die der erlaubten.
slug_keys  = set(re.findall(r"=>\s*'(edi-[a-z0-9\-]+)'", media))
slug_files = {f"assets/images/{slug}.{ext}"
              for slug in slug_keys for ext in ('webp', 'jpg', 'png', 'svg')}
refs |= {"assets/images/" + m for m in re.findall(r"'(favicon-\d+\.webp)'", rd(os.path.join(theme,'functions.php')))}
# fonts.css darf fehlende Schriftdateien nennen: functions.php prüft mit file_exists()
dead = sorted(r for r in refs if r not in ondisk and not r.endswith('.woff2'))
if dead: fail("Asset referenziert, aber nicht vorhanden: " + ", ".join(dead))
else:    ok(f"{len(refs)} referenzierte Assets, alle vorhanden")
imgs = sorted(x for x in ondisk if x.startswith('assets/images/'))
orph = [i for i in imgs if i not in refs and i not in slug_files]
if orph:
    kb = sum(os.path.getsize(os.path.join(theme, i)) for i in orph) / 1024
    fail(f"Bild liegt im Theme, wird aber nirgends referenziert ({kb:.0f} KB): " + ", ".join(orph))
else:
    ok(f"{len(imgs)} Bilder, keines verwaist")

# --- CSS-Klassen ---------------------------------------------------------
CL = r"[A-Za-z][A-Za-z0-9_-]*"
cssc = set(re.findall(r"\.(fs-" + CL + ")", maincss))
phpc = set()
for pat in [r'class="([^"]*)"', r"class='([^']*)'"]:
    for m in re.finditer(pat, allphp):
        for c in m.group(1).split():
            if c.startswith('fs-') and '$' not in c and '<' not in c and '%' not in c:
                phpc.add(c)
und = sorted(phpc - cssc)
if und: fail("CSS-Klasse im Markup ohne Regel in main.css: " + ", ".join(und))
else:   ok(f"{len(phpc)} verwendete fs-Klassen, alle gestylt")

# --- Keyframes -----------------------------------------------------------
kf = set(re.findall(r"@keyframes\s+([A-Za-z0-9_-]+)", maincss))
anim = set()
NOT = {'none','infinite','alternate','linear','ease','ease-in','ease-out','ease-in-out','both',
       'forwards','backwards','normal','reverse','paused','running','step-start','step-end',
       'alternate-reverse','cubic-bezier','steps','var'}
for m in re.finditer(r"animation(?:-name)?\s*:\s*([^;}]+)", maincss):
    for t in re.split(r"[,\s]+", m.group(1).strip()):
        if re.fullmatch(r"[A-Za-z][A-Za-z0-9_-]*", t) and t not in NOT: anim.add(t)
mk = sorted(anim - kf)
if mk: fail("animation: verweist auf nicht definierte @keyframes: " + ", ".join(mk))
else:  ok(f"{len(kf)} @keyframes, alle Verweise aufgelöst")

# --- Blau/Teal im Produktionscode ---------------------------------------
def hue(h):
    h = h.lstrip('#')
    if len(h) == 3: h = ''.join(c*2 for c in h)
    r, g, b = [int(h[i:i+2], 16)/255 for i in (0, 2, 4)]
    hh, l, s = colorsys.rgb_to_hls(r, g, b)
    return hh*360, s
blue = []
for dp, dn, fn in os.walk(root):
    dn[:] = [d for d in dn if d not in ('.git', 'dist', 'docs', 'node_modules')]
    for f in fn:
        if not f.endswith(('.css', '.php', '.js', '.json')): continue
        p = os.path.join(dp, f)
        for i, line in enumerate(rd(p).splitlines(), 1):
            for m in re.finditer(r'#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b', line):
                deg, s = hue(m.group(1))
                if s >= 0.15 and 165 <= deg < 260:
                    blue.append(f"{os.path.relpath(p, root)}:{i} #{m.group(1)}")
if blue: fail("Blau-/Teal-Farbwert im Produktionscode: " + "; ".join(blue))
else:    ok("kein Blau-/Teal-Farbwert im Produktionscode")

print()
if fails:
    print(f"REFERENZPRÜFUNG: {len(fails)} Fehler")
    sys.exit(1)
print("REFERENZPRÜFUNG: bestanden")
