<?php
/**
 * Startseiten-Hero: Video oder Standbild, nie nur ein dunkler Schleier.
 *
 *   1. Video + Standbild: <video muted playsinline loop preload="metadata">
 *      mit poster, ohne autoplay; Standbild als Hintergrund der Sektion;
 *      Abdunkler zwischen Video und Text.
 *   2. nur Standbild: kein <video>, Standbild als Hintergrund.
 *   3. weder noch: kein <video>, kein Abdunkler (Vollpink, Text weiß 4,87:1).
 *   4. Editor-Vorschau (REST): kein <video>, nur das Standbild; ohne
 *      Medien ein Hinweis.
 *
 * Fall 4 läuft zuletzt - REST_REQUEST lässt sich nicht zurücknehmen.
 * Aufruf: php hero.php [<theme-dir>]
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

$cfg  = fahrschule_blocks_config();
$hero = function ( $a ) use ( $cfg ) { return call_user_func( $cfg['fahrschule/video-hero']['render'], $a ); };
$V = '/wp-content/uploads/edi-video-hero.mp4';
$B = '/wp-content/uploads/edi-video-hero-standbild.jpg';

// 1) Video + Standbild
$h = $hero( array( 'video_url' => $V, 'standbild' => $B ) );
$e = array();
if ( ! preg_match( '#<video\b([^>]*)>#', $h, $v ) ) { $e[] = 'kein <video>'; }
else {
	foreach ( array( 'muted', 'playsinline', 'loop', 'preload="metadata"', 'poster="' . $B . '"', 'data-fs-hero-video' ) as $need ) {
		if ( false === strpos( $v[1], $need ) ) { $e[] = "<video> ohne $need"; }
	}
	if ( false !== strpos( $v[1], 'autoplay' ) ) { $e[] = '<video> mit autoplay (startet trotz reduzierter Bewegung)'; }
}
if ( false === strpos( $h, 'background-image:url(' . $B . ')' ) ) { $e[] = 'Standbild nicht als Hintergrund'; }
if ( false === strpos( $h, 'fs-vhero__overlay' ) ) { $e[] = 'kein Abdunkler zwischen Video und Text'; }
if ( false === strpos( $h, 'src="' . $V . '"' ) ) { $e[] = 'MP4-Quelle fehlt'; }
$e ? fail( 'Video + Standbild: ' . implode( ', ', $e ) ) : ok( 'Video + Standbild: muted playsinline loop preload=metadata, Poster, kein autoplay, Hintergrundbild, Abdunkler' );

// 2) nur Standbild
$h = $hero( array( 'video_url' => '', 'standbild' => $B ) );
( false === strpos( $h, '<video' ) && false !== strpos( $h, 'background-image:url(' . $B . ')' ) )
	? ok( 'nur Standbild: kein <video>, Standbild als Hintergrund' )
	: fail( 'nur Standbild: ' . ( false !== strpos( $h, '<video' ) ? '<video> trotz fehlendem Video' : 'Hintergrundbild fehlt' ) );

// 3) weder noch
$h = $hero( array( 'video_url' => '', 'standbild' => '' ) );
( false === strpos( $h, '<video' ) && false === strpos( $h, 'fs-vhero__overlay' ) && false !== strpos( $h, 'fs-vhero--ohne-medien' ) )
	? ok( 'ohne Medien: Vollpink ohne Schleier, kein leeres <video>' )
	: fail( 'ohne Medien: Video-Tag oder dunkler Schleier ohne Bild' );

// 4) Editor-Vorschau
define( 'REST_REQUEST', true );
$h  = $hero( array( 'video_url' => $V, 'standbild' => $B ) );
$h0 = $hero( array( 'video_url' => '', 'standbild' => '' ) );
( false === strpos( $h, '<video' ) && false !== strpos( $h, 'background-image:url(' . $B . ')' ) && false !== strpos( $h0, 'fs-editor-hint' ) )
	? ok( 'Editor-Vorschau: Standbild statt Video; ohne Medien ein Hinweis' )
	: fail( 'Editor-Vorschau: ' . ( false !== strpos( $h, '<video' ) ? 'Video im Editor' : 'Standbild oder Hinweis fehlt' ) );

// CSS: reduzierte Bewegung blendet das Video aus.
$css = file_get_contents( $GLOBALS['__theme'] . '/assets/css/main.css' );
preg_match_all( '#@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{(.*?)\n\}#s', $css, $mm );
( preg_match( '#\.fs-vhero__media\s*\{\s*display:\s*none#', implode( "\n", $mm[1] ) ) )
	? ok( 'reduzierte Bewegung: CSS blendet das Video aus, das Standbild bleibt' )
	: fail( 'reduzierte Bewegung: kein .fs-vhero__media { display: none } im @media (prefers-reduced-motion: reduce)' );

echo "\n";
if ( $fails ) { echo 'HERO: ' . count( $fails ) . " Fehler\n"; exit( 1 ); }
echo "HERO: bestanden\n";
