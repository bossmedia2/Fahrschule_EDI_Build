/**
 * Layout- und Kontrastprüfung im echten Browser.
 *
 * Erwartet ein Verzeichnis mit den vom Smoke-Test gerenderten Seiten.
 * Geprüft wird, was sich nur am gerenderten Dokument zeigt:
 *   - bricht die Navigationszeile um oder überlappt sie den CTA-Block
 *   - entsteht horizontales Scrollen (auch bei 200% Zoom, WCAG 1.4.4)
 *   - erfüllen Textfarben AA, auch im gescrollten Zustand der Navigation
 *   - ist der Fokusring auf jeder Fläche erkennbar (SC 1.4.11)
 */
import { createRequire } from 'node:module';

/* Playwright wird über require() aufgelöst, nicht über import: so greifen
   NODE_PATH und eine global installierte Fassung, die ein ESM-import nicht
   findet. Fehlt das Paket, meldet gate.sh das als übersprungen. */
function loadPlaywright() {
  const req = createRequire(import.meta.url);
  const candidates = [
    'playwright',
    ...(process.env.NODE_PATH || '').split(':').filter(Boolean).map(d => d + '/playwright'),
    '/opt/node-tools/node_modules/playwright',
  ];
  for (const c of candidates) {
    try { return req(c); } catch { /* nächster Kandidat */ }
  }
  console.error('playwright nicht gefunden – npm i -D playwright');
  process.exit(2);
}
const { chromium } = loadPlaywright();

/* Verzeichnis mit den gerenderten Seiten. Ohne Argument (Aufruf allein)
   rendert das Skript selbst: smoke.php und mkpages.php in ein Temp-Verzeichnis,
   Pfade am eigenen Ort verankert. */
let dir = process.argv[2];
if (!dir) {
  const { mkdtempSync } = await import('node:fs');
  const { execFileSync } = await import('node:child_process');
  const { tmpdir } = await import('node:os');
  const here = new URL('.', import.meta.url).pathname;
  const theme = new URL('../../fahrschule-edi', import.meta.url).pathname;
  dir = mkdtempSync(tmpdir() + '/fs-browser-');
  const env = { ...process.env, FS_OUT: dir };
  try { execFileSync('php', [here + 'smoke.php', theme], { env, stdio: 'ignore' }); } catch { /* Smoke meldet selbst */ }
  execFileSync('php', [here + 'mkpages.php', theme], { env, stdio: 'inherit' });
}

const WIDTHS = [360, 390, 414, 600, 640, 768, 900, 1024, 1100, 1179, 1239, 1240, 1280, 1366, 1440, 1512, 1920];
// Seitenbreiten des Briefings: jede Seite wird bei allen sechs gemessen.
const SIX    = [390, 768, 1024, 1280, 1512, 1920];
const ZOOM   = [960, 720, 640, 512];           // 1920/1440/1280/1024 bei 200%
let fails = [];
const fail = m => { fails.push(m); console.log('  FEHLER: ' + m); };
const ok   = m => console.log('  ok: ' + m);

const HELPERS = `
function lum(c){const s=c.map(v=>{v/=255;return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4)});return .2126*s[0]+.7152*s[1]+.0722*s[2]}
function parse(c){const m=c.match(/rgba?\\(([^)]+)\\)/);if(!m)return null;const p=m[1].split(',').map(x=>parseFloat(x));return {r:p[0],g:p[1],b:p[2],a:p.length>3?p[3]:1}}
function over(f,b){return [f.r*f.a+b.r*(1-f.a), f.g*f.a+b.g*(1-f.a), f.b*f.a+b.b*(1-f.a)]}
// Hintergrund hinter einem Element. Normalfall: die Vorfahren. Liegt das
// Element aber in einer FIXIERTEN Ebene ohne eigene deckende Fläche (die
// Leiste am Seitenanfang), sieht man nicht die Vorfahren, sondern den
// Seiteninhalt, der gerade darunter liegt - ein Lauf über die Vorfahren
// mäße dort gegen das Pink des body und übersähe Weiß auf Rosé.
function effBg(el){let e=el;const st=[];let fixedHost=null;
  while(e&&e!==document.documentElement){const cs=getComputedStyle(e);const c=parse(cs.backgroundColor);if(c&&c.a>0)st.push(c);if(c&&c.a===1)break;
    if(cs.position==='fixed'){fixedHost=e;break}e=e.parentElement}
  let base=[255,255,255];
  if(fixedHost&&!(st.length&&st[st.length-1].a===1)){const r=el.getBoundingClientRect();
    const under=document.elementsFromPoint(r.left+r.width/2,r.top+r.height/2).find(x=>!fixedHost.contains(x)&&x!==document.documentElement);
    if(under) base=effBg(under);}
  for(let i=st.length-1;i>=0;i--)base=over(st[i],{r:base[0],g:base[1],b:base[2]});return base}
function ratio(a,b){const L1=lum(a),L2=lum(b);return (Math.max(L1,L2)+.05)/(Math.min(L1,L2)+.05)}
// Beschriftungen über Foto + Scrim: die abdunkelnde Ebene ist ein GESCHWISTER,
// kein Vorfahr - ein Lauf über die Vorfahren würde hier falsch Alarm schlagen.
const OVERLAY='.fs-tile__cap,.fs-locimg__cap,.fs-vhero__inner,.fs-lochero__inner';
function scanText(){
  const bad=[];
  for(const el of document.querySelectorAll('body *')){
    const cs=getComputedStyle(el);
    if(cs.visibility==='hidden'||cs.display==='none'||parseFloat(cs.opacity)===0) continue;
    if(!el.offsetParent && cs.position!=='fixed') continue;
    if(el.closest(OVERLAY)) continue;
    const txt=[...el.childNodes].filter(n=>n.nodeType===3).map(n=>n.textContent.trim()).join('').trim();
    if(!txt) continue;
    const fgc=parse(cs.color); if(!fgc) continue;
    const bg=effBg(el), fg=over(fgc,{r:bg[0],g:bg[1],b:bg[2]});
    const size=parseFloat(cs.fontSize), wt=parseInt(cs.fontWeight)||400;
    const need=(size>=24||(size>=18.66&&wt>=700))?3:4.5;
    const r=ratio(fg,bg);
    if(r<need) bad.push({sel:el.tagName.toLowerCase()+(el.className&&typeof el.className==='string'?'.'+el.className.trim().split(/\\s+/).join('.'):''),
      txt:txt.slice(0,40), r:+r.toFixed(2), need, color:cs.color, bg:'rgb('+bg.map(Math.round).join(',')+')'});
  }
  return bad;
}
function scanUi(){
  const bad=[];
  for(const el of document.querySelectorAll('.fs-btn,.fs-nav__burger')){
    const cs=getComputedStyle(el);
    if(cs.display==='none'||!el.offsetParent) continue;
    const bg=effBg(el.parentElement||document.body);
    // Ein 0px breiter Rahmen traegt keine Farbe und zaehlt deshalb nicht mit.
    const hasBorder = parseFloat(cs.borderTopWidth) > 0 && cs.borderTopStyle !== 'none';
    const bc=hasBorder?parse(cs.borderTopColor):null, fc=parse(cs.backgroundColor);
    // SC 1.4.11: die KOMPONENTE muss erkennbar sein - dafuer genuegt eine
    // kontrastierende Fuellung ODER ein kontrastierender Rand, und bei einem
    // reinen Icon-Button (Burger) die Strichfarbe des SVG selbst: es erbt sie
    // ueber currentColor aus der color-Eigenschaft des Buttons.
    const ic=el.querySelector('svg')?parse(cs.color):null;
    const rb=bc&&bc.a>0?ratio(over(bc,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    const rf=fc&&fc.a>0?ratio(over(fc,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    const ri=ic&&ic.a>0?ratio(over(ic,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    const r=Math.max(rb,rf,ri);
    if(r<3) bad.push({sel:el.className,r:+r.toFixed(2),fill:cs.backgroundColor,border:cs.borderTopColor,bg:'rgb('+bg.map(Math.round).join(',')+')'});
  }
  return bad;
}
function navGeometry(){
  const links=document.querySelector('.fs-nav__links'); if(!links) return null;
  const cs=getComputedStyle(links);
  const inner=document.querySelector('.fs-nav__inner');
  const act=document.querySelector('.fs-nav__actions');
  const ib=inner.getBoundingClientRect(), lb=links.getBoundingClientRect(), ab=act.getBoundingClientRect();
  const hidden = cs.display==='none';
  // Echte Zeilen: Kinder gruppieren, deren vertikale Bereiche sich überlappen.
  // Über offsetTop allein zu zählen wäre falsch - bei align-items:center haben
  // unterschiedlich hohe Elemente verschiedene Oberkanten in DERSELBEN Zeile.
  let lines=[];
  if(!hidden){
    for(const k of links.children){
      const r=k.getBoundingClientRect(); if(!r.height) continue;
      const hit=lines.find(L=>!(r.bottom<=L.top||r.top>=L.bot));
      if(hit){hit.top=Math.min(hit.top,r.top);hit.bot=Math.max(hit.bot,r.bottom);}
      else lines.push({top:r.top,bot:r.bottom});
    }
  }
  return {hidden, lines:lines.length,
    overflow: !hidden && links.scrollWidth>links.clientWidth+1,
    spill: hidden?0:Math.max(0, Math.round(lb.bottom-ib.bottom), Math.round(ib.top-lb.top)),
    overlapCta: !hidden && lb.right>ab.left+0.5,
    docW:document.documentElement.scrollWidth, winW:window.innerWidth};
}
`;

const browser = await chromium.launch();
const page = await browser.newPage();
/* Harte Regel 6: keine Abrufe bei Dritten ohne Einwilligung. Jede Anfrage,
 * die nicht auf die lokalen Testseiten geht, wird abgefangen und notiert -
 * eine Schrift von Google, ein Karten-iframe vor dem Klick, ein Tracker. */
const fremd = [];
await page.route(/^(?!file:|data:|blob:|about:)/, r => { fremd.push(r.request().url()); r.abort(); });
const home = 'file://' + dir + '/home.html';

// ---------- 1) Navigationslayout -----------------------------------------
console.log('\nNavigationslayout');
for (const w of WIDTHS) {
  await page.setViewportSize({ width: w, height: 900 });
  await page.goto(home);
  await page.waitForTimeout(60);
  const g = await page.evaluate(new Function(HELPERS + 'return navGeometry()'));
  const p = [];
  if (g.lines > 1)   p.push(`${g.lines} Zeilen`);
  if (g.overflow)    p.push('Linkzeile läuft über');
  if (g.spill > 0)   p.push(`${g.spill}px außerhalb der Leiste`);
  if (g.overlapCta)  p.push('überlappt CTA-Block');
  if (g.docW > g.winW + 1) p.push(`horizontaler Scroll (+${g.docW - g.winW}px)`);
  if (p.length) fail(`Navigation bei ${w}px: ${p.join(', ')}`);
}
if (!fails.length) ok(`Navigation einzeilig und überlappungsfrei bei ${WIDTHS.length} Breiten (360-1920px)`);

// ---------- 2) 200% Zoom (WCAG 1.4.4) ------------------------------------
console.log('\nZoom 200% (WCAG 1.4.4)');
let zf = 0;
for (const w of ZOOM) {
  await page.setViewportSize({ width: w, height: 800 });
  await page.goto(home); await page.waitForTimeout(60);
  const g = await page.evaluate(new Function(HELPERS + 'return navGeometry()'));
  if (g.docW > g.winW + 1) { fail(`200% Zoom bei ${w * 2}px: horizontaler Scroll (+${g.docW - g.winW}px)`); zf++; }
  if (g.lines > 1)         { fail(`200% Zoom bei ${w * 2}px: Navigation bricht um`); zf++; }
}
if (!zf) ok('kein horizontaler Scroll, kein Umbruch bei 200% Zoom');

// ---------- 3) Kontrast über alle Seiten ---------------------------------
console.log('\nKontrast (WCAG 2.1 AA)');
const fs = await import('node:fs');
const pages = fs.readdirSync(dir).filter(f => f.endsWith('.html'));
const seen = new Map();
const NOANIM = '*{transition:none!important;animation:none!important}.fs-fade-up{opacity:1!important}';

const collect = async (state) => {
  const r = await page.evaluate(new Function(HELPERS + 'return {t:scanText(),u:scanUi()}'));
  for (const x of r.t) seen.set('T' + state + x.sel + x.r, { ...x, state, kind: 'Text' });
  for (const x of r.u) seen.set('U' + state + x.sel + x.r, { ...x, state, kind: 'Bedienelement' });
};

// 3a) Seiteninhalt: jede Seite bei allen sechs Breiten, Leiste oben und gescrollt.
const overflow = [];
for (const w of SIX) {
  await page.setViewportSize({ width: w, height: 1000 });
  for (const f of pages) {
    await page.goto('file://' + dir + '/' + f);
    await page.addStyleTag({ content: NOANIM });
    await page.waitForTimeout(30);
    const ov = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    if (ov > 0) overflow.push(`${f} @${w}px: ${ov}px horizontaler Überlauf`);
    await collect(`oben @${w}px`);
    await page.evaluate(() => { const n = document.querySelector('.fs-nav'); if (n) n.classList.add('is-scrolled'); });
    await page.waitForTimeout(20);
    await collect(`gescrollt @${w}px`);
  }
}
if (overflow.length) for (const x of overflow.slice(0, 10)) fail(x);
else ok(`kein horizontaler Überlauf: ${pages.length} Seiten bei ${SIX.join('/')}px`);
await page.setViewportSize({ width: 1512, height: 1000 });

/* 3b) Aufgeklappte Menüs - auf JEDER Seite.
 * Diese Zustände hatten bis Oktober 2026 kein Prüfschritt erfasst, und genau
 * darin steckten zwei Fehler: erst weiße Schrift auf weißem Flyout, danach der
 * aktive Eintrag (weiß auf --c-border, 1,65:1). Der zweite war nur auf der
 * Seite sichtbar, deren Eintrag aktiv ist - eine Prüfung über home.html
 * allein sieht ihn nie. Deshalb hier jede Seite mit ihrem eigenen Kontext:
 * Flyouts offen, Leiste oben und gescrollt, dazu der Mauszeiger auf dem
 * aktiven Eintrag (Hover darf die Aktiv-Farben nicht zerstören). */
const urls = JSON.parse(fs.readFileSync(dir + '/urls.json', 'utf8'));
const ariaBad = [];
// aria-current gehört an jeden Menülink auf die geöffnete Seite - und nur dorthin.
const checkAria = async (f, where) => {
  const key = f.replace(/\.html$/, '');
  const want = urls[key];
  if (!want) return;
  const r = await page.evaluate(([want, where]) => {
    const out = [];
    for (const a of document.querySelectorAll(where + ' a[href]')) {
      if (a.matches('.fs-btn, .fs-nav__logo')) continue;
      const cur = a.getAttribute('aria-current') === 'page';
      const hit = a.getAttribute('href') === want;
      if (cur !== hit) out.push((cur ? 'aria-current auf fremdem Link ' : 'aria-current fehlt an ') + a.getAttribute('href') + ' "' + a.textContent.trim() + '"');
    }
    return out;
  }, [want, where]);
  for (const x of r) ariaBad.push(`${f} [${where}] ${x}`);
};
let activeSeen = 0, drawerActiveSeen = 0;
const drawerTab = [];
/* Nichts Lesbares unter der fixierten Leiste: Brotkrumen und Seitentitel
 * müssen am Seitenanfang unterhalb der Leiste beginnen. Auf Seiten ohne
 * Seitenkopf lagen die Brotkrumen bis Oktober 2026 verdeckt darunter. */
const covered = [];
const checkCovered = async (f, w) => {
  const r = await page.evaluate(() => {
    const nav = document.querySelector('.fs-nav__inner'); if (!nav) return [];
    const bottom = nav.getBoundingClientRect().bottom;
    return [...document.querySelectorAll('main .fs-crumbs ol, main h1')]
      .filter(e => e.offsetParent && e.getBoundingClientRect().top < bottom - 1)
      .map(e => e.tagName.toLowerCase() + '.' + (e.parentElement.className || '') + ' top=' + Math.round(e.getBoundingClientRect().top) + ' < Leiste ' + Math.round(bottom));
  });
  for (const x of r) covered.push(`${f} @${w}px: ${x}`);
};
for (const f of pages) {
  await page.goto('file://' + dir + '/' + f);
  await page.addStyleTag({ content: NOANIM });
  await checkCovered(f, 1512);
  await checkAria(f, '.fs-nav');
  await checkAria(f, '.fs-drawer');
  for (const bar of ['oben', 'gescrollt']) {
    await page.evaluate((scrolled) => {
      const n = document.querySelector('.fs-nav');
      if (n) n.classList.toggle('is-scrolled', scrolled);
      document.querySelectorAll('.fs-nav__drop').forEach(d => d.classList.add('is-open'));
    }, bar === 'gescrollt');
    await page.waitForTimeout(40);
    await collect('Flyout offen, Leiste ' + bar + ', ' + f);
    const act = page.locator('.fs-nav__menu a[aria-current="page"]');
    if (await act.count()) {
      activeSeen++;
      // Nur das Flyout mit dem aktiven Eintrag offen lassen - alle zugleich
      // offen überdecken sich, der Mauszeiger träfe ein fremdes Menü.
      await page.evaluate(() => document.querySelectorAll('.fs-nav__drop').forEach(d =>
        d.classList.toggle('is-open', !!d.querySelector('.fs-nav__menu a[aria-current="page"]'))));
      await page.waitForTimeout(30);
      await act.first().hover({ timeout: 5000 });
      await page.waitForTimeout(30);
      await collect('Flyout offen, Maus auf aktivem Eintrag, Leiste ' + bar + ', ' + f);
      await page.mouse.move(1, 999);
    }
  }
}
if (ariaBad.length) for (const x of ariaBad) fail(x);
else ok(`aria-current: auf ${pages.length} Seiten genau an den Links zur geöffneten Seite (Flyout und Schubfach)`);
if (!activeSeen) fail('kein einziger aktiver Flyout-Eintrag gefunden – Seitenkontext im Smoke-Test kaputt?');

/* 3c) Schmale Breite, ebenfalls je Seite. Burger und Schubfach sind ab
 * 1240px display:none und werden von den Durchläufen oben nie erfasst.
 * Beide Zustände der Leiste zählen: der ursprüngliche P0 war ein Burger, der
 * erst auf der hinterlegten Leiste unsichtbar wurde. Im Schubfach steht der
 * aktive Eintrag in der serverseitig geöffneten Gruppe. */
await page.setViewportSize({ width: 390, height: 800 });
for (const f of pages) {
  await page.goto('file://' + dir + '/' + f);
  await page.addStyleTag({ content: NOANIM });
  await page.waitForTimeout(30);
  await checkCovered(f, 390);
  if (f === 'home.html') {
    await collect('schmal, Leiste oben');
    await page.evaluate(() => document.querySelector('.fs-nav')?.classList.add('is-scrolled'));
    await page.waitForTimeout(30);
    await collect('schmal, Leiste gescrollt');
    await page.evaluate(() => document.querySelector('.fs-nav')?.classList.remove('is-scrolled'));
  }
  // Geschlossenes Schubfach: nichts darin darf per Tab erreichbar sein.
  const tabbar = await page.evaluate(() => [...document.querySelectorAll('.fs-drawer a, .fs-drawer button')]
    .filter(e => getComputedStyle(e).visibility !== 'hidden' && e.getClientRects().length).length);
  if (tabbar) drawerTab.push(`${f}: ${tabbar} Links im geschlossenen Schubfach per Tab erreichbar`);
  // Über den echten Burger öffnen - so wie ein Mensch es tut.
  await page.click('[data-fs-burger]');
  await page.waitForTimeout(60);
  await collect('mobiles Menü offen, ' + f);
  const act = page.locator('.fs-drawer a[aria-current="page"]');
  if (await act.count()) {
    drawerActiveSeen++;
    if (!(await act.first().isVisible())) fail(`${f}: aktiver Eintrag im Schubfach ist nicht sichtbar (Gruppe zu?)`);
    else {
      await act.first().hover({ timeout: 5000 });
      await page.waitForTimeout(30);
      await collect('mobiles Menü, Finger auf aktivem Eintrag, ' + f);
    }
  }
  if (f === 'home.html') {
    await page.evaluate(() => document.querySelectorAll('[data-fs-sub]').forEach(x => x.classList.add('is-open')));
    await page.waitForTimeout(40);
    await collect('mobiles Menü, alle Gruppen offen');
  }
}
if (covered.length) for (const x of covered) fail('verdeckt von der Leiste: ' + x);
else ok(`Brotkrumen und Seitentitel auf ${pages.length} Seiten unterhalb der fixierten Leiste (390 und 1512px)`);
if (drawerTab.length) for (const x of drawerTab.slice(0, 5)) fail(x);
else ok(`geschlossenes Schubfach auf ${pages.length} Seiten nicht per Tab erreichbar`);
if (!drawerActiveSeen) fail('kein einziger aktiver Eintrag im Schubfach gefunden');
else ok(`Schubfach: ${drawerActiveSeen} Seiten mit sichtbarem aktivem Eintrag, Flyout: ${activeSeen} Zustände mit aktivem Eintrag gemessen`);
await page.setViewportSize({ width: 1440, height: 1000 });

if (!seen.size) ok(`keine Kontrastverstöße auf ${pages.length} Seiten bei ${SIX.length} Breiten – Leiste oben und gescrollt, Flyout und Schubfach je Seite geöffnet`);
for (const [, v] of [...seen].sort((a, b) => a[1].r - b[1].r)) {
  if (v.kind === 'Text') fail(`${v.r}:1 (nötig ${v.need}:1) [${v.state}] ${v.sel} "${v.txt}" – ${v.color} auf ${v.bg}`);
  else                   fail(`${v.r}:1 (nötig 3:1) [${v.state}] ${v.sel} – Füllung ${v.fill}, Rand ${v.border} auf ${v.bg}`);
}

/* ---------- 3d) Ausgewählter Zustand muss sichtbar sein ------------------
 * Eine Auswahl, die genauso aussieht wie die nicht gewählten Geschwister,
 * ist keine Auswahl. Beim Härten der Kontraste war genau das zweimal
 * passiert: die aktive Pille bekam dieselbe Füllung wie die inaktiven und
 * unterschied sich nur noch durch einen kaum sichtbaren Schatten. Reine
 * Kontrastprüfung findet das nicht - beide Zustände sind für sich lesbar. */
console.log('\nAusgewählter Zustand (Pills)');
let pillChecked = 0;
for (const f of ['preise.html', 'duisburg.html', 'klassen.html'].filter(x => pages.includes(x))) {
  await page.goto('file://' + dir + '/' + f);
  await page.addStyleTag({ content: NOANIM });
  await page.waitForTimeout(40);
  const bad = await page.evaluate(() => {
    const out = [];
    for (const group of document.querySelectorAll('[data-fs-group]')) {
      const on = group.querySelector('.fs-pill.is-active');
      const off = group.querySelector('.fs-pill:not(.is-active)');
      if (!on || !off) continue;
      const a = getComputedStyle(on), b = getComputedStyle(off);
      if (a.backgroundColor === b.backgroundColor && a.color === b.color) {
        out.push({ group: group.getAttribute('data-fs-group'), bg: a.backgroundColor, color: a.color });
      }
    }
    return out;
  });
  pillChecked++;
  for (const x of bad) {
    fail(`Pill-Gruppe "${x.group}" in ${f}: gewählt und ungewählt sind identisch (${x.bg}, Schrift ${x.color})`);
  }
}
if (pillChecked) ok(`gewählter Zustand auf ${pillChecked} Seiten von den ungewählten unterscheidbar`);

// ---------- 4) Fokusring auf jeder Fläche --------------------------------
console.log('\nFokusring (WCAG SC 1.4.11)');
await page.goto(home);
const fr = await page.evaluate(new Function(HELPERS + `
  const probe=document.createElement('button'); probe.textContent='x';
  const out=[];
  for(const surf of ['fs-surface-brand','fs-surface-light','fs-ctx-light','fs-ctx-deep']){
    const box=document.createElement('div'); box.className=surf;
    // Kontextsätze ohne eigene Fläche (ctx-light/-deep) tragen sie hier selbst - wie die Komponenten, die sie einbinden.
    box.style.background='var(--c-bg)';
    document.body.appendChild(box); box.appendChild(probe);
    probe.focus();
    const cs=getComputedStyle(probe);
    const bg=effBg(box);
    const oc=parse(cs.outlineColor);
    // Der Doppelring besteht aus outline UND box-shadow; erkennbar ist er,
    // sobald EINER der beiden Ringe 3:1 gegen den Untergrund erreicht.
    const shadow=(cs.boxShadow.match(/rgba?\\([^)]+\\)/)||[null])[0];
    const sc=shadow?parse(shadow):null;
    const r1=oc?ratio(over(oc,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    const r2=sc?ratio(over(sc,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    out.push({surf, outline:cs.outlineColor, shadow, r:+Math.max(r1,r2).toFixed(2), bg:'rgb('+bg.map(Math.round).join(',')+')'});
    box.remove();
  }
  return out;
`));
for (const f of fr) {
  if (f.r < 3) fail(`Fokusring auf .${f.surf} nur ${f.r}:1 (nötig 3:1) – ${f.outline} / ${f.shadow} auf ${f.bg}`);
  else ok(`Fokusring auf .${f.surf}: ${f.r}:1`);
}

// ---------- 4b) Social-Feed (Prüfseite _social) --------------------------
// Raster aus Links: Cover 3:4 formatfüllend, Titel höchstens zwei Zeilen,
// Marken-Kachel statt leerem Rahmen, jede Kachel per Tab erreichbar.
if (pages.includes('_social.html')) {
  console.log('\nSocial-Feed');
  const sbad = [];
  for (const w of [320, 390, 768]) {
    await page.setViewportSize({ width: w, height: 900 });
    await page.goto('file://' + dir + '/_social.html');
    await page.addStyleTag({ content: NOANIM });
    const ov = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    if (ov > 0) sbad.push(`${w}px: ${ov}px horizontaler Überlauf`);
  }
  await page.setViewportSize({ width: 1512, height: 1000 });
  await page.goto('file://' + dir + '/_social.html');
  await page.addStyleTag({ content: NOANIM });
  const st = await page.evaluate(new Function(HELPERS + `
    const k=[...document.querySelectorAll('.fs-social__kachel')];
    const cov=k.map(a=>a.querySelector('.fs-social__cover').getBoundingClientRect());
    const cols=new Set(cov.map(r=>Math.round(r.left))).size;
    const sameW=cov.every(r=>Math.abs(r.width-cov[0].width)<=1);
    const ratio34=cov.every(r=>Math.abs(r.height/r.width-4/3)<0.02);
    const fill=k.filter(a=>a.querySelector('img')).every(a=>{const i=a.querySelector('img').getBoundingClientRect(),c=a.querySelector('.fs-social__cover').getBoundingClientRect();return i.width>=c.width-1&&i.height>=c.height-1;});
    const zeilen=[...document.querySelectorAll('.fs-social__text')].map(t=>{const lh=parseFloat(getComputedStyle(t).lineHeight);return Math.round(t.getBoundingClientRect().height/lh);});
    const marke=k.filter(a=>a.classList.contains('fs-social__kachel--marke'));
    const markeOk=marke.every(a=>{const c=a.querySelector('.fs-social__cover');return c.getBoundingClientRect().height>50 && a.querySelectorAll('svg').length>=2 && parseFloat(getComputedStyle(c).backgroundColor.split(',')[3]??'1')!==0;});
    const notLink=k.filter(a=>a.tagName!=='A'||a.target!=='_blank'||!/noopener/.test(a.rel)).length;
    const kopf=document.querySelector('.fs-social__kopf'); const h=kopf&&kopf.querySelector('h2'), btn=kopf&&kopf.querySelector('.fs-btn');
    const kopfOk=!!(h&&btn&&h.getBoundingClientRect().left<btn.getBoundingClientRect().left&&h.querySelector('svg'));
    // Icons auf dem Abdunkler bzw. der Marken-Fläche: mindestens 3:1
    const ic=k.map(a=>{const b=a.querySelector('.fs-social__play');const fg=parse(getComputedStyle(b).color),bg=effBg(b);return ratio(over(fg,{r:bg[0],g:bg[1],b:bg[2]}),bg);});
    return {n:k.length, cols, sameW, ratio34, fill, maxZeilen:Math.max(0,...zeilen), marke:marke.length, markeOk, notLink, kopfOk, minIcon:Math.min(...ic)};`));
  if (st.cols !== 3 || !st.sameW) sbad.push(`Raster bei 1512px: ${st.cols} Spalten, gleich breit ${st.sameW}`);
  if (!st.ratio34 || !st.fill) sbad.push(`Cover: 3:4 ${st.ratio34}, formatfüllend ${st.fill}`);
  if (st.maxZeilen > 2) sbad.push(`Titel über ${st.maxZeilen} Zeilen (höchstens 2)`);
  if (!st.marke || !st.markeOk) sbad.push(`Marken-Kachel: ${st.marke} gefunden, mit Fläche und Icons ${st.markeOk}`);
  if (st.notLink) sbad.push(`${st.notLink} Kachel(n) ohne Link in neuem Tab`);
  if (!st.kopfOk) sbad.push('Kopf: Überschrift mit Icon links, Profil-Knopf rechts fehlt');
  if (st.minIcon < 3) sbad.push(`Abspiel-Zeichen nur ${st.minIcon.toFixed(2)}:1 (nötig 3:1)`);
  const erreicht = new Set();
  await page.focus('body');
  for (let t = 0; t < 80; t++) {
    await page.keyboard.press('Tab');
    const idx = await page.evaluate(() => [...document.querySelectorAll('.fs-social__kachel')].indexOf(document.activeElement));
    if (idx >= 0) erreicht.add(idx);
  }
  if (erreicht.size !== st.n) sbad.push(`per Tab erreicht: ${erreicht.size} von ${st.n} Kacheln`);
  for (const x of sbad) fail('Social-Feed: ' + x);
  if (!sbad.length) ok(`Social-Feed: ${st.n} Kacheln im 3er-Raster, Cover 3:4 formatfüllend, Titel ≤ 2 Zeilen, ${st.marke} Marken-Kacheln, Kopf links/rechts, Abspiel-Zeichen ${st.minIcon.toFixed(2)}:1, alle per Tab und in neuem Tab, kein Überlauf bei 320/390/768px`);
}

// ---------- 5) Keine Drittanbieter-Abrufe ohne Einwilligung -------------
console.log('\nDrittanbieter (harte Regel 6)');
if (fremd.length) for (const u of [...new Set(fremd)].slice(0, 10)) fail('Abruf ohne Einwilligung: ' + u);
else ok(`keine Anfrage an Dritte auf ${pages.length} Seiten in allen geprüften Zuständen`);
// Gegenstück: Die Karte lädt auf Klick - und erst dann.
const karteSeite = pages.find(f => f === 'duisburg.html');
if (karteSeite) {
  fremd.length = 0;
  await page.goto('file://' + dir + '/' + karteSeite);
  await page.addStyleTag({ content: NOANIM });
  const vor = fremd.length;
  await page.click('[data-fs-karte-load]');
  await page.waitForTimeout(200);
  const nach = fremd.filter(u => u.startsWith('https://www.openstreetmap.org/export/embed.html')).length;
  if (vor !== 0) fail(`Karte: ${vor} Abruf(e) schon vor dem Klick`);
  else if (nach !== 1) fail(`Karte: nach dem Klick ${nach} statt 1 Abruf bei OpenStreetMap`);
  else ok('Karte: kein Abruf vor dem Klick, genau einer danach (OpenStreetMap)');
}

await browser.close();
console.log();
if (fails.length) { console.log(`BROWSERPRÜFUNG: ${fails.length} Fehler`); process.exit(1); }
console.log('BROWSERPRÜFUNG: bestanden');
