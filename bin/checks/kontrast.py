#!/usr/bin/env python3
"""Kontrast rechnerisch je Fläche (WCAG 2.1 AA) - aus den Tokens in main.css.

Die Browserprüfung misst, was auf den gerenderten Seiten steht. Diese Prüfung
rechnet die Paare aus, die das Farbsystem überhaupt erzeugen KANN - auch
solche, die gerade auf keiner Seite vorkommen (Hover, aktiver Eintrag, Karte
auf Leiste). Für jede Fläche werden die --c-*-Tokens aufgelöst (var()-Ketten,
rgba über dem Untergrund verrechnet) und geprüft:

  Text, Überschrift, Akzent auf Fläche ............ 4,5:1
  Button-Text auf Button (normal und Hover) ....... 4,5:1
  aktiver Eintrag, Hover-Eintrag .................. 4,5:1
  Fokusring (Kern ODER Saum) gegen Fläche ......... 3:1   (SC 1.4.11)

und dasselbe für eine Karte auf jeder Fläche. Die Fläche „Foto“ hängt vom
Bild ab und wird im Browser gemessen; hier steht nur die Abdunklung.
"""
import os, pathlib, re, sys

root = sys.argv[1] if len(sys.argv) > 1 else str(pathlib.Path(__file__).resolve().parents[2])
css = open(os.path.join(root, 'fahrschule-edi/assets/css/main.css'), encoding='utf-8').read()
css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)

def rule(first_selector):
    """Deklarationen der Regel, deren Selektorliste mit first_selector beginnt."""
    m = re.search(r'(?:^|\})\s*' + re.escape(first_selector) + r'\s*(?:,[^{]*)?\{([^}]*)\}', css)
    if not m:
        print(f"  FEHLER: Regel {first_selector} nicht gefunden"); sys.exit(1)
    return dict((k.strip(), v.strip()) for k, v in
                (d.split(':', 1) for d in m.group(1).split(';') if ':' in d) if k.strip().startswith('--'))

palette = rule(':root')

def hexrgb(h):
    h = h.lstrip('#')
    if len(h) == 3: h = ''.join(c * 2 for c in h)
    return (int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16), 1.0)

def parse(val, scope, depth=0):
    val = val.strip()
    if depth > 12: raise ValueError('var()-Kette zu tief: ' + val)
    m = re.fullmatch(r'var\(\s*(--[\w-]+)\s*\)', val)
    if m:
        name = m.group(1)
        if name in scope: return parse(scope[name], scope, depth + 1)
        if name in palette: return parse(palette[name], palette, depth + 1)
        raise KeyError(name)
    if val.startswith('#'): return hexrgb(val)
    m = re.fullmatch(r'rgba?\(([^)]*)\)', val)
    if m:
        p = [float(x) for x in m.group(1).split(',')]
        return (p[0], p[1], p[2], p[3] if len(p) > 3 else 1.0)
    raise ValueError(val)

def over(fg, bg):
    a = fg[3]
    return tuple(fg[i] * a + bg[i] * (1 - a) for i in range(3)) + (1.0,)

def lum(c):
    s = []
    for v in c[:3]:
        v /= 255
        s.append(v / 12.92 if v <= 0.03928 else ((v + 0.055) / 1.055) ** 2.4)
    return 0.2126 * s[0] + 0.7152 * s[1] + 0.0722 * s[2]

def ratio(a, b):
    l1, l2 = lum(a), lum(b)
    return (max(l1, l2) + 0.05) / (min(l1, l2) + 0.05)

fails, n = [], 0

def pruefe(name, scope):
    global n
    get = lambda k: parse(scope[k], scope)
    bg = get('--c-bg')
    paare = [
        ('Text', '--c-text', '--c-bg', 4.5), ('Überschrift', '--c-heading', '--c-bg', 4.5),
        ('Akzent', '--c-accent', '--c-bg', 4.5),
        ('Button', '--c-btn-text', '--c-btn-bg', 4.5), ('Button Hover', '--c-btn-text', '--c-btn-bg-hover', 4.5),
        ('aktiver Eintrag', '--c-active-text', '--c-active-bg', 4.5), ('Hover-Eintrag', '--c-hover-text', '--c-hover-bg', 4.5),
    ]
    zeile = []
    for label, fgk, bgk, need in paare:
        b = over(get(bgk), bg)
        f = over(get(fgk), b)
        r = ratio(f, b); n += 1
        zeile.append(f"{label} {r:.2f}")
        if r < need:
            fails.append(f"{name}: {label} {r:.2f}:1 (nötig {need}:1) – {scope[fgk]} auf {scope[bgk]}")
    ring = max(ratio(over(get('--c-focus-ring'), bg), bg), ratio(over(get('--c-focus-halo'), bg), bg)); n += 1
    zeile.append(f"Fokus {ring:.2f}")
    if ring < 3:
        fails.append(f"{name}: Fokusring {ring:.2f}:1 (nötig 3:1)")
    print(f"  {name:<22} " + ' · '.join(zeile))

base = {k: v for k, v in palette.items() if k.startswith('--c-')}
flaechen = {
    'Vollpink':           '.fs-surface-brand',
    'Hellpink':           '.fs-surface-light',
    'Leiste':             '.fs-ctx-deep',
    'Weiß (Menü, Banner)': '.fs-ctx-light',
}
karte = rule('.fs-card')
for name, sel in flaechen.items():
    scope = {**base, **rule(sel)}
    pruefe(name, scope)
    # Karte auf dieser Fläche: die Karte belegt ihre Tokens aus denen der Fläche.
    k = {**scope}
    for key, val in karte.items():
        # Selbstbezüge (--c-text: var(--c-card-text)) gegen den Flächen-Scope auflösen
        m = re.fullmatch(r'var\(\s*(--c-[\w-]+)\s*\)', val)
        k[key] = scope[m.group(1)] if m and m.group(1) in scope else val
    k['--c-bg'] = scope['--c-card']
    pruefe('  Karte auf ' + name, k)

# Foto: weißer Text über dem Abdunkler - Untergrund schlimmstenfalls weiß.
scrim = parse(palette['--fs-scrim'], palette)
r = ratio((255, 255, 255, 1), over(scrim, (255, 255, 255, 1))); n += 1
print(f"  {'Foto (Weiß, Scrim auf Weiß)':<22} Text {r:.2f}  (Bild unter dem Scrim: im Browser gemessen)")
if r < 4.5:
    fails.append(f"Foto: Weiß auf Abdunkler über hellstem Bild {r:.2f}:1")

# Referenzwerte des Briefings (nur Ausgabe): Weiß auf den Pinkstufen.
ref = ' · '.join(f"{h} {ratio((255,255,255,1), hexrgb(h)):.2f}" for h in ('#6d0d45', '#b81d5e', '#d12487', '#e62775'))
print(f"  Referenz Weiß auf: {ref}  (#e62775 nur für großen Text)")

print()
for f in fails: print("  FEHLER: " + f)
if fails:
    print(f"\nKONTRAST: {len(fails)} Fehler"); sys.exit(1)
print(f"  ok: {n} Paare auf {len(flaechen)} Flächen und ihren Karten erfüllen AA")
print("\nKONTRAST: bestanden")
