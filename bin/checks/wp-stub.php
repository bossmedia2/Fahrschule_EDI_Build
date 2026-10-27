<?php
/* Minimal WordPress stub so the theme's render callbacks can be executed
   outside a real WP install.  Only enough to smoke-test HTML output. */
define('ABSPATH', __DIR__.'/');
// Rueckgabetyp-Konstanten, die WordPress in wp-includes/constants.php setzt.
if(!defined('OBJECT')) define('OBJECT','OBJECT');
if(!defined('ARRAY_A')) define('ARRAY_A','ARRAY_A');
if(!defined('ARRAY_N')) define('ARRAY_N','ARRAY_N');
$GLOBALS['__actions']=[]; $GLOBALS['__filters']=[];
function add_action($h,$c,$p=10,$a=1){ $GLOBALS['__actions'][$h][]=$c; }
function remove_action($h,$c,$p=10){ }
function add_filter($h,$c,$p=10,$a=1){ $GLOBALS['__filters'][$h][]=$c; }
function apply_filters($h,$v){ $args=func_get_args(); if(!empty($GLOBALS['__filters'][$h])) foreach($GLOBALS['__filters'][$h] as $cb){ $v=call_user_func_array($cb, array_merge([$v], array_slice($args,2))); } return $v; }
function add_shortcode($t,$c){}
function add_theme_support(){} function add_editor_style(){} function register_nav_menus(){}
function __($s,$d=null){return $s;} function _e($s,$d=null){echo $s;} function esc_html__($s,$d=null){return $s;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_url($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_url_raw($s){return (string)$s;}
function esc_textarea($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function wp_kses_post($s){return $s;}
function sanitize_title($s,$f='',$c='save'){ $s=strtolower(remove_accents_stub((string)$s));
  $s=preg_replace('/[^a-z0-9\s\-_]/','',$s); $s=preg_replace('/[\s_]+/','-',$s);
  return trim(preg_replace('/-+/','-',$s),'-') ?: $f; }
function remove_accents_stub($s){ return strtr($s, ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue']); }
function sanitize_key($s){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$s));}
function sanitize_text_field($s){return trim(strip_tags((string)$s));}
function sanitize_textarea_field($s){return trim(strip_tags((string)$s));}
function sanitize_email($s){return (string)$s;} function is_email($s){return (bool)filter_var($s,FILTER_VALIDATE_EMAIL);}
function wp_strip_all_tags($s){return strip_tags((string)$s);}
function wp_unslash($s){return $s;}
function home_url($p='/'){return 'https://fahrschule-edi.de'.$p;}
function admin_url($p=''){return 'https://fahrschule-edi.de/wp-admin/'.$p;}
function site_url($p=''){return 'https://fahrschule-edi.de'.$p;}
function get_template_directory(){return $GLOBALS['__theme'];}
function get_template_directory_uri(){return '/wp-content/themes/fahrschule-edi';}
function get_stylesheet_directory_uri(){return get_template_directory_uri();}
function trailingslashit($s){return rtrim((string)$s,'/').'/';}
function untrailingslashit($s){return rtrim((string)$s,'/');}
function add_query_arg($k,$v=null,$u=null){return ($u?:'/').'?'.$k.'='.$v;}
function wp_parse_args($a,$d=[]){ return (is_array($d)&&$d) ? array_merge($d,(array)$a) : (array)$a; } // wie WordPress: schlichtes array_merge. Ein frueherer array_filter hier verwarf leere Werte und verbarg damit genau die Faelle, in denen ein geleertes Feld eine leere Huelle hinterlaesst.
function wp_parse_url($u,$c=-1){return parse_url($u,$c);}
function wp_json_encode($d,$f=0){return json_encode($d,$f);}
function get_option($k,$d=false){return $GLOBALS['__options'][$k]??$d;} function update_option($k,$v,$a=null){$GLOBALS['__options'][$k]=$v;return true;}
function get_bloginfo($k=''){ return $k==='name'?'Fahrschule edi':($k==='description'?'Fahrschule & Fahrlehrerfachschule':'https://fahrschule-edi.de'); }
function bloginfo($k=''){echo get_bloginfo($k);}
function get_locale(){return 'de_DE';}
function language_attributes(){echo 'lang="de-DE"';}
function body_class($c=''){echo 'class="fahrschule-theme '.esc_attr(is_array($c)?implode(' ',$c):$c).'"';}
function post_class($c=''){echo 'class="post page"';}
function wp_head(){} function wp_footer(){} function wp_body_open(){}
function wp_enqueue_style(){} function wp_enqueue_script(){} function wp_register_style(){} function wp_register_script(){}
function wp_localize_script($h='',$n='',$d=null){ $GLOBALS['__localized'][$n]=$d; return true; } function wp_add_inline_script(){}
function register_block_type($n,$a=[]){ $GLOBALS['__blocks'][$n]=$a; return true; }
function register_block_pattern($n,$a=[]){ $GLOBALS['__patterns'][$n]=$a; return true; }
function register_block_pattern_category($n,$a=[]){ return true; }
function render_block($b){ $n=$b['blockName']; if(!isset($GLOBALS['__blocks'][$n])) { trigger_error("render_block: UNREGISTERED $n",E_USER_WARNING); return ''; }
  $cb=$GLOBALS['__blocks'][$n]['render_callback']; $attrs=$b['attrs'];
  $defaults=[]; foreach(($GLOBALS['__blocks'][$n]['attributes']??[]) as $k=>$d){ $defaults[$k]=$d['default']; }
  return (string)call_user_func($cb, array_merge($defaults,$attrs)); }
function current_user_can($c){return !empty($GLOBALS['__can']);} function delete_option($k){unset($GLOBALS['__options'][$k]);return true;} function is_user_logged_in(){return false;}
function has_site_icon(){return false;}
function wp_nonce_field($a='',$n='_wpnonce',$r=true,$e=true){ $h='<input type="hidden" name="'.$n.'" value="stub">'; if($e) echo $h; return $h; }
function wp_verify_nonce($n,$a){return $n==='stub'?1:false;} function wp_slash($s){return $s;} function _n($a,$b,$n,$d=null){return $n==1?$a:$b;} function wp_create_nonce($a=''){return 'stub';}
function wp_safe_redirect($u,$s=302){return true;} function wp_mail(){return true;}
function wp_remote_post($u,$a=[]){return ['response'=>['code'=>200],'body'=>''];}
if(!class_exists('WP_Error')){ class WP_Error { public $c; public $m; public function __construct($c='',$m=''){ $this->c=$c; $this->m=$m; } public function get_error_message(){ return $this->m; } } }
function is_wp_error($t){return $t instanceof WP_Error;}
/* ---- Netz und Mediathek für den Social-Abruf (bin/checks/social.php) ----
   Kein echter Abruf: __http bestimmt die Antwort, jede Anfrage wird notiert. */
function wp_remote_get($u,$a=[]){ $GLOBALS['__http_calls'][]=$u; $h=$GLOBALS['__http']??null; return is_callable($h)?$h($u):new WP_Error('http','kein Netz im Test'); }
function wp_remote_retrieve_response_code($r){ return is_array($r)?(int)($r['response']['code']??0):0; }
function wp_remote_retrieve_body($r){ return is_array($r)?(string)($r['body']??''):''; }
function download_url($u,$t=300){ $GLOBALS['__downloads'][]=$u; if(!empty($GLOBALS['__download_fail'])) return new WP_Error('dl','Zeitüberschreitung'); $f=tempnam(sys_get_temp_dir(),'fs'); file_put_contents($f,'x'); return $f; }
function media_handle_sideload($file,$pid=0,$desc=null){ static $n=5000; $id=++$n; if(is_file($file['tmp_name'])) unlink($file['tmp_name']); $GLOBALS['__sideloads'][$id]=$file['name']; return $id; }
function wp_delete_file($f){ if(is_file($f)) unlink($f); }
function update_post_meta($id,$k,$v){ $GLOBALS['__meta'][$id][$k]=$v; return true; }
function register_rest_route($ns,$r,$a){ $GLOBALS['__rest'][$ns.$r]=$a; return true; }
function rest_ensure_response($x){ return $x; }
function wp_get_attachment_url($id){return '/wp-content/uploads/stub-'.$id.'.jpg';}
function wp_get_attachment_image_src($id,$s='full'){return ['/wp-content/uploads/stub-'.$id.'.jpg',1200,800];}
function post_type_exists($t){ return isset($GLOBALS['__post_types'][$t]); }
function taxonomy_exists($t){ return isset($GLOBALS['__taxonomies'][$t]); }
function get_the_terms($p,$tax){ $id=is_object($p)?$p->ID:(int)$p; return $GLOBALS['__terms'][$id][$tax] ?? false; }
function wp_list_pluck($l,$f){ return array_map(fn($x)=>is_object($x)?$x->$f:$x[$f],$l); }
if(!function_exists('wpautop')){ function wpautop($s){ $s=trim((string)$s); return $s===''?'':'<p>'.preg_replace('/\n\s*\n/','</p><p>',$s).'</p>'; } }
function remove_filter($h,$c,$p=10){ if(empty($GLOBALS['__filters'][$h])) return false; foreach($GLOBALS['__filters'][$h] as $i=>$cb){ if($cb===$c){ unset($GLOBALS['__filters'][$h][$i]); return true; } } return false; }
/* Minimaler $wpdb: prepare() quotet wie das Original, esc_like() maskiert
   die LIKE-Jokerzeichen. Genug, um die erzeugte WHERE-Klausel auszuwerten. */
class FS_Stub_WPDB { public $posts='wp_posts';
  public function esc_like($t){ return addcslashes($t,'_%\\'); }
  public function prepare($q,...$args){ $i=0; return preg_replace_callback('/%[sd]/',function($m)use(&$i,$args){ $v=$args[$i++]; return $m[0]==='%d'?(int)$v:"'".addslashes($v)."'"; },$q); } }
$GLOBALS['wpdb']=new FS_Stub_WPDB();
/* Mediathek im Test: leer, außer ein Prüfschritt legt $GLOBALS['__attachments']
   an (Liste von post_name). Ein posts_where-Filter wird wirklich angewendet:
   die LIKE-Klausel auf post_name wird ausgewertet - so prüft der Test auch
   das erzeugte SQL-Muster, nicht nur die PHP-Seite. */
function get_posts($a=[]){
  if(isset($a['meta_key'])){ $ids=[]; foreach(($GLOBALS['__meta']??[]) as $id=>$m) if(($m[$a['meta_key']]??null)===($a['meta_value']??null)) $ids[]=$id; return array_slice($ids,0,$a['numberposts']??10); }
  if(($a['post_type']??'')==='job_listing'){ $GLOBALS['__last_query']=$a; return $GLOBALS['__jobs']??[]; }
  $names=$GLOBALS['__attachments']??[]; if(!$names || ($a['post_type']??'')!=='attachment') return [];
  if(($a['suppress_filters']??true)===false){
    $where=apply_filters('posts_where','');
    if(preg_match_all("/post_name LIKE '((?:[^'\\\\]|\\\\.)*)'/",$where,$mm)){
      foreach($mm[1] as $like){
        $like=stripslashes($like);
        $re='';$n=strlen($like);
        for($i=0;$i<$n;$i++){ $c=$like[$i];
          if($c==='\\' && $i+1<$n){ $re.=preg_quote($like[++$i],'/'); }
          elseif($c==='%'){ $re.='.*'; } elseif($c==='_'){ $re.='.'; } else { $re.=preg_quote($c,'/'); } }
        $names=array_values(array_filter($names,fn($x)=>preg_match('/^'.$re.'$/',$x)));
      }
    }
  }
  sort($names);
  $out=[]; foreach(array_slice($names,0,$a['posts_per_page']??10) as $i=>$nm){ $out[]=new WP_Post(['ID'=>1000+$i,'post_name'=>$nm,'post_type'=>'attachment']); }
  return $out;
}
function wp_get_attachment_metadata($id){ return $GLOBALS['__att_meta'][$id] ?? array(); }
function wp_get_attachment_image_url($id,$s='full'){return '/wp-content/uploads/stub-'.$id.'.jpg';}
function get_post_meta($id,$k='',$single=false){ $v=$GLOBALS['__meta'][$id][$k]??null; return $single?($v??''):($v===null?[]:[$v]); }
function get_post_thumbnail_id($p=null){return 0;} function has_post_thumbnail($p=null){return false;}
/* Seitenkontext. Frueher lieferte get_queried_object() immer null - jede
   Seite renderte dadurch, als waere keine geoeffnet: kein aria-current, kein
   aktiver Menuepunkt, "Stub-Titel" im Seitenkopf. Genau so blieb der
   unsichtbare aktive Flyout-Eintrag (Oktober 2026) dem Gate verborgen.
   fs_stub_set_page() setzt den Kontext, den WordPress fuer eine Seite haette. */
if(!class_exists('WP_Post')){ class WP_Post { public $ID=0; public $post_parent=0; public $post_name=''; public $post_title=''; public $post_type='page'; public $post_content='';
  public function __construct($a=[]){ foreach($a as $k=>$v) $this->$k=$v; } } }
function fs_stub_set_page($slug,$title,$front=false,$content=''){
  $GLOBALS['__is_front']=$front;
  $GLOBALS['__queried']=$front?null:new WP_Post(['ID'=>crc32($slug)%100000,'post_name'=>$slug,'post_title'=>$title,'post_content'=>$content]);
}
function get_permalink($p=null){ $q=($p instanceof WP_Post)?$p:($GLOBALS['__queried']??null); return home_url('/'.($q?($q->post_type==='job_listing'?'job/':'').$q->post_name.'/':'')); }
function get_the_title($p=null){ if($p instanceof WP_Post) return $p->post_title; $q=$GLOBALS['__queried']??null; return $q?$q->post_title:'Startseite'; } function get_the_content($m=null){return '';}
function get_post_field($f,$p=null){return '';}
function the_content(){} function the_excerpt(){} function the_post(){} function have_posts(){return false;}
function the_ID(){echo '1';}
function get_search_form($e=true){$h='<form role="search" method="get" class="search-form" action="/"><label><span class="screen-reader-text">Suche nach:</span><input type="search" class="search-field" placeholder="Suche …" value="" name="s"></label><input type="submit" class="search-submit" value="Suche"></form>'; if(is_array($e)) $e=$e['echo']??true; if($e) echo $h; return $h;}
function get_post_ancestors($p){ return []; }
function get_search_query(){return '';}
function get_header(){} function get_footer(){}
/* Seiten-Datenbank für bin/checks/seitenanlage.php: aus, solange __db_on
   nicht gesetzt ist - dann verhalten sich beide Funktionen wie bisher. */
function fs_stub_db_post($id){ foreach(($GLOBALS['__db_pages']??[]) as $p) if($p->ID===(int)$id) return $p; return null; }
function get_post($p=null){ return $p instanceof WP_Post ? $p : fs_stub_db_post($p); }
function get_page_uri($p){ $p=get_post($p); foreach(($GLOBALS['__db_pages']??[]) as $path=>$q) if($q===$p) return $path; return ''; }
function wp_update_post($a,$e=false){ $p=fs_stub_db_post($a['ID']??0); if(!$p) return 0; foreach($a as $k=>$v) if($k!=='ID') $p->$k=($k==='post_content'?wp_unslash($v):$v); $GLOBALS['__db_updates'][]=$a['ID']; return $a['ID']; }
function get_page_by_path($p,$o='OBJECT',$t='page'){ return $GLOBALS['__db_pages'][trim((string)$p,'/')] ?? null; }
function get_queried_object(){return $GLOBALS['__queried']??null;} function get_queried_object_id(){ $q=$GLOBALS['__queried']??null; return $q?$q->ID:0; }
function get_term_link($t){return home_url('/term/');} function term_description($t=0){return '';}
function get_previous_posts_page_link(){return home_url('/');}
function wp_get_document_title(){return 'Fahrschule edi';}
function wp_insert_post($a,$e=false){
  if(empty($GLOBALS['__db_on'])) return 1;
  $id=1000+count($GLOBALS['__db_pages']??[])+1; $path=$a['post_name'];
  if(!empty($a['post_parent'])) foreach($GLOBALS['__db_pages'] as $pp=>$po) if($po->ID===$a['post_parent']) $path=$pp.'/'.$a['post_name'];
  $post=new WP_Post(['ID'=>$id,'post_name'=>$a['post_name'],'post_title'=>$a['post_title'],'post_content'=>$a['post_content']??'','post_parent'=>$a['post_parent']??0]);
  $GLOBALS['__db_pages'][$path]=$post;
  foreach(($a['meta_input']??[]) as $k=>$v) $GLOBALS['__meta'][$id][$k]=$v;
  $GLOBALS['__db_inserts'][]=$path;
  return $id; }
function strip_shortcodes($s){return $s;} function excerpt_remove_blocks($s){return $s;}
function is_front_page(){return (bool)($GLOBALS['__is_front']??false);} function is_home(){return false;}
function is_singular($t=''){ $q=$GLOBALS['__queried']??null; if(!$q) return false; return $t==='' || $t===$q->post_type || (is_array($t)&&in_array($q->post_type,$t,true)); } function is_404(){return false;} function is_search(){return false;}
function is_category(){return false;} function is_tag(){return false;} function is_tax(){return false;}
function is_author(){return false;} function is_attachment(){return false;} function is_paged(){return false;}
function current_time($t='mysql',$g=0){return date('Y-m-d H:i:s');}
function flush_rewrite_rules(){} function add_options_page(){} function register_setting(){}
function settings_fields($g){} function submit_button($t=null){} function checked($a,$b=true,$e=true){}
/* Wie wp-includes/compat.php: ohne mbstring ersetzt WordPress genau diese
   beiden Funktionen - und keine weitere. Der Stub tut dasselbe, damit die
   Prüfungen auf einem Rechner ohne mbstring das echte Verhalten zeigen. */
if(!function_exists('mb_substr')){ function mb_substr($s,$start,$len=null,$enc=null){ preg_match_all('/./us',(string)$s,$m); return implode('',array_slice($m[0],$start,$len)); } }
if(!function_exists('mb_strlen')){ function mb_strlen($s,$enc=null){ return preg_match_all('/./us',(string)$s); } }
function number_format_i18n($n,$d=0){return number_format($n,$d,',','.');}
/* ---- Block-Serialisierung wie WordPress (wp-includes/blocks.php) ----
   Gleiche Maskierung der Attribute, gleiche Kurzform für Kern-Blöcke,
   damit der Test dasselbe Markup sieht, das die Seitenanlage schreibt. */
if(!function_exists('serialize_block_attributes')){
function serialize_block_attributes($a){
  $s=json_encode($a,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  $s=preg_replace('/--/','\\u002d\\u002d',$s);
  $s=preg_replace('/</','\\u003c',$s); $s=preg_replace('/>/','\\u003e',$s);
  $s=preg_replace('/&/','\\u0026',$s); $s=preg_replace('/\\\\"/','\\u0022',$s);
  return $s; }
function strip_core_block_namespace($n=null){ return (is_string($n)&&str_starts_with($n,'core/'))?substr($n,5):$n; }
function get_comment_delimited_block_content($name,$attrs,$content){
  if($name===null) return $content;
  $n=strip_core_block_namespace($name);
  $sa=empty($attrs)?'':serialize_block_attributes($attrs).' ';
  if(empty($content)) return sprintf('<!-- wp:%s %s/-->',$n,$sa);
  return sprintf('<!-- wp:%s %s-->%s<!-- /wp:%s -->',$n,$sa,$content,$n); }
function serialize_block($b){
  $c=''; $i=0; foreach($b['innerContent'] as $chunk){ $c.=is_string($chunk)?$chunk:serialize_block($b['innerBlocks'][$i++]); }
  if(!is_array($b['attrs'])) $b['attrs']=[];
  return get_comment_delimited_block_content($b['blockName'],$b['attrs'],$c); }
function serialize_blocks($bs){ return implode('',array_map('serialize_block',$bs)); }
}
