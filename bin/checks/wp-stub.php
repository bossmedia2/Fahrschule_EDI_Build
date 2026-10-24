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
function get_option($k,$d=false){return $d;} function update_option($k,$v){return true;}
function get_bloginfo($k=''){ return $k==='name'?'Fahrschule edi':($k==='description'?'Fahrschule & Fahrlehrerfachschule':'https://fahrschule-edi.de'); }
function bloginfo($k=''){echo get_bloginfo($k);}
function get_locale(){return 'de_DE';}
function language_attributes(){echo 'lang="de-DE"';}
function body_class($c=''){echo 'class="fahrschule-theme '.esc_attr(is_array($c)?implode(' ',$c):$c).'"';}
function post_class($c=''){echo 'class="post page"';}
function wp_head(){} function wp_footer(){} function wp_body_open(){}
function wp_enqueue_style(){} function wp_enqueue_script(){} function wp_register_style(){} function wp_register_script(){}
function wp_localize_script(){} function wp_add_inline_script(){}
function register_block_type($n,$a=[]){ $GLOBALS['__blocks'][$n]=$a; return true; }
function register_block_pattern($n,$a=[]){ $GLOBALS['__patterns'][$n]=$a; return true; }
function register_block_pattern_category($n,$a=[]){ return true; }
function render_block($b){ $n=$b['blockName']; if(!isset($GLOBALS['__blocks'][$n])) { trigger_error("render_block: UNREGISTERED $n",E_USER_WARNING); return ''; }
  $cb=$GLOBALS['__blocks'][$n]['render_callback']; $attrs=$b['attrs'];
  $defaults=[]; foreach(($GLOBALS['__blocks'][$n]['attributes']??[]) as $k=>$d){ $defaults[$k]=$d['default']; }
  return (string)call_user_func($cb, array_merge($defaults,$attrs)); }
function current_user_can($c){return false;} function is_user_logged_in(){return false;}
function has_site_icon(){return false;}
function wp_nonce_field($a='',$n='_wpnonce',$r=true,$e=true){ $h='<input type="hidden" name="'.$n.'" value="stub">'; if($e) echo $h; return $h; }
function wp_verify_nonce($n,$a){return 1;} function wp_create_nonce($a=''){return 'stub';}
function wp_safe_redirect($u,$s=302){return true;} function wp_mail(){return true;}
function wp_remote_post($u,$a=[]){return ['response'=>['code'=>200],'body'=>''];}
function is_wp_error($t){return false;}
function wp_get_attachment_url($id){return '/wp-content/uploads/stub-'.$id.'.jpg';}
function wp_get_attachment_image_src($id,$s='full'){return ['/wp-content/uploads/stub-'.$id.'.jpg',1200,800];}
function get_posts($a=[]){return [];}            // leere Mediathek im Test
function wp_get_attachment_image_url($id,$s='full'){return '/wp-content/uploads/stub-'.$id.'.jpg';}
function get_post_meta($id,$k='',$single=false){return $single?'':[];}
function get_post_thumbnail_id($p=null){return 0;} function has_post_thumbnail($p=null){return false;}
/* Seitenkontext. Frueher lieferte get_queried_object() immer null - jede
   Seite renderte dadurch, als waere keine geoeffnet: kein aria-current, kein
   aktiver Menuepunkt, "Stub-Titel" im Seitenkopf. Genau so blieb der
   unsichtbare aktive Flyout-Eintrag (Oktober 2026) dem Gate verborgen.
   fs_stub_set_page() setzt den Kontext, den WordPress fuer eine Seite haette. */
if(!class_exists('WP_Post')){ class WP_Post { public $ID=0; public $post_name=''; public $post_title=''; public $post_type='page'; public $post_content='';
  public function __construct($a=[]){ foreach($a as $k=>$v) $this->$k=$v; } } }
function fs_stub_set_page($slug,$title,$front=false){
  $GLOBALS['__is_front']=$front;
  $GLOBALS['__queried']=$front?null:new WP_Post(['ID'=>crc32($slug)%100000,'post_name'=>$slug,'post_title'=>$title]);
}
function get_permalink($p=null){ $q=$GLOBALS['__queried']??null; return home_url('/'.($q?$q->post_name.'/':'')); }
function get_the_title($p=null){ $q=$GLOBALS['__queried']??null; return $q?$q->post_title:'Startseite'; } function get_the_content($m=null){return '';}
function get_post_field($f,$p=null){return '';}
function the_content(){} function the_excerpt(){} function the_post(){} function have_posts(){return false;}
function the_ID(){echo '1';}
function get_search_form($e=true){$h='<form role="search"><label class="fs-visually-hidden" for="s">Suche</label><input id="s" type="search" name="s"><button type="submit">Suchen</button></form>'; if($e) echo $h; return $h;}
function get_search_query(){return '';}
function get_header(){} function get_footer(){}
function get_page_by_path($p,$o='OBJECT',$t='page'){return null;}
function get_queried_object(){return $GLOBALS['__queried']??null;} function get_queried_object_id(){ $q=$GLOBALS['__queried']??null; return $q?$q->ID:0; }
function get_term_link($t){return home_url('/term/');} function term_description($t=0){return '';}
function get_previous_posts_page_link(){return home_url('/');}
function wp_get_document_title(){return 'Fahrschule edi';}
function wp_insert_post($a,$e=false){return 1;}
function strip_shortcodes($s){return $s;} function excerpt_remove_blocks($s){return $s;}
function is_front_page(){return (bool)($GLOBALS['__is_front']??false);} function is_home(){return false;}
function is_singular($t=''){ $q=$GLOBALS['__queried']??null; if(!$q) return false; return $t==='' || $t===$q->post_type || (is_array($t)&&in_array($q->post_type,$t,true)); } function is_404(){return false;} function is_search(){return false;}
function is_category(){return false;} function is_tag(){return false;} function is_tax(){return false;}
function is_author(){return false;} function is_attachment(){return false;} function is_paged(){return false;}
function current_time($t='mysql',$g=0){return date('Y-m-d H:i:s');}
function flush_rewrite_rules(){} function add_options_page(){} function register_setting(){}
function settings_fields($g){} function submit_button($t=null){} function checked($a,$b=true,$e=true){}
function mb_strrpos_stub(){}
function number_format_i18n($n,$d=0){return number_format($n,$d,',','.');}