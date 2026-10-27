<?php
/**
 * Wiederholer mit Spaltentypen und der Block „Social-Raster“.
 *
 *   1. Spaltentypen: jedes Wiederholfeld parst; eine Zeichenketten-Spalte
 *      wird type text; erlaubt sind nur text, image, video. Bestehende
 *      Wiederholer (alle außer dem Raster) sind reine Textspalten - ihre
 *      Aufrufe wurden nicht angefasst.
 *   2. (kein Netzabruf - refs.py und social.php)
 *   3. Das Raster bei 0, 1, 3, 6 und 7 Zeilen.
 *   4. Markup je Kachel: preload, playsinline, aria-label, kein <a>.
 *   5. Alte Werte: Slug, Attachment-ID und URL lösen auf.
 *   6. (Flächenfolge - flaechen.py, über die Prüfseite _raster)
 *
 * Aufruf: php raster.php [<theme-dir>]
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

$cfg = fahrschule_blocks_config();

// --- 1. Spaltentypen ---------------------------------------------------------
$TYPEN = array( 'text', 'image', 'video' );
$e = array(); $n = 0; $typisiert = array();
foreach ( $cfg as $name => $c ) {
	foreach ( $c['fields'] ?? array() as $key => $f ) {
		if ( 'repeater' !== ( $f['control'] ?? '' ) ) { continue; }
		$n++;
		foreach ( $f['columns'] as $i => $col ) {
			if ( ! is_array( $col ) || ! isset( $col['label'], $col['type'] ) ) { $e[] = "$name.$key Spalte $i: nicht normalisiert"; continue; }
			if ( '' === trim( (string) $col['label'] ) ) { $e[] = "$name.$key Spalte $i: ohne Beschriftung"; }
			if ( ! in_array( $col['type'], $TYPEN, true ) ) { $e[] = "$name.$key Spalte $i: Typ „{$col['type']}“ (erlaubt: text, image, video)"; }
			if ( 'text' !== $col['type'] ) { $typisiert[ "$name.$key" ] = true; }
		}
		// Zeilen der Vorgabe passen zur Spaltenzahl.
		foreach ( (array) $f['default'] as $z => $row ) {
			if ( is_array( $row ) && count( $row ) > count( $f['columns'] ) ) { $e[] = "$name.$key Vorgabezeile $z: mehr Werte als Spalten"; }
		}
	}
}
$fremd = array_diff( array_keys( $typisiert ), array( 'fahrschule/social-raster.items' ) );
if ( $fremd ) { $e[] = 'Medienspalten außerhalb des Social-Rasters (bestehende Wiederholer bleiben Text, Umstellung einzeln): ' . implode( ', ', $fremd ); }
$e ? array_map( 'fail', $e ) : ok( "Spaltentypen: $n Wiederholfelder parsen, Zeichenketten-Spalten sind text, Medienspalten nur im Social-Raster" );

// --- Raster: Hilfen ---------------------------------------------------------
$R = function ( $items, $extra = array() ) use ( $cfg ) {
	if ( ! isset( $cfg['fahrschule/social-raster'] ) ) { return null; }
	$d = array();
	foreach ( $cfg['fahrschule/social-raster']['fields'] as $k => $f ) { $d[ $k ] = $f['default']; }
	return (string) call_user_func( $cfg['fahrschule/social-raster']['render'], array_merge( $d, array( 'items' => $items ), $extra ) );
};
$zeilen  = function ( $n, $start = 1 ) { $o = array(); for ( $i = $start; $i < $start + $n; $i++ ) { $o[] = array( (string) ( 900 + $i ), (string) ( 800 + $i ), "Titel $i" ); } return $o; };
$kacheln = function ( $h ) { preg_match_all( '#<li class="fs-raster__kachel">(.*?)</li>#s', $h, $m ); return $m[1]; };

if ( null === $R( array() ) ) {
	fail( 'Block fahrschule/social-raster fehlt in der Blockkonfiguration' );
} else {
	// --- 3. 0, 1, 3, 6, 7 Zeilen ---------------------------------------------
	$e = array();
	if ( '' !== trim( $R( array() ) ) ) { $e[] = '0 Zeilen: Sektion steht trotzdem da'; }
	if ( '' !== trim( $R( array_fill( 0, 6, array( '', '', '' ) ) ) ) ) { $e[] = 'Vorgabe (sechs leere Zeilen): Sektion steht trotzdem da'; }
	foreach ( array( 1 => 1, 3 => 3, 6 => 6, 7 => 6 ) as $n => $soll ) {
		$h = $R( $zeilen( $n ) );
		if ( count( $kacheln( $h ) ) !== $soll ) { $e[] = "$n Zeilen: " . count( $kacheln( $h ) ) . " Kacheln statt $soll"; }
		if ( 7 === $n && false !== strpos( $h, 'Titel 7' ) ) { $e[] = '7 Zeilen: die siebte erscheint'; }
	}
	// Zeilen ohne Video fallen weg, die übrigen rücken auf.
	$gemischt = array( array( '', '801', 'Ohne Video' ), array( '902', '802', 'Zwei' ), array( '', '', '' ), array( '904', '', 'Vier' ) );
	$h = $R( $gemischt );
	if ( 2 !== count( $kacheln( $h ) ) || false !== strpos( $h, 'Ohne Video' ) ) { $e[] = 'Zeilen ohne Video fallen nicht weg'; }
	$e ? array_map( 'fail', $e ) : ok( 'Raster: 0 Zeilen = keine Sektion, 1/3/6 Zeilen = 1/3/6 Kacheln, die siebte wird ignoriert, Zeilen ohne Video fallen weg' );

	// --- 4. Markup je Kachel -------------------------------------------------
	$e = array();
	$h = $R( array( array( '901', '801', 'Mit Standbild' ), array( '902', '', 'Ohne Standbild' ), array( '903', '803', '' ) ) );
	foreach ( $kacheln( $h ) as $i => $k ) {
		$z = $i + 1;
		if ( ! preg_match( '#<video\b([^>]*)>#', $k, $v ) ) { $e[] = "Kachel $z: kein <video>"; continue; }
		$v = $v[1];
		if ( ! preg_match( '#\bpreload="(none|metadata)"#', $v, $p ) ) { $e[] = "Kachel $z: preload fehlt"; }
		elseif ( ( 2 === $z ? 'metadata' : 'none' ) !== $p[1] ) { $e[] = "Kachel $z: preload=\"{$p[1]}\" (mit Standbild none, ohne metadata)"; }
		foreach ( array( 'playsinline', 'controls' ) as $need ) { if ( ! preg_match( '#\b' . $need . '\b#', $v ) ) { $e[] = "Kachel $z: ohne $need"; } }
		if ( ! preg_match( '#aria-label="([^"]+)"#', $v ) ) { $e[] = "Kachel $z: aria-label leer"; }
		if ( preg_match( '#\b(autoplay|loop)\b#', $v ) ) { $e[] = "Kachel $z: autoplay oder loop"; }
		if ( preg_match( '#<a\b#', $k ) ) { $e[] = "Kachel $z: enthält einen Link"; }
		if ( 2 !== $z && ! preg_match( '#\bposter="[^"]+"#', $v ) ) { $e[] = "Kachel $z: Standbild nicht als poster"; }
		if ( 2 === $z && preg_match( '#\bposter=#', $v ) ) { $e[] = 'Kachel ohne Standbild mit leerem poster'; }
	}
	if ( preg_match( '#<a\b[^>]*>\s*<li|<a\b[^>]*>\s*<figure|<a\b[^>]*>\s*<video#', $h ) ) { $e[] = 'Kachel in ein <a> gewickelt'; }
	if ( ! preg_match( '#aria-label="Video 3"#', $h ) ) { $e[] = 'Kachel ohne Titel: aria-label nicht „Video 3“'; }
	$e ? array_map( 'fail', $e ) : ok( 'Markup: <video controls playsinline>, preload none (mit Standbild) bzw. metadata (ohne), aria-label je Kachel, kein autoplay/loop, Standbild als poster, kein <a>' );

	// --- 5. Alte Werte: Slug, ID, URL ----------------------------------------
	$GLOBALS['__db_pages']['edi-video-probe'] = new WP_Post( array( 'ID' => 950, 'post_name' => 'edi-video-probe', 'post_type' => 'attachment' ) );
	$h = $R( array(
		array( 'edi-video-probe', 'edi-logo', 'Slug' ),
		array( '951', '952', 'ID' ),
		array( 'https://fahrschule-edi.de/wp-content/uploads/clip.mp4', 'https://fahrschule-edi.de/wp-content/uploads/clip.jpg', 'URL' ),
	) );
	$e = array();
	foreach ( array( 'stub-950.jpg' => 'Slug (Mediathek)', 'assets/images/edi-logo.webp' => 'Slug (Theme)', 'stub-951.jpg' => 'ID (Video)', 'stub-952.jpg' => 'ID (Standbild)', 'uploads/clip.mp4' => 'URL (Video)', 'uploads/clip.jpg' => 'URL (Standbild)' ) as $need => $was ) {
		if ( false === strpos( $h, $need ) ) { $e[] = "$was löst nicht auf"; }
	}
	if ( 3 !== count( $kacheln( $h ) ) ) { $e[] = count( $kacheln( $h ) ) . ' statt 3 Kacheln'; }
	$e ? array_map( 'fail', $e ) : ok( 'alte Werte: Slug (Mediathek und Theme), Attachment-ID und URL lösen auf - nichts wird umgeschrieben' );

	// --- Editor: Hinweise statt stiller Lücken ------------------------------
	$GLOBALS['__att_meta'][901] = array( 'filesize' => 12 * 1048576 );
	define( 'REST_REQUEST', true );
	$h = $R( array_merge( array( array( '901', '801', 'Groß' ), array( '', '802', 'Ohne Video' ) ), $zeilen( 6, 10 ) ) );
	$e = array();
	if ( false === strpos( $h, 'Zeile 2' ) || false === strpos( $h, 'ohne Video' ) ) { $e[] = 'Zeile ohne Video im Editor nicht benannt'; }
	if ( false === strpos( $h, '12,0 MB' ) ) { $e[] = 'Video über 5 MB ohne Größenhinweis'; }
	if ( false === strpos( $h, 'Zeile 8' ) ) { $e[] = 'überzählige Zeile (die siebte Kachel) im Editor nicht benannt'; }
	if ( 6 !== count( $kacheln( $h ) ) ) { $e[] = 'Editor zeigt nicht dieselben Kacheln wie das Frontend'; }
	$e ? array_map( 'fail', $e ) : ok( 'Editor: Zeile ohne Video benannt, überzählige Zeile benannt, Video über 5 MB mit tatsächlicher Größe - blockiert nichts' );

	// --- 6. Fläche: Feld vorhanden, Abschnitt trägt data-flaeche ------------
	$f6 = $cfg['fahrschule/social-raster']['fields']['flaeche'] ?? null;
	$h6 = $R( $zeilen( 2 ), array( 'flaeche' => 'hellpink' ) );
	( $f6 && 'vollpink' === $f6['default'] && preg_match( '#<section[^>]*data-flaeche="hellpink"#', $h6 ) )
		? ok( 'Fläche: Feld „Fläche“ (Vorgabe Vollpink) am Block, der Abschnitt trägt data-flaeche - die Flächenfolge-Prüfung kennt ihn' )
		: fail( 'Social-Raster ohne Feld „Fläche“ oder ohne data-flaeche am Abschnitt' );

	// Kontrast: Der Titel steht auf der Fläche des Abschnitts, nicht auf dem Bild.
	$css = file_get_contents( $GLOBALS['__theme'] . '/assets/css/main.css' );
	( preg_match( '#\.fs-raster__titel\s*\{[^}]*color:\s*var\(--c-text\)#', $css ) && ! preg_match( '#\.fs-raster__titel\s*\{[^}]*position:\s*absolute#', $css ) )
		? ok( 'Titel unter dem Bild auf der Abschnittsfläche (var(--c-text)) - Kontrast wie Fließtext, von kontrast.py je Fläche gerechnet' )
		: fail( 'Titel nicht auf der Abschnittsfläche bzw. nicht aus --c-text' );
}

echo "\n";
if ( $fails ) { echo 'RASTER: ' . count( $fails ) . " Fehler\n"; exit( 1 ); }
echo "RASTER: bestanden\n";
