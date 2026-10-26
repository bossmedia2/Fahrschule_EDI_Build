#!/usr/bin/env python3
"""Startseite nach PDF (AP 4) und Stammdaten-Abgleich.

Liest die gerenderten Seiten (pages.json aus smoke.php) und die Texte/Stamm-
daten direkt aus dem Theme. Geprüft wird:

1. Reihenfolge: Die Startseite hat die zwölf Abschnitte in der Reihenfolge
   des Briefings - gemessen an den PDF-Markern im gerenderten HTML.
2. Keine Doppelung: Kein PDF-Abschnitt steht VOLL (data-pdf-section) auf
   der Startseite und zugleich auf einer Unterseite. Anrisse tragen
   data-pdf-teaser, Seitenköpfe data-pdf-head - beides zählt nicht.
3. Wortgleich: Label und Überschrift jedes PDF-Abschnitts der Startseite
   stehen so im HTML wie in inc/pdf-texts.php.
4. NAP: Adresse, PLZ, Telefon und E-Mail je Standort stimmen zwischen PDF
   (inc/pdf-texts.php), Stammdaten (inc/seo.php), sichtbarer Seite und
   JSON-LD überein; Öffnungszeiten zwischen Text und Schema.
"""
import html, json, os, pathlib, re, subprocess, sys, tempfile

# Pfade am eigenen Ort verankert. Ohne Ausgabeverzeichnis (Aufruf allein)
# rendert das Skript die Seiten selbst über smoke.php in ein Temp-Verzeichnis.
root = sys.argv[1] if len(sys.argv) > 1 else str(pathlib.Path(__file__).resolve().parents[2])
out = sys.argv[2] if len(sys.argv) > 2 else None
if out is None:
    out = tempfile.mkdtemp(prefix='fs-startseite-')
    subprocess.run(['php', os.path.join(root, 'bin/checks/smoke.php'), os.path.join(root, 'fahrschule-edi')],
                   env={**os.environ, 'FS_OUT': out}, capture_output=True)
theme = os.path.join(root, 'fahrschule-edi')
fails = []
def fail(m): fails.append(m); print("  FEHLER: " + m)
def ok(m):   print("  ok: " + m)

pages = json.load(open(os.path.join(out, 'pages.json'), encoding='utf-8'))

php = r'''
$GLOBALS["__theme"]=$argv[1];
require $argv[2]."/wp-stub.php";
define("FAHRSCHULE_DIR",$argv[1]); define("FAHRSCHULE_URI","/u"); define("FAHRSCHULE_VERSION","1");
foreach(["helpers","pdf-texts","media","pages","seo"] as $f) require $argv[1]."/inc/".$f.".php";
$pdf=[]; foreach(["f1","f2","f3","f4","f5","f6","f7","f8","f9","f10"] as $k){ $p=fahrschule_pdf($k); $pdf[$p["id"]]=["label"=>$p["label"],"headline"=>$p["headline"]]; }
$hours=[]; foreach(["Duisburg","Essen","Gelsenkirchen"] as $c){ $hours[$c]=fahrschule_location_hours_text($c); }
ob_start(); fahrschule_json_ld(); $ld=ob_get_clean();
echo json_encode(["pdf"=>$pdf,"f8"=>fahrschule_pdf("f8")["locations"],"biz"=>fahrschule_business_data()["locations"],"hours"=>$hours,"ld"=>$ld]);
'''
r = subprocess.run(['php', '-r', php, theme, os.path.join(root, 'bin/checks')], capture_output=True, text=True)
if r.returncode != 0:
    print(r.stderr[-500:]); sys.exit(1)
data = json.loads(r.stdout)

home = pages['home']
main = home[home.index('<main'):]

# --- 1) Reihenfolge -----------------------------------------------------------
seq = []
for m in re.finditer(r'<section\b([^>]*)>', main):
    attrs = m.group(1)
    pm = re.search(r'data-pdf-(?:section|teaser)="(F\d+)"', attrs)
    if 'fs-trustbar' in attrs:
        seq.append('Vertrauensleiste')
    elif pm:
        seq.append(pm.group(1))
    else:
        # Abschnitt ohne PDF-Marker: am Inhalt erkennen (Theorie-Anriss, Bewertungen)
        end = main.find('</section>', m.end())
        body = main[m.end():end]
        if 'href="' in body and '/theorieunterricht/' in body and 'fs-anriss' in body:
            seq.append('Theorie')
        elif 'Bewertungen' in body and 'google.' in body:
            seq.append('Bewertungen')
# Die Beruf-Kacheln (page-teasers) tragen ihren Marker am Link IN einem Abschnitt.
beruf = re.findall(r'<a class="fs-teaser"[^>]*data-pdf-teaser="(F\d+)"', main)
want = ['F1', 'Vertrauensleiste', 'F3', 'F2', 'F4', 'F7', 'F8', 'F9', 'Theorie', 'Bewertungen', 'F10']
if seq != want:
    fail("Startseite: Abschnittsfolge " + " → ".join(seq) + "\n           erwartet " + " → ".join(want) + " (F5/F6 als Kacheln zwischen F7 und F8)")
elif beruf != ['F5', 'F6']:
    fail(f"Startseite: Abschnitt Beruf zeigt {beruf} statt F5, F6")
else:
    # Position des Beruf-Abschnitts: zwischen F7 und F8
    i7 = main.find('data-pdf-teaser="F7"'); i5 = main.find('data-pdf-teaser="F5"'); i8 = main.find('="F8"')
    if not (i7 < i5 < i8):
        fail("Startseite: Beruf (F5+F6) steht nicht zwischen Moderne Ausbildung (F7) und Standorten (F8)")
    else:
        ok("Startseite: zwölf Abschnitte in Briefing-Reihenfolge – Hero, Vertrauensleiste, Klassen, Über uns, "
           "Leistungen, Moderne Ausbildung, Beruf, Standorte, Team, Theorie, Bewertungen, Anmeldung")

# --- 2) Keine Doppelung ------------------------------------------------------
full = lambda h: set(re.findall(r'<section\b[^>]*data-pdf-section="(F\d+)"', h))
home_full = full(home)
dups = []
for key, h in pages.items():
    if key == 'home':
        continue
    both = home_full & full(h)
    if both:
        dups.append(f"{key}: {', '.join(sorted(both))}")
if dups:
    fail("PDF-Abschnitt voll auf Startseite UND Unterseite – " + "; ".join(dups))
else:
    ok(f"kein PDF-Abschnitt doppelt: voll auf der Startseite nur {', '.join(sorted(home_full))}, alles andere als Anriss")

# --- 3) Wortgleich -----------------------------------------------------------
bad = []
for pid in sorted(set(re.findall(r'data-pdf-(?:section|teaser)="(F\d+)"', home))):
    if pid == 'F1':
        continue  # Hero: Überschrift mit Hervorhebung, eigener Aufbau
    p = data['pdf'][pid]
    for part in ('label', 'headline'):
        if html.escape(p[part], quote=False) not in home and p[part] not in home:
            bad.append(f"{pid} {part} „{p[part]}“")
if bad:
    fail("Startseite: PDF-Text nicht wortgleich: " + "; ".join(bad))
else:
    ok("Startseite: Label und Überschrift aller PDF-Abschnitte wortgleich zu inc/pdf-texts.php")

# --- 4) NAP ------------------------------------------------------------------
def digits(tel):
    d = re.sub(r'\D', '', tel)
    return '0' + d[2:] if d.startswith('49') else d
nap_bad = []
ld = data['ld']
for city, p in data['f8'].items():
    b = data['biz'].get(city.lower())
    if not b:
        nap_bad.append(f"{city}: fehlt in inc/seo.php"); continue
    if p['street'] != b['street']: nap_bad.append(f"{city}: Straße PDF „{p['street']}“ ≠ Stammdaten „{b['street']}“")
    if not p['zip'].startswith(b['zip']): nap_bad.append(f"{city}: PLZ PDF „{p['zip']}“ ≠ „{b['zip']}“")
    if digits(p['phone']) != digits(b['phone']): nap_bad.append(f"{city}: Telefon PDF „{p['phone']}“ ≠ „{b['phone']}“")
    if p['email'] != b['email']: nap_bad.append(f"{city}: E-Mail PDF „{p['email']}“ ≠ „{b['email']}“")
    # sichtbar: Standortseite und Übersicht
    for key in (city.lower(), 'standorte'):
        h = html.unescape(pages.get(key, ''))
        for v in (p['street'], p['email']):
            if v not in h: nap_bad.append(f"{city}: „{v}“ nicht sichtbar auf /{key}/")
        if digits(p['phone']) not in re.sub(r'\D', '', h): nap_bad.append(f"{city}: Telefon nicht sichtbar auf /{key}/")
    # JSON-LD
    for v in (b['street'], b['email'], b['phone']):
        if json.dumps(v)[1:-1] not in ld and v not in ld: nap_bad.append(f"{city}: „{v}“ fehlt im JSON-LD")
    # Öffnungszeiten: Text ↔ Schema
    txt = data['hours'][city]
    for o in b['opening']:
        if o['open'] not in txt or o['close'] not in txt:
            nap_bad.append(f"{city}: Schema {o['open']}–{o['close']} fehlt im Text „{txt}“")
if nap_bad:
    for x in nap_bad: fail(x)
else:
    ok("NAP: Adresse, PLZ, Telefon, E-Mail je Standort identisch in PDF, Stammdaten, Seite und JSON-LD; Zeiten Text = Schema")

print()
if fails:
    print(f"STARTSEITE/NAP: {len(fails)} Fehler"); sys.exit(1)
print("STARTSEITE/NAP: bestanden")
