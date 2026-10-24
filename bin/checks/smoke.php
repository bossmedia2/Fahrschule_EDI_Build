<?php
// Ausgabeverzeichnis; ohne FS_OUT ins Temp-Verzeichnis, nie in den Wurzelordner.
$FS_OUT = getenv('FS_OUT') ?: sys_get_temp_dir();
error_reporting(E_ALL); ini_set('display_errors','stderr');
$GLOBALS['__theme'] = $argv[1];
$GLOBALS['__blocks']=[]; $GLOBALS['__patterns']=[];
$errs=[];
set_error_handler(function($no,$str,$f,$l) use (&$errs){ $errs[]=sprintf("[%s] %s  (%s:%d)", $no===E_USER_WARNING?'WARN':'PHP', $str, basename($f), $l); return true; });
require __DIR__.'/wp-stub.php';
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

/* ---- Page-level render: build each registry page like page.php does ---- */
echo "\n".str_repeat("=",74)."\nPAGE-LEVEL SMOKE TEST (inc/pages.php registry)\n".str_repeat("=",74)."\n";
$map = fahrschule_page_map();
$pages=[];
foreach($map as $key=>$pg){
  $errs=[]; $html='';
  // Kontext der jeweiligen Seite herstellen - sonst rendert die Navigation
  // ohne aktiven Eintrag und der Seitenkopf ohne Titel.
  fs_stub_set_page($pg['slug'], $pg['title'] ?? ($pg['menu'] ?? $key), $key==='home');
  try{
    $html .= render_block(['blockName'=>'fahrschule/navigation','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    $html .= '<!--fs-main-->'; // Grenze Kopf/Inhalt für mkpages.php
    // Wie page.php: Seitenkopf, ausser die Seite bringt ihren eigenen Hero mit.
    $first = $pg['blocks'][0][0] ?? '';
    if($key!=='home' && !in_array($first,['fahrschule/location-hero','fahrschule/testimonials-hero','fahrschule/video-hero'],true)){
      $html .= render_block(['blockName'=>'fahrschule/page-hero','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    }
    if($key!=='home'){ $html .= fs_breadcrumbs(); }
    foreach(($pg['blocks']?:[]) as $b){
      $html .= render_block(['blockName'=>$b[0],'attrs'=>$b[1]??[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    }
    $html .= render_block(['blockName'=>'fahrschule/footer','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    $html .= render_block(['blockName'=>'fahrschule/cookie-banner','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    $html .= render_block(['blockName'=>'fahrschule/tiktok-badge','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    $st = $errs ? '!! '.count($errs).' issue(s)' : 'OK';
  } catch(\Throwable $e){ $st='FATAL: '.$e->getMessage(); }
  printf("%-24s %-38s %8d  %s\n", $key, '/'.$pg['slug'], strlen($html), $st);
  foreach(array_unique($errs) as $e) echo "      -> $e\n";
  $pages[$key]=$html;
}
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
