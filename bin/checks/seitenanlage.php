<?php
/**
 * Seitenanlage bei der Theme-Aktivierung (after_switch_theme) - zweimal.
 *
 * Gegen eine Seiten-Datenbank im Stub (get_page_by_path / wp_insert_post
 * mit Zustand) wird fahrschule_create_pages() zweimal ausgeführt, wie bei
 * einer frischen Installation und einer erneuten Aktivierung. Erwartet:
 *
 *   1. Lauf: jede Registry-Seite angelegt, Elternseite vor Kind, mit Eltern-ID,
 *      Vorlage (_wp_page_template) und Inhalt; Startseite gesetzt;
 *      Hinweis listet alle Seiten - genau einmal.
 *   2. Lauf: nichts angelegt, nichts geändert, kein neuer Hinweis.
 *
 * Aufruf: php seitenanlage.php [<theme-dir>]
 */
error_reporting( E_ALL );
$GLOBALS['__theme']  = $argv[1] ?? dirname( __DIR__, 2 ) . '/fahrschule-edi';
$GLOBALS['__blocks'] = array();
require __DIR__ . '/wp-stub.php';
define( 'FAHRSCHULE_DIR', $GLOBALS['__theme'] ); define( 'FAHRSCHULE_URI', '/u' ); define( 'FAHRSCHULE_VERSION', '1' );
foreach ( array( 'helpers', 'pdf-texts', 'media', 'pages', 'seo', 'render-callbacks', 'blocks-config' ) as $f ) { require $GLOBALS['__theme'] . '/inc/' . $f . '.php'; }

$fails = array();
function fail( $m ) { global $fails; $fails[] = $m; echo "  FEHLER: $m\n"; }
function ok( $m ) { echo "  ok: $m\n"; }
set_error_handler( function ( $no, $str, $file, $line ) { fail( "PHP: $str (" . basename( $file ) . ":$line)" ); return true; } );

$GLOBALS['__db_on']    = true;
$GLOBALS['__db_pages'] = array();
$GLOBALS['__can']      = true;
$map = fahrschule_page_map();

// --- 1. Aktivierung ---------------------------------------------------------
$GLOBALS['__db_inserts'] = array();
fahrschule_create_pages();
$ins = $GLOBALS['__db_inserts'];
if ( count( $ins ) !== count( $map ) ) {
	fail( 'erste Aktivierung: ' . count( $ins ) . ' Seiten angelegt, erwartet ' . count( $map ) );
} else {
	ok( 'erste Aktivierung: alle ' . count( $map ) . ' Registry-Seiten angelegt' );
}

$bad = array();
foreach ( $map as $key => $p ) {
	$path = fahrschule_page_path( $key );
	$post = $GLOBALS['__db_pages'][ '' === $path ? 'startseite' : $path ] ?? null;
	if ( ! $post ) { $bad[] = "$key: nicht unter /$path/ angelegt"; continue; }
	if ( ! empty( $p['parent'] ) ) {
		$elt = $GLOBALS['__db_pages'][ fahrschule_page_path( $p['parent'] ) ] ?? null;
		if ( ! $elt || $post->post_parent !== $elt->ID ) { $bad[] = "$key: Elternseite fehlt"; }
		if ( array_search( fahrschule_page_path( $p['parent'] ), $ins, true ) > array_search( $path, $ins, true ) ) { $bad[] = "$key: vor seiner Elternseite angelegt"; }
	}
	$tpl  = $p['template'] ?? 'page';
	$meta = $GLOBALS['__meta'][ $post->ID ]['_wp_page_template'] ?? '';
	if ( ! in_array( $tpl, array( 'page', 'front-page' ), true ) && $meta !== $tpl ) { $bad[] = "$key: Vorlage „{$meta}“ statt „{$tpl}“"; }
	if ( '' === trim( $post->post_content ) ) { $bad[] = "$key: ohne Inhalt"; }
	// Ohne eigenen Stapel kommt der Inhalt aus dem Startmuster der Vorlage.
	if ( empty( $p['blocks'] ) ) {
		$m = fahrschule_startmuster_fuer( $tpl );
		if ( '' === $m || $post->post_content !== fahrschule_startmuster_markup( $m ) ) { $bad[] = "$key: Inhalt nicht aus dem Startmuster der Vorlage $tpl"; }
	}
}
if ( $bad ) { foreach ( $bad as $b ) { fail( $b ); } }
else { ok( 'jede Seite mit Elternseite (Eltern zuerst angelegt), Vorlage und Inhalt; ohne eigenen Stapel aus dem Startmuster' ); }

$home = $GLOBALS['__db_pages']['startseite'] ?? null;
if ( 'page' !== get_option( 'show_on_front' ) || ! $home || get_option( 'page_on_front' ) !== $home->ID ) {
	fail( 'Startseite nicht gesetzt (show_on_front/page_on_front)' );
} else {
	ok( 'page_on_front = Startseite (ID ' . $home->ID . ')' );
}

ob_start(); fahrschule_neue_seiten_hinweis(); $h1 = ob_get_clean();
ob_start(); fahrschule_neue_seiten_hinweis(); $h2 = ob_get_clean();
if ( substr_count( $h1, '<li>' ) !== count( $map ) || '' !== $h2 ) {
	fail( 'Admin-Hinweis: ' . substr_count( $h1, '<li>' ) . ' Seiten gelistet (erwartet ' . count( $map ) . '), zweiter Aufruf ' . ( '' === $h2 ? 'leer' : 'NICHT leer' ) );
} else {
	ok( 'Admin-Hinweis listet die ' . count( $map ) . ' angelegten Seiten, genau einmal' );
}

// --- 2. Aktivierung ---------------------------------------------------------
$vorher                  = serialize( $GLOBALS['__db_pages'] );
$GLOBALS['__db_inserts'] = array();
fahrschule_create_pages();
ob_start(); fahrschule_neue_seiten_hinweis(); $h3 = ob_get_clean();
if ( $GLOBALS['__db_inserts'] || serialize( $GLOBALS['__db_pages'] ) !== $vorher || '' !== $h3 ) {
	fail( 'zweite Aktivierung: ' . count( $GLOBALS['__db_inserts'] ) . ' Seiten angelegt, Bestand ' . ( serialize( $GLOBALS['__db_pages'] ) === $vorher ? 'gleich' : 'GEÄNDERT' ) . ', Hinweis ' . ( '' === $h3 ? 'keiner' : 'erneut' ) );
} else {
	ok( 'zweite Aktivierung: nichts angelegt, nichts geändert, kein Hinweis' );
}

echo "\n";
if ( $fails ) { echo 'SEITENANLAGE: ' . count( $fails ) . " Fehler\n"; exit( 1 ); }
echo "SEITENANLAGE: bestanden\n";
