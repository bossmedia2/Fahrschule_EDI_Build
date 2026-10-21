<?php
error_reporting(E_ALL); ini_set('display_errors','stderr');
$GLOBALS['__theme']=$argv[1];
/* Prüft, ob sich jedes Feld mit Vorgabe leeren lässt, ohne eine leere Hülle
   zu hinterlassen - der Fehler, der im Oktober 2026 97 von 136 Feldern betraf
   und weder vom Lint noch vom Smoke-Test gesehen wurde. */ $GLOBALS['__blocks']=[];
require __DIR__.'/wp-stub.php';
define('FAHRSCHULE_DIR',$argv[1]); define('FAHRSCHULE_URI','/u'); define('FAHRSCHULE_VERSION','1');
foreach(['helpers','pdf-texts','media','pages','render-callbacks','blocks-config'] as $f) require $argv[1].'/inc/'.$f.'.php';
$cfg=fahrschule_blocks_config();
foreach($cfg as $n=>$c){ $at=[]; if(!empty($c['fields'])) foreach($c['fields'] as $k=>$fl) $at[$k]=['type'=>((($fl['control']??'')==='repeater')?'array':'string'),'default'=>$fl['default']];
  register_block_type($n,['attributes'=>$at,'render_callback'=>$c['render']]); }
$R=function($n,$attrs){ return render_block(['blockName'=>$n,'attrs'=>$attrs,'innerBlocks'=>[],'innerHTML'=>'','innerContent'=>[]]); };
$ignored=[]; $emptyEl=[]; $okF=0; $total=0;
$skipped=0;
foreach($cfg as $name=>$c){
  if(empty($c['fields'])) continue;
  /* Bloecke, die ohne Konfiguration gar nichts ausgeben (Bereichsliste,
     Kurs-Slots), lassen sich so nicht vergleichen: leer bleibt leer. Sie
     werden an anderer Stelle geprueft - hier uebersprungen statt faelschlich
     als Fehler gemeldet. */
  if(!empty($c['may_be_empty'])){ $skipped += count($c['fields']); continue; }
  foreach($c['fields'] as $key=>$f){
    if(($f['control']??'')==='select') continue;
    if(($f['control']??'')==='repeater') continue;   // eigener Prüfteil weiter unten
    if(!is_string($f['default']) || trim($f['default'])==='') continue;
    $total++;
    $base = $R($name,[]);              // Vorgabe
    $cleared = $R($name,[$key=>'']);   // Feld geleert
    // Bewusste Ausnahmen: das Logo faellt auf die Medien-Registry zurueck.
    // Ein Header ohne Logo waere kaputt, nicht leer.
    $erlaubt = ['fahrschule/navigation.logo', 'fahrschule/footer.logo'];
    if($base === $cleared){ if(!in_array("$name.$key",$erlaubt,true)) $ignored[]="$name.$key"; continue; }
    // Nur zählen, was das GELEERTE Feld verursacht: leere Elemente vorher/nachher vergleichen.
    $rx='#<(h[1-6]|p|span|div|li|a)\b[^>]*>\s*</\1>#';
    $nb=preg_match_all($rx,$base); $nc=preg_match_all($rx,$cleared);
    if($nc>$nb){ preg_match_all($rx,$cleared,$mm); $emptyEl[]="$name.$key  ->  ".$mm[0][count($mm[0])-1]; continue; }
    $okF++;
  }
}
printf("Textfelder mit Vorgabe geprüft: %d (übersprungen, da ohne Konfiguration leer: %d)\n", $total, $skipped);
printf("  ok (Feld verschwindet sauber) : %d\n", $okF);
printf("  LEEREN WIRKUNGSLOS            : %d\n", count($ignored));
printf("  HINTERLÄSST LEERES ELEMENT    : %d\n", count($emptyEl));
echo "\nLeeren wirkungslos:\n"; foreach(array_slice($ignored,0,14) as $x) echo "   - $x\n";
if(count($ignored)>14) echo "   ... +".(count($ignored)-14)." weitere\n";
echo "\nLeeres Element bleibt stehen:\n"; foreach(array_slice($emptyEl,0,14) as $x) echo "   - $x\n";
if(count($emptyEl)>14) echo "   ... +".(count($emptyEl)-14)." weitere\n";

/* ---- Wiederholfelder: eigene Zeilen wirken, Leeren entfernt den Abschnitt ---- */
$repBad = [];
foreach($cfg as $name=>$c){
  if(empty($c['fields'])) continue;
  foreach($c['fields'] as $key=>$f){
    if(($f['control']??'')!=='repeater') continue;
    $cols  = max(1, count($f['columns'] ?? []));
    $probe = 'ZZPROBEZZ';
    $row   = array_fill(0, $cols, $probe);
    $custom = $R($name,[$key=>[$row]]);
    $empty  = $R($name,[$key=>[]]);
    $base   = $R($name,[]);
    if(strpos($custom,$probe)===false){ $repBad[]="$name.$key: eigene Zeile erscheint nicht"; }
    if(strlen($empty) >= strlen($base)){ $repBad[]="$name.$key: Leeren verkleinert die Ausgabe nicht"; }
    /* Nur Container zählen, die durch das Leeren NEU entstehen. Manche sind
       von Haus aus leer - .fs-cta__bg etwa ist eine reine Hintergrundebene. */
    $rx='#<(div|ul)\b[^>]*>\s*</(div|ul)>#';
    if(preg_match_all($rx,$empty) > preg_match_all($rx,$base)){
      preg_match_all($rx,$empty,$mm);
      $repBad[]="$name.$key: leerer Container bleibt stehen (".end($mm[0]).")";
    }
  }
}
printf("\nWiederholfelder geprüft: %d\n", count($repBad)? count($repBad) : 0);
foreach($repBad as $x) echo "  FEHLER: $x\n";
if(!$repBad) echo "  ok: alle Wiederholfelder sind bearbeitbar und leerbar\n";

if ( count($ignored) || count($emptyEl) || count($repBad) ) {
	printf("\nLEERBARKEIT: %d Fehler\n", count($ignored)+count($emptyEl)+count($repBad));
	exit(1);
}
echo "\nLEERBARKEIT: bestanden\n";
