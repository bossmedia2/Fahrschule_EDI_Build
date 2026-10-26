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

// run the theme's own registration loop
$cfg = fahrschule_blocks_config();
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
$pages=[];
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
  } catch(\Throwable $e){ $st='FATAL: '.$e->getMessage(); }
  printf("%-24s %-38s %8d  %s\n", $key, '/'.$pg['slug'], strlen($html), $st);
  foreach(array_unique($errs) as $e) echo "      -> $e\n";
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
/* ---- Social-Feed: Prüfseite mit gemischten Kacheln ----
 * Bilder sind Theme-Dateien, damit sie ohne Mediathek auflösen. Neun Zeilen:
 * eine ohne Bild, eine ohne Beschreibung -> sieben Kacheln. */
$social_rows = [
  ['tiktok','edi-logo','https://www.tiktok.com/@edi_fahrschule/video/1','Logo der Fahrschule edi auf pinkem Grund'],
  ['instagram','edi-partner-certqua','','CERTQUA-Zertifikat der Fahrschule'],
  ['tiktok','edi-partner-schalke','','Partnerlogo Schalke'],
  ['instagram','','','Kachel ohne Bild'],
  ['tiktok','edi-logo','','Zweites TikTok-Cover'],
  ['instagram','edi-partner-certqua','https://www.instagram.com/p/abc/',''],
  ['instagram','edi-logo','','Drittes Instagram-Bild'],
  ['tiktok','edi-partner-certqua','','Viertes TikTok-Cover'],
  ['instagram','edi-partner-schalke','','Letzte Kachel'],
];
$sb = fn($attrs)=>render_block(['blockName'=>'fahrschule/instagram-slider','attrs'=>$attrs,'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
$serr=[];
$h = $sb(['items'=>$social_rows]);
preg_match_all('#<a class="fs-ig__post"([^>]*)>(.*?)</a>#s',$h,$mm,PREG_SET_ORDER);
if(count($mm)!==7) $serr[]=count($mm).' Kacheln statt 7 (ohne Bild bzw. ohne Beschreibung muss entfallen)';
foreach($mm as $i=>$m){
  if(strpos($m[1],'target="_blank"')===false || strpos($m[1],'rel="noopener')===false) $serr[]="Kachel ".($i+1).": kein Link in neuem Tab";
  if(!preg_match('#<img[^>]+alt="([^"]+)"#',$m[2])) $serr[]="Kachel ".($i+1).": ohne Alt-Text";
  if(strpos($m[2],'<svg')===false) $serr[]="Kachel ".($i+1).": ohne Plattform-Icon";
}
$alts=array_map(fn($m)=>preg_match('#alt="([^"]+)"#',$m[2],$x)?$x[1]:'',$mm);
if(($alts[0]??'')!=='Logo der Fahrschule edi auf pinkem Grund' || ($alts[6]??'')!=='Letzte Kachel') $serr[]='Reihenfolge folgt nicht den Zeilen';
if(substr_count($h,'data-plattform="tiktok"')!==4 || substr_count($h,'data-plattform="instagram"')!==3) $serr[]='Plattform je Kachel falsch';
if(strpos($h,'href="https://www.instagram.com/fahrschuleedi/" target="_blank" rel="noopener noreferrer" title="CERTQUA')===false) $serr[]='leerer Link fällt nicht auf das Plattform-Profil zurück';
if(preg_match('#(tiktok\.com|instagram\.com|tiktokcdn|cdninstagram)[^"]*\.(js|jpg|jpeg|png|webp)|<script|<iframe|embed#i',$h)) $serr[]='Embed, Skript oder Fremdbild im Social-Feed';
if(substr_count($sb(['items'=>array_slice($social_rows,0,5)]),'class="fs-ig__post"')!==4) $serr[]='5 Zeilen (eine ohne Bild) ergeben nicht 4 Kacheln';
if(trim($sb(['items'=>[]]))!=='') $serr[]='leere Liste blendet die Sektion nicht aus';
if(trim($sb([]))!=='') $serr[]='Vorgabe ohne Bilder blendet die Sektion nicht aus';
$k=$sb(['items'=>$social_rows,'instaHandle'=>'']);
if(substr_count($k,'fs-ig__profile')!==1 || substr_count($k,'class="fs-btn fs-btn--lg fs-btn--primary"')!==1 || strpos($k,'@edi_fahrschule')===false) $serr[]='nur TikTok-Handle: nicht genau ein Profil-Knopf';
echo "\nSocial-Feed (Prüfseite): ".($serr? "!! ".implode(' | ',$serr)."\n" : "ok: 9 Zeilen -> 7 Kacheln (ohne Bild/Beschreibung entfällt), gemischt TikTok/Instagram, Links in neuem Tab, Alt-Text, Reihenfolge, Profil-Rückfall, leer = ausgeblendet, kein Embed\n");
fs_stub_set_page('social','Social-Feed (Prüfseite)',false,'');
$pages['_social']=fs_fse_render_template($GLOBALS['__theme'],'page',serialize_blocks([['blockName'=>'fahrschule/instagram-slider','attrs'=>['items'=>$social_rows],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]]));

file_put_contents($FS_OUT.'/pages.json', json_encode($pages));
// Erwartete URL je Seite - die Browserprüfung vergleicht damit aria-current.
$urls=[]; foreach($map as $key=>$pg){ $urls[$key]=fs_url($key); }
file_put_contents($FS_OUT.'/urls.json', json_encode($urls));
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
