#!/usr/bin/env python3
"""Statische Prüfung: keine Rohfarbe innerhalb eines Farbkontexts.

Das Farbsystem in main.css hat zwei Ebenen. Die Rohpalette (--fs-*) und die
Kontext-Tokens (--c-*), die jede Fläche für ihren Inhalt neu belegt. Wer in
einem Kontext `color: var(--fs-light)` schreibt, setzt Weiß fest - egal ob die
Fläche darunter hell oder dunkel ist. Genau daraus entstanden bis Oktober 2026
vier Weiß-auf-Weiß-Fehler, zuletzt der unsichtbare aktive Flyout-Eintrag.

Kontexte werden nicht von Hand gelistet, sondern aus dem Stylesheet abgeleitet:
jeder Selektor einer Regel, die `--c-text` setzt, ist ein Kontext-Container.
Gemeldet wird eine Farbdeklaration mit Rohfarbe, wenn
  - der Selektor INNERHALB eines Containers liegt (Nachfahre), oder
  - er den Container selbst trifft und die Schriftfarbe setzt.
Der Hintergrund des Containers selbst ist erlaubt - er definiert die Fläche.
"""
import pathlib, os, re, sys

# Pfade am eigenen Ort verankert: läuft aus jedem Verzeichnis.
root = sys.argv[1] if len(sys.argv) > 1 else str(pathlib.Path(__file__).resolve().parents[2])
css_path = os.path.join(root, 'fahrschule-edi/assets/css/main.css')
css = open(css_path, encoding='utf-8').read()

RAW = re.compile(r'var\(\s*--fs-(light|brand[a-z-]*|rose|tint)\s*\)')
COLOR_PROPS = ('color', 'background', 'background-color', 'border-color',
               'outline-color', 'fill', 'stroke')

def strip_comments(s):
    return re.sub(r'/\*.*?\*/', lambda m: '\n' * m.group(0).count('\n'), s, flags=re.S)

def rules(s):
    """Flache Liste (selektor, deklarationen, zeile) - @media wird aufgelöst."""
    out, i, depth, start, stack = [], 0, 0, 0, []
    s = strip_comments(s)
    buf_start = 0
    while i < len(s):
        c = s[i]
        if c == '{':
            head = s[buf_start:i].strip()
            line = s.count('\n', 0, buf_start) + 1 + len(s[buf_start:i]) - len(s[buf_start:i].lstrip('\n'))
            if head.startswith('@'):
                stack.append(None)          # Gruppenregel, nur Inhalt zählt
                buf_start = i + 1
            else:
                j = s.find('}', i)
                out.append((head, s[i + 1:j], s.count('\n', 0, i) + 1))
                i = j
                buf_start = j + 1
        elif c == '}':
            if stack:
                stack.pop()
            buf_start = i + 1
        i += 1
    return out

def decls(body):
    for part in body.split(';'):
        if ':' in part:
            k, v = part.split(':', 1)
            yield k.strip().lower(), v.strip()

def compounds(sel):
    # Kombinatoren auf Leerzeichen normalisieren, dann zerlegen
    sel = re.sub(r'\s*[>+~]\s*', ' ', sel.strip())
    return [c for c in sel.split() if c]

def classes(compound):
    return set(re.findall(r'\.([A-Za-z0-9_-]+)', compound))

all_rules = rules(css)

# 1) Kontext-Container ermitteln
containers = set()
for sel, body, _ in all_rules:
    if re.search(r'--c-text\s*:', body):
        for one in sel.split(','):
            comps = compounds(one)
            if comps:
                containers |= classes(comps[-1])
containers.discard('')

# 2) Verstöße suchen
hits = []
for sel, body, line in all_rules:
    for one in sel.split(','):
        comps = compounds(one)
        if not comps:
            continue
        inside = any(classes(c) & containers for c in comps[:-1])
        is_container = bool(classes(comps[-1]) & containers)
        for prop, val in decls(body):
            if prop.startswith('--') or prop not in COLOR_PROPS or not RAW.search(val):
                continue
            if inside or (is_container and prop == 'color'):
                hits.append((line, one.strip(), prop, val))

# 3) Kein „Pink Tief“ mehr: #6d0d45 / --fs-brand-deep / rgba(109,13,69,…) nur
#    noch als Schattenfarbe (box-shadow). Gilt für alle Stylesheets und theme.json.
DEEP = re.compile(r'#6d0d45|--fs-brand-deep|rgba?\(\s*109\s*,\s*13\s*,\s*69', re.I)
deep = []
theme = os.path.join(root, 'fahrschule-edi')
for rel in ('assets/css/main.css', 'assets/css/editor.css'):
    p = os.path.join(theme, rel)
    if not os.path.exists(p):
        continue
    for sel, body, line in rules(open(p, encoding='utf-8').read()):
        for prop, val in decls(body):
            if DEEP.search(prop + ':' + val) and prop != 'box-shadow':
                deep.append(f"{rel}:{line}  {sel.strip()[:60]}  {{ {prop}: {val} }}")
tj = open(os.path.join(theme, 'theme.json'), encoding='utf-8').read()
if DEEP.search(tj) or '"brand-deep"' in tj:
    deep.append('theme.json: Palette enthält noch Pink Tief (#6d0d45)')
for root_dir in ('inc', 'patterns', 'templates', 'parts'):
    for dp, _, fn in os.walk(os.path.join(theme, root_dir)):
        for f in fn:
            t = open(os.path.join(dp, f), encoding='utf-8').read()
            if DEEP.search(t) or 'brand-deep' in t:
                deep.append(f"{root_dir}/{f}: Pink Tief im Markup")

# 4) Hover/Fokus nur aus dem Kontext: ein :hover, das eine Rohfarbe setzt,
#    springt in eine Farbe, die zur Fläche nicht passen muss.
HOVER_RAW = re.compile(r'var\(\s*--fs-(?!scrim)|#[0-9a-f]{3,8}\b|rgba?\(', re.I)
hover = []
for sel, body, line in all_rules:
    if ':hover' not in sel and ':focus' not in sel:
        continue
    for prop, val in decls(body):
        if prop in COLOR_PROPS and HOVER_RAW.search(val):
            hover.append((line, sel.strip()[:70], prop, val))

print(f"  Kontext-Container im Stylesheet: {len(containers)}")
for x in deep:
    print("  FEHLER: Pink Tief außerhalb eines Schattens: " + x)
if not deep:
    print("  ok: kein Pink Tief (#6d0d45) außerhalb von box-shadow, nicht in der Palette")
for line, sel, prop, val in hover:
    print(f"  FEHLER: main.css:{line}  {sel}  {{ {prop}: {val} }} – Hover/Fokus nur über --c-*")
if not hover:
    print("  ok: Hover und Fokus setzen Farben nur aus Kontext-Tokens")
if deep or hover:
    hits = hits or []
    print(f"\nKONTEXT-TOKENS: {len(deep) + len(hover) + len(hits)} Fehler")
    sys.exit(1)
if hits:
    for line, sel, prop, val in hits:
        print(f"  FEHLER: main.css:{line}  {sel}  {{ {prop}: {val} }}")
    print(f"\nKONTEXT-TOKENS: {len(hits)} Rohfarbe(n) innerhalb eines Kontexts - "
          f"stattdessen --c-* verwenden oder ein neues Kontext-Token anlegen")
    sys.exit(1)
print("  ok: keine Rohfarbe innerhalb eines Farbkontexts")
print("\nKONTEXT-TOKENS: bestanden")
