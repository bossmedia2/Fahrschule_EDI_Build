<?php
// Ausgabeverzeichnis; ohne FS_OUT ins Temp-Verzeichnis, nie in den Wurzelordner.
$FS_OUT = getenv('FS_OUT') ?: sys_get_temp_dir();
error_reporting(E_ALL); ini_set('display_errors','stderr');
// Theme-Pfad am eigenen Ort verankert: läuft aus jedem Verzeichnis.
$GLOBALS['__theme'] = $argv[1] ?? dirname(__DIR__, 2).'/fahrschule-edi';
$GLOBALS['__blocks']=[]; $GLOBALS['__patterns']=[];
$errs=[];
set_error_handler(function($no,$str,$f,$l) use (&$errs){ $errs[]=sprintf("[%s] %s  (%s:%d)", $no===E_USER_WARNING?'WARN':'PHP', $str, basename($f), $l); return true; });
require __DIR__.'/wp-stub.php';
require __DIR__.'/fse.php';
define('FAHRSCHULE_VERSION','1.1.0');
define('FAHRSCHULE_DIR', $GLOBALS['__theme']);
define('FAHRSCHULE_URI', '/wp-content/themes/fahrschule-edi');
foreach(['helpers','pdf-texts','media','pages','render-callbacks','blocks-config','seo','forms','security'] as $f)
  require $GLOBALS['__theme'].'/inc/'.$f.'.php';

// Ohne Blockkonfiguration rendert keine Seite - dann sofort sagen, warum,
// statt still mit null Seiten weiterzulaufen.
$erwartet = count(fahrschule_page_map());
try { $cfg = fahrschule_blocks_config(); }
catch(\Throwable $e){
  printf("FATAL: Blockkonfiguration nicht ladbar: %s (%s:%d)\n", $e->getMessage(), basename($e->getFile()), $e->getLine());
  printf("Seiten: 0 von %d gerendert (erwartet %d) - erste Meldung: %s\n", $erwartet, $erwartet, $e->getMessage());
  exit(1);
}
// run the theme's own registration loop
foreach($cfg as $name=>$c){
  $attrs=[]; if(!empty($c['fields'])) foreach($c['fields'] as $k=>$fl) $attrs[$k]=['type'=>'string','default'=>$fl['default']];
  register_block_type($name, ['attributes'=>$attrs,'render_callback'=>$c['render']]);
}
echo "Registered blocks: ".count($GLOBALS['__blocks'])."\n";
echo str_repeat("=",74)."\n";
printf("%-40s %9s %7s %s\n","BLOCK","BYTES","ERRORS","STATUS");
echo str_repeat("-",74)."\n";
$total=0; $failed=0; $empty=0; $outs=[];
foreach(array_keys($GLOBALS['__blocks']) as $name){
  $errs=[]; $html=''; $status='OK';
  try { $html = render_block(['blockName'=>$name,'attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]); }
  catch(\Throwable $e){ $status='FATAL: '.$e->getMessage(); $failed++; }
  $len=strlen($html); $total+=$len; $outs[$name]=$html;
  if($status==='OK'){
    // Manche Bloecke sind reine Inhalts-Huellen (Bereichsliste, Kurs-Slots):
    // ohne Konfiguration haben sie nichts auszugeben, und genau das ist richtig.
    $mayBeEmpty = !empty($cfg[$name]['may_be_empty']);
    if($len===0 && !$mayBeEmpty){ $status='!! EMPTY OUTPUT'; $empty++; }
    elseif($len===0){ $status='leer (ohne Konfiguration gewollt)'; }
    elseif($errs){ $status='!! '.count($errs).' notice/warning'; $failed++; }
  }
  printf("%-40s %9d %7d %s\n", $name, $len, count($errs), $status);
  foreach(array_unique($errs) as $e) echo "      -> $e\n";
}
echo str_repeat("-",74)."\n";
printf("TOTAL %d blocks, %d bytes HTML, %d with errors, %d empty\n",count($GLOBALS['__blocks']),$total,$failed,$empty);
file_put_contents($FS_OUT.'/rendered.json', json_encode($outs));

/* ---- Seiten: jede Registry-Seite über ihre Block-Vorlage rendern (bin/checks/fse.php) ---- */
echo "\n".str_repeat("=",74)."\nPAGE-LEVEL SMOKE TEST (inc/pages.php registry)\n".str_repeat("=",74)."\n";
$map = fahrschule_page_map();
$pages=[]; $seiten_ok=0; $erste='';
foreach($map as $key=>$pg){
  $errs=[]; $html='';
  // Kontext der jeweiligen Seite herstellen (unten im try) - sonst rendert
  // die Navigation ohne aktiven Eintrag und der Seitenkopf ohne Titel.
  try{
    // Wie WordPress mit einem Block-Theme: Vorlage aus templates/, Parts aus
    // parts/, Seiteninhalt = das Markup, das die Seitenanlage schreibt.
    $content = fahrschule_page_content($key);
    fs_stub_set_page($pg['slug'], $pg['title'] ?? ($pg['menu'] ?? $key), $key==='home', $content);
    $tpl  = $pg['template'] ?? 'page';
    $html = fs_fse_render_template($GLOBALS['__theme'], $tpl, $content);
    $st = $errs ? '!! '.count($errs).' issue(s)' : 'OK';
  } catch(\Throwable $e){ $st='FATAL: '.$e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')'; }
  printf("%-24s %-38s %8d  %s\n", $key, '/'.$pg['slug'], strlen($html), $st);
  foreach(array_unique($errs) as $e) echo "      -> $e\n";
  if($st==='OK') $seiten_ok++; elseif($erste==='') $erste="$key: ".($errs?reset($errs):$st);
  $pages[$key]=$html;
}
// Vorlagen ohne Registry-Seite: 404, Suche, Archiv, Einzelbeitrag.
foreach(['_404'=>'404','_suche'=>'search','_archiv'=>'archive','_beitrag'=>'single','_index'=>'index','_stelle'=>'single-job_listing','_stellen'=>'archive-job_listing'] as $key=>$tpl){
  $errs=[]; $html='';
  fs_stub_set_page('beitrag','Beispielbeitrag',false,'<!-- wp:paragraph --><p>Beispieltext.</p><!-- /wp:paragraph -->');
  // Stellenanzeige: Beitragstyp job_listing, damit Brotkrumen und Stellenblock greifen.
  if($key==='_stelle'){ $GLOBALS['__queried']->post_type='job_listing'; $GLOBALS['__queried']->post_title='Fahrlehrer (m/w/d)'; $GLOBALS['__queried']->post_content='Beispielbeschreibung der Stelle.'; }
  try{ $html=fs_fse_render_template($GLOBALS['__theme'],$tpl,'<!-- wp:paragraph --><p>Beispieltext.</p><!-- /wp:paragraph -->'); $st=$errs?'!! '.count($errs).' issue(s)':'OK'; }
  catch(\Throwable $e){ $st='FATAL: '.$e->getMessage(); }
  printf("%-24s %-38s %8d  %s\n", $key, 'templates/'.$tpl.'.html', strlen($html), $st);
  foreach(array_unique($errs) as $e) echo "      -> $e\n";
  $pages[$key]=$html;
}
/* ---- 404: Meldung, dann Suche, dann Wege zurück - keine Stellen ---- */
$h=$pages['_404']; $m=substr($h,(int)strpos($h,'<main'));
$p1=strpos($m,'<h1'); $p2=strpos($m,'role="search"'); $p3=strpos($m,'class="fs-feature"');
$e404=[];
if($p1===false||$p2===false||$p3===false) $e404[]='Meldung, Suche oder Wege zurück fehlen';
elseif(!($p1<$p2 && $p2<$p3)) $e404[]='Reihenfolge nicht Meldung -> Suche -> Wege zurück';
if(preg_match('#fs-jobs|Offene Stellen|job_listing#',$m)) $e404[]='Stellenangebote auf der 404';
echo "404: ".($e404?'!! '.implode(' | ',$e404):'ok: Meldung, Suche, Wege zurück - keine Stellenangebote')."\n";
/* ---- TikTok-Video: Prüfseite ohne Einwilligung ----
 * Im HTML nur der Platzhalter - kein Iframe, kein Skript. Den Player nach
 * Zustimmung und die URL-Erkennung prüft bin/checks/social.php, das Laden
 * auf Klick die Browserprüfung. */
$tt = ['videoUrl'=>'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111?is_from_webapp=1'];
$h = render_block(['blockName'=>'fahrschule/instagram-slider','attrs'=>$tt,'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
$serr=[];
if(preg_match('#<iframe|<script|embed\.js#i',$h)) $serr[]='Iframe oder Skript ohne Einwilligung im HTML';
if(!str_contains($h,'data-fs-tt-load')) $serr[]='Platzhalter mit „Video laden“ fehlt';
echo "\nSocial-Feed (Prüfseite): ".($serr? "!! ".implode(' | ',$serr)."\n" : "ok: TikTok-Video ohne Einwilligung = Platzhalter, kein Iframe, kein Skript\n");
fs_stub_set_page('social','TikTok-Video (Prüfseite)',false,'');
$pages['_social']=fs_fse_render_template($GLOBALS['__theme'],'page',serialize_blocks([['blockName'=>'fahrschule/instagram-slider','attrs'=>$tt,'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]]));

/* ---- Social-Raster: Prüfseite mit fünf Kacheln (drei plus zwei) ----
 * Uploads der eigenen Domain; mkpages.php stellt sie aus bin/checks/fixtures
 * lokal bereit. Die fünfte Kachel ohne Standbild (preload=metadata). */
$up = 'https://fahrschule-edi.de/wp-content/uploads/';
$raster_rows = [];
for($i=1;$i<=4;$i++) $raster_rows[] = [$up.'raster-clip.webm', $up.'raster-standbild.jpg', "Fahrstunde $i"];
$raster_rows[] = [$up.'raster-clip.webm', '', 'Ohne Standbild'];
fs_stub_set_page('raster','Social-Raster (Prüfseite)',false,'');
$pages['_raster']=fs_fse_render_template($GLOBALS['__theme'],'page',serialize_blocks([['blockName'=>'fahrschule/social-raster','attrs'=>['items'=>$raster_rows,'heading'=>'Einblicke'],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]]));
echo "Social-Raster (Prüfseite): ".(substr_count($pages['_raster'],'class="fs-raster__kachel"')===5 ? "ok: 5 Kacheln\n" : "!! ".substr_count($pages['_raster'],'class="fs-raster__kachel"')." statt 5 Kacheln\n");
file_put_contents($FS_OUT.'/pages.json', json_encode($pages));
// Erwartete URL je Seite - die Browserprüfung vergleicht damit aria-current.
$urls=[]; foreach($map as $key=>$pg){ $urls[$key]=fs_url($key); }
file_put_contents($FS_OUT.'/urls.json', json_encode($urls));
/* ---- Keine toten internen Links: jedes href auf die eigene Domain (oder /…)
 * zeigt auf eine Registry-Seite, die Startseite, eine Stelle oder ein Asset. */
$home=rtrim(home_url('/'),'/'); $known=array_flip($urls); $tot=[];
foreach($pages as $key=>$h){
  preg_match_all('#href="([^"]*)"#',$h,$hm);
  foreach($hm[1] as $href){
    $href=html_entity_decode(preg_replace('/[#?].*$/','',$href));
    if($href===''||!(str_starts_with($href,$home)||str_starts_with($href,'/'))) continue;
    $abs=str_starts_with($href,'/')?$home.$href:$href;
    if(isset($known[$abs])||$abs===$home.'/'||preg_match('#/(job|wp-content|wp-admin|wp-json)/|\.(css|js|webp|jpg|png|svg|pdf)$#',$abs)) continue;
    $tot[$abs][]=$key;
  }
}
echo "Links: ".($tot?'!! tote interne Links: '.implode('; ',array_map(fn($u,$k)=>"$u (".implode(',',array_unique($k)).")",array_keys($tot),$tot)):'ok: alle internen Links zeigen auf vorhandene Seiten')."\n";
echo "\nSEO smoke: ";
$errs=[];
if(function_exists('fahrschule_business_data')){ $bd=fahrschule_business_data(); echo count($bd)." business records; "; }
echo $errs? implode(' | ',$errs) : "no errors\n";

/* ---- Galerie gegen eine Test-Mediathek ----
 * Die Namen stammen aus bin/edi-bilder.py (unkategorisiert) plus ein
 * kategorisiertes Bild und Fremdnamen, die NICHT erscheinen dürfen. */
echo "\nGalerie (Test-Mediathek): ";
$GLOBALS['__attachments']=['edi-galerie-001','edi-galerie-002','edi-galerie-446','edi-galerie-fuhrpark-003',
  'edi-team-aslan','edi-galeriex-009','xedi-galerie-010','edi-standort-duisburg'];
$g=render_block(['blockName'=>'fahrschule/galerie','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
$GLOBALS['__attachments']=[];
$gerr=[];
$n=substr_count($g,'class="fs-galitem"');
if($n!==4) $gerr[]="$n Bilder statt 4 (3 unkategorisiert + 1 Fuhrpark)";
if(strpos($g,'data-kat="fuhrpark"')===false) $gerr[]='Fuhrpark-Filter fehlt';
if(strpos($g,'>Fahrstunde<')!==false) $gerr[]='Filter ohne Bilder (Fahrstunde) angezeigt';
if(substr_count($g,'aria-label="Bild vergrößern')!==4) $gerr[]='Bildknöpfe ohne zugänglichen Namen';
$leer=render_block(['blockName'=>'fahrschule/galerie','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
if(trim($leer)!=='') $gerr[]='leere Mediathek rendert trotzdem einen Abschnitt';
echo $gerr ? "!! ".implode(' | ',$gerr)."\n" : "ok: 4 von 8 Anhängen per Präfix, Filter nur für belegte Kategorie, leer = ausgeblendet\n";

/* ---- Ergebnis: eine Zeile, die sagt, was erwartet war und was herauskam ---- */
echo "\nSeiten: $seiten_ok von $erwartet gerendert (erwartet $erwartet)".($erste!==''?" - erste Meldung: $erste":'')."\n";
exit($seiten_ok===$erwartet ? 0 : 1);
