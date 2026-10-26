<?php
/**
 * AP 5: Formulare über Contact Form 7 - Begleit-Plugin und Theme zusammen.
 *
 * CF7 selbst läuft hier nicht (keine WordPress-Installation). Nachgebaut ist
 * nur seine Schnittstelle (WPCF7_ContactForm, wpcf7_contact_form(),
 * Form-Tags, Submission). Die Formular-Tags werden mit dem Muster aus
 * WPCF7_FormTagsManager zerlegt, damit ein Tippfehler im Formulartext auffällt.
 *
 * Aufruf: php formulare.php [<theme-dir> <plugin-dir>]  (Vorgabe: neben bin/)
 */
error_reporting( E_ALL );
$GLOBALS['__theme'] = $argv[1] ?? dirname( __DIR__, 2 ) . '/fahrschule-edi';
$plugin_dir         = $argv[2] ?? dirname( __DIR__, 2 ) . '/edi-integrationen';
require __DIR__ . '/wp-stub.php';
define( 'FAHRSCHULE_DIR', $GLOBALS['__theme'] ); define( 'FAHRSCHULE_URI', '/u' ); define( 'FAHRSCHULE_VERSION', '1' );
foreach ( array( 'helpers', 'pdf-texts', 'media', 'pages', 'seo', 'render-callbacks', 'forms' ) as $f ) { require $GLOBALS['__theme'] . '/inc/' . $f . '.php'; }

$fails = array();
function fail( $m ) { global $fails; $fails[] = $m; echo "  FEHLER: $m\n"; }
function ok( $m ) { echo "  ok: $m\n"; }
if ( ! function_exists( 'do_shortcode' ) ) { function do_shortcode( $s ) { $GLOBALS['__sc'][] = $s; return '<div class="wpcf7">' . htmlspecialchars( $s ) . '</div>'; } }

/* ---- 1) Theme ohne Plugin: eingebauter Rückfall ------------------------- */
$h = fs_formular( 'kontakt' );
if ( false === strpos( $h, 'admin-post.php' ) || false === strpos( $h, 'name="fs_privacy"' ) || false === strpos( $h, 'name="fs_alter"' ) || false === strpos( $h, 'Art. 8 DSGVO' ) ) {
	fail( 'ohne CF7: Kontakt-Rückfall fehlt oder ohne beide Einwilligungen' );
}
$s = fs_formular( 'schnellanmeldung', array( 'standort' => 'Essen' ) );
if ( false === strpos( $s, 'value="Essen"' ) || false === strpos( $s, 'name="fs_alter"' ) || false === strpos( $s, 'Klasse BF17' ) || false === strpos( $s, 'Klasse DE' ) ) {
	fail( 'ohne CF7: Schnellanmeldung ohne Standort, Einwilligung oder vollständige Klassenliste (F3)' );
}
$_GET['fs_kontakt'] = 'ok';
if ( false === strpos( fs_formular( 'kontakt' ), 'role="status"' ) ) { fail( 'ohne JavaScript: Erfolgsmeldung des Rückfalls nicht sichtbar' ); }
unset( $_GET['fs_kontakt'] );
if ( 'essen@fahrschule-edi.de' !== fahrschule_standort_email( 'Essen' ) || 'info@fahrschule-edi.de' !== fahrschule_standort_email( 'Bochum' ) ) {
	fail( 'Rückfall-Empfänger nicht aus den Stammdaten' );
}
if ( ! $fails ) { ok( 'ohne CF7: eingebaute Formulare mit beiden Einwilligungen, Klassen aus F3, Empfänger aus Stammdaten, Erfolg ohne JS sichtbar' ); }

/* ---- 2) CF7-Schnittstelle nachbauen, Plugin laden ------------------------ */
class WPCF7_ContactForm {
	public static $alle = array(); public static $neu = 0;
	private $id = 0; private $props = array();
	public static function get_template( $a = array() ) { $c = new self(); $c->props = array( 'title' => $a['title'], 'form' => '', 'mail' => array( 'recipient' => '[_site_admin_email]', 'subject' => '', 'body' => '' ), 'mail_2' => array(), 'messages' => array() ); return $c; }
	public function prop( $k ) { return $this->props[ $k ] ?? null; }
	public function set_properties( $p ) { $this->props = array_merge( $this->props, $p ); }
	public function save() { if ( ! $this->id ) { $this->id = 100 + count( self::$alle ); self::$neu++; } self::$alle[ $this->id ] = $this; return $this->id; }
	public function id() { return $this->id; }
	public function hash() { return 'h' . $this->id; }
}
class WPCF7_Submission { public static $daten = array(); public static function get_instance() { return new self(); } public function get_posted_data() { return self::$daten; } }
function wpcf7_contact_form( $id ) { return WPCF7_ContactForm::$alle[ $id ] ?? null; }
$GLOBALS['__tags'] = array();
function wpcf7_add_form_tag( $t, $cb ) { $GLOBALS['__tags'][ $t ] = $cb; }
function register_activation_hook( $f, $cb ) {}
require $plugin_dir . '/inc/formulare.php';
foreach ( $GLOBALS['__actions']['wpcf7_init'] ?? array() as $cb ) { $cb(); }

/* ---- 3) Anlage: vier Formulare, zweiter Lauf legt nichts an -------------- */
$ids = edi_formulare_anlegen();
$neu1 = WPCF7_ContactForm::$neu;
edi_formulare_anlegen();
if ( 4 !== count( $ids ) || 4 !== $neu1 || 4 !== WPCF7_ContactForm::$neu ) {
	fail( "Anlage: $neu1 Formulare beim ersten, " . ( WPCF7_ContactForm::$neu - $neu1 ) . ' beim zweiten Lauf (erwartet 4 / 0)' );
} else {
	ok( 'Anlage: Kontakt, Anmeldung, Schnellanmeldung, Bewerbung – zweiter Lauf legt nichts doppelt an' );
}

/* ---- 4) Formulartexte: Grammatik, Einwilligungen, keine Adressen --------- */
// Muster aus WPCF7_FormTagsManager::tag_regex() (Contact Form 7, includes/form-tags-manager.php)
$known = array( 'text', 'text*', 'email', 'email*', 'tel', 'tel*', 'select', 'select*', 'textarea', 'textarea*', 'acceptance', 'hidden', 'file', 'submit', 'edi_honeypot' );
$re = '/(\[?)\[(' . implode( '|', array_map( function ( $t ) { return preg_quote( $t, '/' ); }, $known ) ) . ')(?:[\r\n\t ](.*?))?(?:[\r\n\t ](\/))?\](?:([^[]*?)\[\/\2\])?(\]?)/s';
$bad = array();
foreach ( WPCF7_ContactForm::$alle as $cf ) {
	$form = $cf->prop( 'form' ); $t = $cf->prop( 'title' );
	$rest = preg_replace( $re, '', $form );
	if ( preg_match( '/\[[a-z_*]+/', $rest, $m ) ) { $bad[] = "$t: unbekannter Tag {$m[0]}"; }
	foreach ( array( '[edi_honeypot]', '[acceptance datenschutz]', '[acceptance alter]', 'Art. 8 DSGVO', '[submit' ) as $need ) {
		if ( false === strpos( $form, $need ) ) { $bad[] = "$t: „{$need}“ fehlt"; }
	}
	if ( preg_match( '/[a-z0-9._-]+@[a-z0-9.-]+/i', $form . $cf->prop( 'mail' )['body'] . $cf->prop( 'mail' )['recipient'] ) ) { $bad[] = "$t: E-Mail-Adresse im Formular/Mailtext"; }
}
$bew = wpcf7_contact_form( $ids['bewerbung'] );
if ( false === strpos( $bew->prop( 'form' ), 'limit:10mb filetypes:pdf' ) || '[bewerbung]' !== $bew->prop( 'mail' )['attachments'] ) { $bad[] = 'Bewerbung: PDF-Upload bis 10 MB oder Anhang fehlt'; }
foreach ( array( 'stelle', 'stelle_id' ) as $h ) { if ( false === strpos( $bew->prop( 'form' ), "[hidden $h default:shortcode_attr]" ) ) { $bad[] = "Bewerbung: verstecktes Feld $h fehlt"; } }
if ( false === strpos( wpcf7_contact_form( $ids['schnellanmeldung'] )->prop( 'form' ), '[hidden standort default:shortcode_attr]' ) ) { $bad[] = 'Schnellanmeldung: Standort nicht aus dem Shortcode'; }
foreach ( $bad as $b ) { fail( $b ); }
if ( ! $bad ) { ok( 'Formulartexte: nur gültige CF7-Tags, Honeypot und beide Einwilligungen überall, keine Adresse, Bewerbung PDF ≤ 10 MB mit Stelle' ); }

/* ---- 5) Listen live aus Stammdaten / PDF ------------------------------- */
$orte = apply_filters( 'wpcf7_form_tag_data_option', null, array( 'edi_standorte' ), array() );
$kl   = apply_filters( 'wpcf7_form_tag_data_option', null, array( 'edi_klassen' ), array() );
if ( array( 'Duisburg', 'Essen', 'Gelsenkirchen' ) !== $orte || 14 !== count( $kl ) || ! in_array( 'Klasse BF17', $kl, true ) ) {
	fail( 'data:-Listen: Standorte ' . json_encode( $orte ) . ', ' . count( (array) $kl ) . ' Klassen (erwartet 3 / 14)' );
} else {
	ok( 'Standort- und Klassenliste kommen live aus Stammdaten und PDF F3 (3 Standorte, 14 Klassen)' );
}
$atts = apply_filters( 'shortcode_atts_wpcf7', array(), array(), array( 'standort' => 'Essen', 'stelle' => 'Fahrlehrer (m/w/d)', 'fremd' => 'x' ) );
if ( ( $atts['standort'] ?? '' ) !== 'Essen' || ! isset( $atts['stelle'] ) || isset( $atts['fremd'] ) ) { fail( 'shortcode_atts_wpcf7: Standort/Stelle nicht oder Fremdes durchgereicht' ); }

/* ---- 6) Empfänger ------------------------------------------------------ */
$send = function ( $id, $standort ) {
	WPCF7_Submission::$daten = array( 'standort' => $standort );
	$cf = wpcf7_contact_form( $id ); $abort = false;
	foreach ( $GLOBALS['__actions']['wpcf7_before_send_mail'] as $cb ) { $cb( $cf, $abort, new WPCF7_Submission() ); }
	return $cf->prop( 'mail' )['recipient'];
};
$r = array( $send( $ids['schnellanmeldung'], 'Essen' ), $send( $ids['kontakt'], array( 'Gelsenkirchen' ) ), $send( $ids['anmeldung'], 'Bochum' ), $send( $ids['kontakt'], '' ) );
$fremd = WPCF7_ContactForm::get_template( array( 'title' => 'fremd' ) ); $fremd->save();
$r[] = $send( $fremd->id(), 'Essen' );
$want = array( 'essen@fahrschule-edi.de', 'gelsenkirchen@fahrschule-edi.de', 'info@fahrschule-edi.de', 'info@fahrschule-edi.de', '[_site_admin_email]' );
if ( $r !== $want ) { fail( 'Empfänger: ' . json_encode( $r ) . ' statt ' . json_encode( $want ) ); }
else { ok( 'Empfänger aus den Stammdaten: Standort → Standortadresse, unbekannt/leer → info@, fremde Formulare unberührt' ); }

/* ---- 7) Spamschutz ----------------------------------------------------- */
$hp = $GLOBALS['__tags']['edi_honeypot']();
$_POST = array(); $clean = apply_filters( 'wpcf7_spam', false );
$_POST = array( EDI_HONEYPOT => 'http://spam' ); $dirty = apply_filters( 'wpcf7_spam', false );
$_POST = array();
if ( $clean || ! $dirty || false === strpos( $hp, 'tabindex="-1"' ) || false === strpos( $hp, 'aria-hidden="true"' ) ) { fail( 'Honeypot: Spamfilter oder Feld (tabindex/aria-hidden) falsch' ); }
else { ok( 'Spamschutz ohne Drittanbieter: Honeypot-Feld unsichtbar und nicht fokussierbar, gefüllt = Spam' ); }

/* ---- 8) Theme mit Plugin: CF7-Shortcode, Vorschau ---------------------- */
$GLOBALS['__sc'] = array();
$h = fs_formular( 'schnellanmeldung', array( 'standort' => 'Essen' ) );
$sc = end( $GLOBALS['__sc'] );
if ( $sc !== '[contact-form-7 id="h' . $ids['schnellanmeldung'] . '" standort="Essen"]' ) { fail( "Theme: Shortcode „{$sc}“" ); }
if ( preg_match( '/@fahrschule-edi\.de/', $h ) ) { fail( 'Theme: Adresse im Formular-Markup' ); }
fs_formular( 'kontakt', array(), '[contact-form-7 id="eigen"]' );
if ( '[contact-form-7 id="eigen"]' !== end( $GLOBALS['__sc'] ) ) { fail( 'Theme: eigener Shortcode aus dem Block wird nicht verwendet' ); }
define( 'REST_REQUEST', true );
$v = fs_formular( 'bewerbung' );
if ( false !== strpos( $v, '<form' ) || false === strpos( $v, 'disabled' ) || false === strpos( $v, 'Art. 8 DSGVO' ) || false === strpos( $v, 'PDF' ) ) { fail( 'Editor-Vorschau: kein statisches, deaktiviertes Formular' ); }
if ( ! array_filter( $fails, function ( $f ) { return 0 === strpos( $f, 'Theme' ) || 0 === strpos( $f, 'Editor' ); } ) ) {
	ok( 'Theme: CF7-Formular per Shortcode mit Standort, eigener Shortcode möglich, Editor zeigt deaktivierte Vorschau' );
}

/* ---- 9) Stellen (WP Job Manager) -------------------------------------- */
// a) ohne WP Job Manager: Leer-Text + Initiativ-Knopf, kein leerer Abschnitt
$h = fs_render_jobs( array() );
if ( false === strpos( $h, 'Aktuell ist keine Stelle ausgeschrieben' ) || false === strpos( $h, 'Initiativ bewerben' ) ) { fail( 'Stellen: ohne Plugin fehlt Leer-Text oder Initiativ-Knopf' ); }
// b) mit WP Job Manager und zwei Stellen
$GLOBALS['__post_types']['job_listing'] = true; $GLOBALS['__taxonomies']['job_listing_type'] = true; $GLOBALS['__taxonomies']['job_listing_category'] = true;
$GLOBALS['__jobs'] = array(
	new WP_Post( array( 'ID' => 501, 'post_type' => 'job_listing', 'post_name' => 'fahrlehrer-essen', 'post_title' => 'Fahrlehrer (m/w/d) Klasse B', 'post_content' => "Wir suchen Verstärkung.\n\nVollzeit oder Teilzeit." ) ),
	new WP_Post( array( 'ID' => 502, 'post_type' => 'job_listing', 'post_name' => 'buero-duisburg', 'post_title' => 'Bürokraft (m/w/d)' ) ),
);
$GLOBALS['__meta'][501]['_job_location'] = 'Essen';
$GLOBALS['__terms'][501]['job_listing_type'] = array( (object) array( 'name' => 'Vollzeit' ) );
$h = fs_render_jobs( array( 'kategorie' => 'fahrlehrer', 'anzahl' => '5' ) );
$q = $GLOBALS['__last_query'];
$filled = json_encode( $q['meta_query'] ?? array() );
if ( 2 !== substr_count( $h, 'class="fs-card fs-job"' ) || false === strpos( $h, 'Fahrlehrer (m/w/d) Klasse B' ) || false === strpos( $h, '/job/fahrlehrer-essen/' ) || false === strpos( $h, 'Essen' ) || false === strpos( $h, 'Vollzeit' ) ) {
	fail( 'Stellen: Liste zeigt Stellen nicht mit Titel, eigener URL, Ort und Art' );
} elseif ( 5 !== $q['posts_per_page'] || 'fahrlehrer' !== ( $q['tax_query'][0]['terms'] ?? '' ) || false === strpos( $filled, '_filled' ) || false !== strpos( $h, 'keine Stelle ausgeschrieben' ) ) {
	fail( 'Stellen: Abfrage ohne Anzahl, Kategorie-Filter oder Ausschluss besetzter Stellen' );
} else {
	ok( 'Stellen: Leer-Text + Initiativ-Knopf ohne Stelle; mit Stellen Titel, eigene URL, Ort, Art; Kategorie-Filter und besetzte Stellen ausgeschlossen' );
}
// c) Einzelansicht: Beschreibung, Bewerbungsformular mit Stelle - ohne Editor-Kontext
// (REST_REQUEST ist oben schon gesetzt, daher direkt über fs_formular prüfen)
$GLOBALS['__sc'] = array();
$GLOBALS['__queried'] = $GLOBALS['__jobs'][0];
$tmp = fs_stelle_eckdaten( $GLOBALS['__jobs'][0] );
if ( ( $tmp['map-pin'] ?? '' ) !== 'Essen' ) { fail( 'Stelle: Eckdaten ohne Ort' ); }
// d) Plugin: Kategorien einmalig, Kategorie-Option an
$GLOBALS['__terms_db'] = array();
if ( ! function_exists( 'term_exists' ) ) { function term_exists( $s, $t ) { return isset( $GLOBALS['__terms_db'][ $t ][ $s ] ); } }
if ( ! function_exists( 'wp_insert_term' ) ) { function wp_insert_term( $n, $t, $a ) { $GLOBALS['__terms_db'][ $t ][ $a['slug'] ] = $n; $GLOBALS['__ins'] = ( $GLOBALS['__ins'] ?? 0 ) + 1; return array(); } }
require $plugin_dir . '/inc/stellen.php';
edi_stellen_einrichten(); edi_stellen_einrichten();
$kats = $GLOBALS['__terms_db']['job_listing_category'] ?? array();
if ( 3 !== ( $GLOBALS['__ins'] ?? 0 ) || ! in_array( 'Büro & Ansprechpartner', $kats, true ) || 1 !== get_option( 'job_manager_enable_categories' ) ) {
	fail( 'Stellen-Plugin: Kategorien nicht genau einmal angelegt oder Kategorie-Option aus' );
} else {
	ok( 'Stellen-Plugin: Fahrlehrer, Büro & Ansprechpartner, Quereinstieg & Ausbildung einmalig angelegt, Kategorien eingeschaltet' );
}

echo "\n" . ( $fails ? 'FORMULARE: ' . count( $fails ) . " Fehler\n" : "FORMULARE: bestanden\n" );
exit( $fails ? 1 : 0 );
