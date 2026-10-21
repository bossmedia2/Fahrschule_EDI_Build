<?php
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
file_put_contents(getenv('FS_OUT').'/rendered.json', json_encode($outs));

/* ---- Page-level render: build each registry page like page.php does ---- */
echo "\n".str_repeat("=",74)."\nPAGE-LEVEL SMOKE TEST (inc/pages.php registry)\n".str_repeat("=",74)."\n";
$map = fahrschule_page_map();
$pages=[];
foreach($map as $key=>$pg){
  $errs=[]; $html='';
  try{
    $html .= render_block(['blockName'=>'fahrschule/navigation','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]);
    if(empty($pg['blocks'])){ $html .= render_block(['blockName'=>'fahrschule/page-hero','attrs'=>[],'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]); }
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
file_put_contents(getenv('FS_OUT').'/pages.json', json_encode($pages));
echo "\nSEO smoke: ";
$errs=[];
if(function_exists('fahrschule_business_data')){ $bd=fahrschule_business_data(); echo count($bd)." business records; "; }
echo $errs? implode(' | ',$errs) : "no errors\n";
