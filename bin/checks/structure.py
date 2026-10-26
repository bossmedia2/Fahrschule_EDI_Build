#!/usr/bin/env python3
"""Struktur-Prüfung: Seitenvorlagen, Start-Patterns und Seitenhierarchie.

Diese Fehler fallen weder beim Lint noch beim Rendern auf, schlagen aber im
Backend zu: eine Vorlage ohne Datei erscheint nicht im Auswahlfeld, ein
Pattern mit kaputtem Block-Markup legt den Muster-Dialog lahm, und eine
Klassenseite ohne gültigen Bereich erzeugt eine Brotkrume ins Leere.
"""
import pathlib, json, os, re, subprocess, sys

# Pfade am eigenen Ort verankert: läuft aus jedem Verzeichnis.
root = sys.argv[1] if len(sys.argv) > 1 else str(pathlib.Path(__file__).resolve().parents[2])
theme = os.path.join(root, 'fahrschule-edi')
rd = lambda p: open(p, encoding='utf-8', errors='replace').read()
fails = []
def fail(m): fails.append(m); print("  FEHLER: " + m)
def ok(m):   print("  ok: " + m)

# --- 1) Block-Theme oder klassisch - nie beides ----------------------------
# Sobald templates/index.html existiert, behandelt WordPress das Theme als
# Block-Theme und ignoriert jede PHP-Vorlage. Ein Theme mit templates/ UND
# PHP-Vorlagen ist der einzige wirklich kaputte Zustand (Briefing AP 2).
tdir, pdir_ = os.path.join(theme, 'templates'), os.path.join(theme, 'parts')
is_block = os.path.exists(os.path.join(tdir, 'index.html'))
style = rd(os.path.join(theme, 'style.css'))
fse_tag = re.search(r'^Tags:.*\bfull-site-editing\b', style, re.M) is not None
PHP_TEMPLATES = re.compile(r'^(index|header|footer|front-page|home|page(-[a-z0-9-]+)?|single(-[a-z0-9_-]+)?|archive(-[a-z0-9_-]+)?|search|404|singular|attachment|category|tag|author|date|taxonomy(-[a-z0-9_-]+)?)\.php$')
php_tpls = sorted(f for f in os.listdir(theme) if PHP_TEMPLATES.match(f))
if is_block:
    if php_tpls:
        fail("Block-Theme (templates/index.html) mit PHP-Vorlagen daneben: " + ", ".join(php_tpls))
    if not fse_tag:
        fail("Block-Theme ohne Tag full-site-editing in style.css")
elif os.path.isdir(tdir) and os.listdir(tdir):
    fail("templates/ ohne index.html: halber Umbau - entweder index.html anlegen oder templates/ entfernen")
elif fse_tag:
    fail("klassisches Theme trägt den Tag full-site-editing")
if not fails:
    ok("Block-Theme, keine PHP-Vorlage daneben" if is_block else "klassisches Theme, kein templates/, kein FSE-Tag")

# --- 1b) Vorlagen, Parts, theme.json ---------------------------------------
tj = json.loads(rd(os.path.join(theme, 'theme.json')))
tfiles = sorted(f[:-5] for f in os.listdir(tdir) if f.endswith('.html')) if os.path.isdir(tdir) else []
pfiles = sorted(f[:-5] for f in os.listdir(pdir_) if f.endswith('.html')) if os.path.isdir(pdir_) else []
custom = {t['name']: t for t in tj.get('customTemplates', [])}
parts_decl = {t['name'] for t in tj.get('templateParts', [])}
for n in custom:
    if n not in tfiles: fail(f"theme.json customTemplates: '{n}' ohne templates/{n}.html")
    if 'page' not in custom[n].get('postTypes', []): fail(f"customTemplates '{n}': postTypes ohne 'page' - erscheint nicht in der Vorlagenauswahl")
for n in tfiles:
    if n.startswith('page-') and n not in custom:
        fail(f"templates/{n}.html fehlt in theme.json customTemplates - im Editor nicht auswählbar")
for n in parts_decl:
    if n not in pfiles: fail(f"theme.json templateParts: '{n}' ohne parts/{n}.html")
for n in pfiles:
    if n not in parts_decl: fail(f"parts/{n}.html fehlt in theme.json templateParts")
if is_block and tfiles:
    ok(f"{len(tfiles)} Vorlagen, {len(custom)} davon auswählbar, {len(pfiles)} Parts - alle in theme.json")
# Titel der auswählbaren Vorlagen wie im Briefing (Überarbeitung, Oktober 2026).
TITEL = {'page-uebersicht': 'Übersichtsseite', 'page-bereich': 'Bereichsseite',
         'page-kurs': 'Kursseite (Führerscheinklasse)', 'page-beruf': 'Beruf & Weiterbildung',
         'page-standort': 'Standortseite', 'page-team': 'Teamseite', 'page-theorie': 'Theorieunterricht',
         'page-faq': 'FAQ', 'page-kontakt': 'Kontakt & Anmeldung', 'page-recht': 'Rechtstext'}
falsch = [f"{n}: „{custom[n]['title']}“ statt „{t}“" for n, t in TITEL.items() if n in custom and custom[n]['title'] != t]
fehlt = [n for n in TITEL if n not in custom]
if falsch or fehlt or len(custom) != 10:
    fail(f"auswählbare Vorlagen: {len(custom)} statt 10; fehlend {fehlt}; Titel {falsch}")
else:
    ok("10 auswählbare Vorlagen mit den Titeln des Briefings")

# --- 2) Registrierte Blöcke einsammeln (aus der Konfiguration) ------------
cfg = rd(os.path.join(theme, 'inc/blocks-config.php'))
known = set(re.findall(r"'(fahrschule/[a-z0-9-]+)'\s*=>\s*array\(", cfg))

# --- 2b) Vorlagen und Parts parsen ----------------------------------------
CORE_OK = {'template-part', 'group', 'post-content', 'post-title', 'post-date', 'post-excerpt',
           'query', 'post-template', 'query-pagination', 'query-pagination-previous',
           'query-pagination-next', 'query-no-results', 'query-title', 'search', 'paragraph', 'heading'}
parse_php = r'''
require $argv[1]."/fse.php";
$out=[];
foreach(array_slice($argv,2) as $f){
  try{ $bl=fs_fse_parse(file_get_contents($f)); }
  catch(\Throwable $e){ $out[$f]=["error"=>$e->getMessage()]; continue; }
  $names=[]; $parts=[];
  $walk=function($bs) use (&$walk,&$names,&$parts){ foreach($bs as $b){ if($b["blockName"]){ $names[]=$b["blockName"]; if($b["blockName"]==="core/template-part") $parts[]=$b["attrs"]["slug"]??""; } $walk($b["innerBlocks"]); } };
  $walk($bl); $out[$f]=["names"=>$names,"parts"=>$parts];
}
echo json_encode($out);
'''
files = [os.path.join(tdir, f + '.html') for f in tfiles] + [os.path.join(pdir_, f + '.html') for f in pfiles]
if files:
    r = subprocess.run(['php', '-r', parse_php, os.path.join(root, 'bin/checks')] + files, capture_output=True, text=True)
    if r.returncode != 0:
        fail("Vorlagen-Parser: " + r.stderr.strip()[:300])
    else:
        bad0 = len(fails)
        for f, res in json.loads(r.stdout).items():
            rel = os.path.relpath(f, theme)
            if 'error' in res:
                fail(f"{rel}: {res['error']}"); continue
            for n in res['names']:
                if n.startswith('fahrschule/') and n not in known:
                    fail(f"{rel}: Block {n} ist nicht registriert")
                elif n.startswith('core/') and n[5:] not in CORE_OK:
                    fail(f"{rel}: Kern-Block {n} nicht freigegeben (Prüfstand bin/checks/fse.php kennt ihn nicht)")
            for sl in res['parts']:
                if sl not in pfiles: fail(f"{rel}: Template-Part '{sl}' existiert nicht")
            if rel.startswith('templates'):
                for need in ('header', 'footer'):
                    if res['parts'].count(need) != 1:
                        fail(f"{rel}: Part '{need}' {res['parts'].count(need)}× statt genau einmal - Seite ohne/mit doppelter Navigation bzw. Footer")
        if len(fails) == bad0:
            ok(f"{len(files)} Vorlagen/Parts parsen, alle Blöcke registriert, Header und Footer je genau einmal")

# --- 3) Start-Patterns ----------------------------------------------------
# Die Muster sind PHP (Inhalt aus fahrschule_startmuster()) - geprüft wird,
# was WordPress beim Einbinden der Datei tatsächlich ausgibt.
pdir = os.path.join(theme, 'patterns')
php_pat = r'''
$GLOBALS["__theme"]=$argv[1]; $GLOBALS["__blocks"]=[];
require $argv[2]."/wp-stub.php";
define("FAHRSCHULE_DIR",$argv[1]); define("FAHRSCHULE_URI","/u"); define("FAHRSCHULE_VERSION","1");
foreach(["helpers","pdf-texts","media","pages","seo","render-callbacks","blocks-config"] as $f) require $argv[1]."/inc/".$f.".php";
$out=["pat"=>[],"folge"=>[]];
foreach(glob($argv[1]."/patterns/*.php") as $p){ ob_start(); include $p; $out["pat"][basename($p)]=["src"=>file_get_contents($p),"html"=>ob_get_clean()]; }
// Flächenfolge je Startmuster (Seitenrahmen der Vorlage + Muster).
$cfg=fahrschule_blocks_config();
foreach(fahrschule_startmuster() as $k=>$m){
  // Seit 3.2 steht der Seitenrahmen mit im Muster: die Folge ist die des Markups.
  preg_match_all('#<!--\s+wp:([a-z0-9-]+/[a-z0-9-]+)(?:\s+(\{.*?\}))?\s+/?-->#', fahrschule_startmuster_markup($k), $mm, PREG_SET_ORDER);
  $folge=[]; foreach($mm as $b){ $f=fahrschule_block_flaeche($b[1], isset($b[2])?(array)json_decode($b[2],true):[], $cfg); if($f!=="") $folge[]=$f; }
  $out["folge"][$k]=$folge;
}
echo json_encode($out);
'''
res = subprocess.run(['php', '-r', php_pat, theme, os.path.join(root, 'bin/checks')], capture_output=True, text=True)
if res.returncode != 0 or not res.stdout.startswith('{'):
    fail("Patterns nicht ladbar: " + (res.stderr or res.stdout).strip()[:300])
else:
    data = json.loads(res.stdout)
    pats = data['pat']
    starters = 0
    for f, p in sorted(pats.items()):
        head = p['src'][:900]
        for key in ('Title:', 'Slug:', 'Categories:'):
            if key not in head:
                fail(f"patterns/{f}: Kopfzeile '{key}' fehlt")
        if 'Block Types:' in head and 'core/post-content' in head and 'Post Types: page' in head:
            starters += 1
        if 'fahrschule-seiten' not in head:
            fail(f"patterns/{f}: nicht in der Kategorie fahrschule-seiten")
        if '<!-- wp:' not in p['html']:
            fail(f"patterns/{f}: gibt kein Block-Markup aus")
        for m in re.finditer(r'<!--\s*wp:([a-z0-9-]+/[a-z0-9-]+)(\s+(\{.*?\}))?\s*/?-->', p['html'], re.S):
            name, attrs = m.group(1), m.group(3)
            if name.startswith('fahrschule/') and name not in known:
                fail(f"patterns/{f}: Block {name} ist nicht registriert")
            if attrs:
                try:
                    json.loads(attrs)
                except Exception as e:
                    fail(f"patterns/{f}: Attribut-JSON von {name} ist ungültig ({e})")
    if starters != 11 or len(pats) != 11:
        fail(f"{len(pats)} Patterns, {starters} als Startmuster - erwartet 11 (eins je Vorlage plus Landingpage)")
    else:
        ok("11 Startmuster für neue Seiten (eins je auswählbarer Vorlage plus Landingpage), Kategorie fahrschule-seiten")
    kurz = {'vollpink': 'V', 'hellpink': 'H', 'leiste': 'L', 'foto': 'F'}
    bad = []
    for k, seq in data['folge'].items():
        s_ = ''.join(kurz[x] for x in seq)
        if 'VVV' in s_:
            bad.append(f"{k}: {s_}")
    if bad:
        fail("Startmuster mit dreimal Vollpink (samt Seitenrahmen): " + "; ".join(bad))
    else:
        ok("Startmuster im Rhythmus, samt Seitenrahmen: " + " · ".join(f"{k} {''.join(kurz[x] for x in v)}" for k, v in data['folge'].items()))

# --- 4) Hierarchie --------------------------------------------------------
php = r'''
$GLOBALS["__theme"]=$argv[1];
require $argv[2]."/wp-stub.php";
define("FAHRSCHULE_DIR",$argv[1]); define("FAHRSCHULE_URI","/u"); define("FAHRSCHULE_VERSION","1");
foreach(["helpers","pdf-texts","media","pages"] as $f) require $argv[1]."/inc/".$f.".php";
$m=fahrschule_page_map(); $out=[];
foreach($m as $k=>$p){ $out[$k]=["parent"=>$p["parent"]??"","slug"=>$p["slug"]??"","nav"=>$p["nav"]??"","template"=>$p["template"]??"page"]; }
echo json_encode($out);
'''
res = subprocess.run(['php', '-r', php, theme, os.path.join(root, 'bin/checks')],
                     capture_output=True, text=True)
if res.returncode != 0:
    fail("Seitenregistry nicht lesbar: " + res.stderr.strip()[:200])
else:
    pages = json.loads(res.stdout)
    notpl = [f"{k} -> {v['template']}" for k, v in pages.items() if is_block and v['template'] not in tfiles]
    if notpl:
        fail("Registry-Seite verweist auf fehlende Vorlage: " + ", ".join(notpl))
    elif is_block:
        ok(f"{len(pages)} Registry-Seiten, jede mit vorhandener Vorlage")
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
