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

const dir = process.argv[2];
if (!dir) { console.error('Aufruf: browser.mjs <verzeichnis-mit-html>'); process.exit(2); }

const WIDTHS = [360, 390, 414, 600, 640, 768, 900, 1024, 1100, 1179, 1239, 1240, 1280, 1366, 1440, 1920];
const ZOOM   = [960, 720, 640, 512];           // 1920/1440/1280/1024 bei 200%
let fails = [];
const fail = m => { fails.push(m); console.log('  FEHLER: ' + m); };
const ok   = m => console.log('  ok: ' + m);

const HELPERS = `
function lum(c){const s=c.map(v=>{v/=255;return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4)});return .2126*s[0]+.7152*s[1]+.0722*s[2]}
function parse(c){const m=c.match(/rgba?\\(([^)]+)\\)/);if(!m)return null;const p=m[1].split(',').map(x=>parseFloat(x));return {r:p[0],g:p[1],b:p[2],a:p.length>3?p[3]:1}}
function over(f,b){return [f.r*f.a+b.r*(1-f.a), f.g*f.a+b.g*(1-f.a), f.b*f.a+b.b*(1-f.a)]}
function effBg(el){let e=el;const st=[];while(e&&e!==document.documentElement){const c=parse(getComputedStyle(e).backgroundColor);if(c&&c.a>0)st.push(c);if(c&&c.a===1)break;e=e.parentElement}
  let base=[255,255,255];for(let i=st.length-1;i>=0;i--)base=over(st[i],{r:base[0],g:base[1],b:base[2]});return base}
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
    const bc=parse(cs.borderTopColor), fc=parse(cs.backgroundColor);
    // SC 1.4.11: die KOMPONENTE muss erkennbar sein - dafür genügt eine
    // kontrastierende Füllung ODER ein kontrastierender Rand.
    const rb=bc&&bc.a>0?ratio(over(bc,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    const rf=fc&&fc.a>0?ratio(over(fc,{r:bg[0],g:bg[1],b:bg[2]}),bg):0;
    const r=Math.max(rb,rf);
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
await page.setViewportSize({ width: 1440, height: 1000 });
const seen = new Map();
for (const f of pages) {
  await page.goto('file://' + dir + '/' + f);
  await page.addStyleTag({ content: '*{transition:none!important;animation:none!important}.fs-fade-up{opacity:1!important}' });
  await page.waitForTimeout(40);
  for (const state of ['top', 'scrolled']) {
    if (state === 'scrolled') {
      await page.evaluate(() => { const n = document.querySelector('.fs-nav'); if (n) n.classList.add('is-scrolled'); });
      await page.waitForTimeout(30);
    }
    const r = await page.evaluate(new Function(HELPERS + 'return {t:scanText(),u:scanUi()}'));
    for (const x of r.t) seen.set('T' + x.sel + x.r, { ...x, state, kind: 'Text' });
    for (const x of r.u) seen.set('U' + x.sel + x.r, { ...x, state, kind: 'Bedienelement' });
  }
}
if (!seen.size) ok(`keine Kontrastverstöße auf ${pages.length} Seiten (Normalzustand und gescrollte Navigation)`);
for (const [, v] of [...seen].sort((a, b) => a[1].r - b[1].r)) {
  if (v.kind === 'Text') fail(`${v.r}:1 (nötig ${v.need}:1) [${v.state}] ${v.sel} "${v.txt}" – ${v.color} auf ${v.bg}`);
  else                   fail(`${v.r}:1 (nötig 3:1) [${v.state}] ${v.sel} – Füllung ${v.fill}, Rand ${v.border} auf ${v.bg}`);
}

// ---------- 4) Fokusring auf jeder Fläche --------------------------------
console.log('\nFokusring (WCAG SC 1.4.11)');
await page.goto(home);
const fr = await page.evaluate(new Function(HELPERS + `
  const probe=document.createElement('button'); probe.textContent='x';
  const out=[];
  for(const surf of ['fs-surface-brand','fs-surface-light','fs-ctx-light','fs-ctx-deep']){
    const box=document.createElement('div'); box.className=surf;
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

await browser.close();
console.log();
if (fails.length) { console.log(`BROWSERPRÜFUNG: ${fails.length} Fehler`); process.exit(1); }
console.log('BROWSERPRÜFUNG: bestanden');
