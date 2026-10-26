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

print(f"  Kontext-Container im Stylesheet: {len(containers)}")
if hits:
    for line, sel, prop, val in hits:
        print(f"  FEHLER: main.css:{line}  {sel}  {{ {prop}: {val} }}")
    print(f"\nKONTEXT-TOKENS: {len(hits)} Rohfarbe(n) innerhalb eines Kontexts - "
          f"stattdessen --c-* verwenden oder ein neues Kontext-Token anlegen")
    sys.exit(1)
print("  ok: keine Rohfarbe innerhalb eines Farbkontexts")
print("\nKONTEXT-TOKENS: bestanden")
