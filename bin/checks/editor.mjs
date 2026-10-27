/**
 * Editor-Oberfläche (assets/js/editor.js) ohne WordPress: das echte Skript
 * mit einem nachgebauten `wp`-Objekt und der echten Blockliste aus PHP
 * (fahrschule_editor_assets()).
 *
 *   - Social-Raster: Videospalte öffnet die Mediathek nur für Videos, die
 *     Standbildspalte nur für Bilder; onSelect schreibt die Attachment-ID
 *     (nicht die URL); „Entfernen“ leert die Zelle und gibt den Fokus an
 *     „Auswählen“ zurück; Knöpfe sind beschriftet („Video für Zeile 2
 *     auswählen“); alte Werte (Slug, URL) bleiben stehen; Video über 5 MB
 *     zeigt die Größe.
 *   - Alle übrigen Wiederholfelder: nur Textfelder wie bisher.
 *
 * Nicht nachgebaut: die Mediathek selbst (MediaUpload öffnet sie in einem
 * echten WordPress) - geprüft wird, was editor.js ihr übergibt.
 *
 * Aufruf: node editor.mjs
 */
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import vm from 'node:vm';

const here = new URL('.', import.meta.url).pathname;
const root = new URL('../../', import.meta.url).pathname;
const fails = [];
const fail = m => { fails.push(m); console.log('  FEHLER: ' + m); };
const ok = m => console.log('  ok: ' + m);

// Echte Blockliste, wie wp_localize_script sie dem Editor gibt.
const php = `$GLOBALS["__theme"]=$argv[1]; $GLOBALS["__blocks"]=[]; require $argv[2]."/wp-stub.php"; require $argv[1]."/functions.php"; fahrschule_editor_assets(); echo json_encode($GLOBALS["__localized"]["FahrschuleBlocks"]);`;
const bloecke = JSON.parse(execFileSync('php', ['-r', php, root + 'fahrschule-edi', here], { encoding: 'utf8' }));

// --- nachgebautes wp ----------------------------------------------------------
const reg = {};
const comp = name => { const f = p => ({ type: name, props: p }); f.displayName = name; return f; };
let hooks = { store: {}, inst: null, idx: 0, effects: [] };
const medien = {};
const wp = {
  element: {
    createElement: (type, props, ...children) => ({ type, props: { ...(props || {}), children: children.flat() } }),
    useRef: init => { const s = (hooks.store[hooks.inst] ||= []); const i = hooks.idx++; return (s[i] ||= { current: init }); },
    useEffect: fn => { hooks.effects.push(fn); },
  },
  blocks: { registerBlockType: (n, s) => { reg[n] = s; }, getBlockType: () => null, unregisterBlockType: () => {} },
  blockEditor: { InspectorControls: comp('InspectorControls'), useBlockProps: () => ({}), MediaUpload: comp('MediaUpload'), MediaUploadCheck: comp('MediaUploadCheck') },
  components: { PanelBody: comp('PanelBody'), TextControl: comp('TextControl'), TextareaControl: comp('TextareaControl'), SelectControl: comp('SelectControl'), Button: comp('Button') },
  serverSideRender: comp('ServerSideRender'),
  i18n: { __: s => s },
  data: { useSelect: fn => fn(() => ({ getMedia: id => medien[id] })), select: () => ({ getCurrentPostId: () => 1 }) },
};
vm.runInNewContext(readFileSync(root + 'fahrschule-edi/assets/js/editor.js', 'utf8'), { window: { wp, FahrschuleBlocks: bloecke } });

// Baum aufklappen: Funktions-Komponenten (MediaZelle) ausführen, MediaUpload
// über seine render-Funktion. Hook-Speicher je Komponente über ihren key.
function expand(n) {
  if (Array.isArray(n)) return n.map(expand);
  if (!n || typeof n !== 'object') return n;
  if (typeof n.type === 'function' && !n.type.displayName) {
    const vorher = [hooks.inst, hooks.idx];
    hooks.inst = n.props.key || 'x'; hooks.idx = 0;
    const out = expand(n.type(n.props));
    [hooks.inst, hooks.idx] = vorher;
    return { type: 'Zelle', props: { ...n.props, children: [out] } };
  }
  const typ = typeof n.type === 'function' ? n.type.displayName : n.type;
  const props = { ...n.props, children: expand(n.props.children || []) };
  if (typ === 'MediaUpload') props.children = [expand(n.props.render({ open: () => {} }))];
  return { type: typ, props };
}
const alle = (n, pred, out = []) => {
  if (Array.isArray(n)) { n.forEach(x => alle(x, pred, out)); return out; }
  if (!n || typeof n !== 'object') return out;
  if (pred(n)) out.push(n);
  alle(n.props.children, pred, out);
  return out;
};
const text = n => Array.isArray(n) ? n.map(text).join('') : (n && typeof n === 'object' ? text(n.props.children) : (n ?? '') + '');
function zeichne(name, attrs) {
  const s = reg[name];
  const gesetzt = [];
  hooks.effects = [];
  const baum = expand(s.edit({ attributes: attrs, setAttributes: u => gesetzt.push(u), clientId: 'c1' }));
  hooks.effects.forEach(f => f());
  return { baum, gesetzt };
}

// --- Social-Raster -------------------------------------------------------------
const raster = bloecke.find(b => b.name === 'fahrschule/social-raster');
if (!raster) fail('Block fahrschule/social-raster fehlt in FahrschuleBlocks');
else {
  medien[901] = { id: 901, source_url: 'https://x/clip.mp4', media_details: { filesize: 12 * 1048576 } };
  medien[801] = { id: 801, source_url: 'https://x/bild.jpg', media_details: { sizes: { thumbnail: { source_url: 'https://x/bild-150.jpg' } } } };
  const items = [['901', '801', 'Eins'], ['', '', 'Zwei'], ['edi-video-alt', 'https://x/alt.jpg', 'Drei']];
  const { baum, gesetzt } = zeichne('fahrschule/social-raster', { items });
  const e = [];
  const ups = alle(baum, n => n.type === 'MediaUpload');
  if (ups.length !== 6) e.push(`${ups.length} Mediathek-Auswahlen statt 6 (3 Zeilen × Video + Standbild)`);
  ups.forEach((u, i) => {
    const soll = i % 2 === 0 ? 'video' : 'image';
    if (JSON.stringify(u.props.allowedTypes) !== JSON.stringify([soll])) e.push(`Zelle ${i}: allowedTypes ${JSON.stringify(u.props.allowedTypes)} statt ["${soll}"]`);
  });
  const knoepfe = alle(baum, n => n.type === 'Button').map(b => ({ txt: text(b.props.children), aria: b.props['aria-label'] || '' }));
  for (const need of ['Video für Zeile 2 auswählen', 'Bild für Zeile 2 auswählen', 'Video für Zeile 1 ersetzen', 'Video aus Zeile 1 entfernen']) {
    if (!knoepfe.some(k => k.aria === need)) e.push(`Knopf „${need}“ fehlt`);
  }
  if (!knoepfe.some(k => k.txt === 'Auswählen') || !knoepfe.some(k => k.txt === 'Ersetzen')) e.push('sichtbare Beschriftung „Auswählen“/„Ersetzen“ fehlt');
  // Auswahl schreibt die ID, nicht die URL.
  ups[2].props.onSelect({ id: 555, url: 'https://x/neu.mp4' });
  const nach = gesetzt.at(-1)?.items;
  if (!nach || nach[1][0] !== '555') e.push(`Auswahl schreibt „${nach && nach[1][0]}“ statt der ID 555`);
  // Entfernen leert die Zelle.
  const weg = alle(baum, n => n.type === 'Button' && n.props['aria-label'] === 'Video aus Zeile 1 entfernen')[0];
  weg && weg.props.onClick();
  if (gesetzt.at(-1)?.items?.[0]?.[0] !== '') e.push('„Entfernen“ leert die Zelle nicht');
  const alt = text(baum);
  if (!alt.includes('Alter Wert (bleibt gültig): edi-video-alt') || !alt.includes('https://x/alt.jpg')) e.push('alter Slug bzw. alte URL nicht sichtbar stehen gelassen');
  if (!alt.includes('12,0 MB')) e.push('Video über 5 MB ohne Größe');
  if (alle(baum, n => n.type === 'img').every(i => i.props.src !== 'https://x/bild-150.jpg')) e.push('Standbild ohne Vorschau');
  // Fokus: Zelle erst mit, dann ohne Wert - der Auswählen-Knopf bekommt den Fokus.
  let fokus = false;
  hooks.store = {};
  const z1 = zeichne('fahrschule/social-raster', { items: [['901', '', '']] });
  const btn = alle(z1.baum, n => n.type === 'Button' && /Zeile 1 ersetzen/.test(n.props['aria-label'] || ''))[0];
  if (btn) btn.props.ref.current = { focus: () => { fokus = true; } };
  zeichne('fahrschule/social-raster', { items: [['', '', '']] });
  if (!fokus) e.push('nach „Entfernen“ geht der Fokus nicht auf „Auswählen“');
  e.length ? e.forEach(fail) : ok('Social-Raster im Editor: Video- bzw. Bildspalte öffnet die Mediathek nur für Videos bzw. Bilder, Auswahl speichert die ID, „Entfernen“ leert und gibt den Fokus an „Auswählen“, Knöpfe sprechend beschriftet, alte Werte bleiben, Größenhinweis ab 5 MB');
}

// --- übrige Wiederholfelder: unverändert Textfelder ---------------------------
const e2 = [];
let n2 = 0;
for (const b of bloecke) {
  for (const f of b.fields) {
    if (f.control !== 'repeater' || b.name === 'fahrschule/social-raster') continue;
    n2++;
    const zeile = (f.columns || []).map(() => 'x');
    const { baum } = zeichne(b.name, { [f.key]: [zeile] });
    if (alle(baum, n => n.type === 'MediaUpload').length) e2.push(`${b.name}.${f.key}: Mediathek-Auswahl in einem Textwiederholer`);
    const tc = alle(baum, n => n.type === 'TextControl' && String(n.props.key || '').startsWith(f.key + '-0-'));
    if (tc.length !== (f.columns || []).length) e2.push(`${b.name}.${f.key}: ${tc.length} Textfelder statt ${(f.columns || []).length}`);
    if (tc.some(t => typeof t.props.label !== 'string' || !t.props.label)) e2.push(`${b.name}.${f.key}: Spalte ohne Beschriftung`);
  }
}
e2.length ? e2.forEach(fail) : ok(`übrige ${n2} Wiederholfelder: unverändert eine Textzeile je Spalte, mit Beschriftung`);

console.log();
if (fails.length) { console.log(`EDITOR: ${fails.length} Fehler`); process.exit(1); }
console.log('EDITOR: bestanden');
