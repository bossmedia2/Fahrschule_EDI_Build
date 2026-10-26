<?php
/**
 * Social-Feed: Link einfügen, fertig - der Abruf (fs_social_abruf) und
 * seine Folgen, gegen eine nachgebaute TikTok-Antwort.
 *
 * Kein echter Abruf: wp_remote_get, download_url und media_handle_sideload
 * sind im Stub nachgebaut und notieren jeden Aufruf. Die Antwort folgt dem
 * dokumentierten oEmbed-Format von TikTok (title, thumbnail_url, …).
 *
 * Aufruf: php social.php [<theme-dir>]
 */
error_reporting( E_ALL );
$GLOBALS['__theme']  = $argv[1] ?? dirname( __DIR__, 2 ) . '/fahrschule-edi';
$GLOBALS['__blocks'] = array();
require __DIR__ . '/wp-stub.php';
require __DIR__ . '/fse.php';
if ( ! function_exists( 'parse_blocks' ) ) { function parse_blocks( $c ) { return fs_fse_parse( $c ); } }
define( 'FAHRSCHULE_DIR', $GLOBALS['__theme'] ); define( 'FAHRSCHULE_URI', '/u' ); define( 'FAHRSCHULE_VERSION', '1' );
foreach ( array( 'helpers', 'pdf-texts', 'media', 'pages', 'seo', 'render-callbacks', 'blocks-config', 'social-abruf' ) as $f ) { require $GLOBALS['__theme'] . '/inc/' . $f . '.php'; }

$fails = array();
function fail( $m ) { global $fails; $fails[] = $m; echo "  FEHLER: $m\n"; }
function ok( $m ) { echo "  ok: $m\n"; }
set_error_handler( function ( $no, $str, $file, $line ) { fail( "PHP: $str (" . basename( $file ) . ":$line)" ); return true; } );

$cfg    = fahrschule_blocks_config();
$render = function ( $items ) use ( $cfg ) { return call_user_func( $cfg['fahrschule/instagram-slider']['render'], array( 'items' => $items ) ); };
$tiktok = function ( $code, $daten = array() ) {
	return function ( $u ) use ( $code, $daten ) { return array( 'response' => array( 'code' => $code ), 'body' => json_encode( $daten ) ); };
};
$reset = function () { $GLOBALS['__http_calls'] = array(); $GLOBALS['__downloads'] = array(); $GLOBALS['__sideloads'] = array(); $GLOBALS['__download_fail'] = false; };

// --- 1. Erfolg: Titel und Cover, Cover lokal ---------------------------------
$reset();
$GLOBALS['__http'] = $tiktok( 200, array( 'version' => '1.0', 'type' => 'video', 'title' => 'Erste Fahrstunde <b>auf der Autobahn</b>', 'author_name' => 'edi', 'thumbnail_url' => 'https://p16-sign.tiktokcdn-eu.com/obj/abc~tplv-photomode-image?x-expires=1700000000&x-signature=xyz' ) );
$z = fs_social_abruf( 'https://www.tiktok.com/@edi_fahrschule/photo/7311111111111111111?is_from_webapp=1&sender_device=pc#x' );
$e = array();
if ( 'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111' !== $z[1] ) { $e[] = "Link nicht normalisiert: {$z[1]}"; }
if ( false === strpos( $GLOBALS['__http_calls'][0] ?? '', rawurlencode( 'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111' ) ) || false !== strpos( $GLOBALS['__http_calls'][0], 'is_from_webapp' ) ) { $e[] = 'Anfrage nicht mit /video/ und ohne Tracking-Parameter'; }
if ( 'Erste Fahrstunde auf der Autobahn' !== $z[2] ) { $e[] = "Titel „{$z[2]}“"; }
if ( 'ok' !== $z[4] || ! preg_match( '/^\d+$/', $z[3] ) ) { $e[] = "Status/Bild-ID: {$z[4]} / {$z[3]}"; }
if ( 1 !== count( $GLOBALS['__sideloads'] ) || 'edi-tiktok-7311111111111111111.jpg' !== reset( $GLOBALS['__sideloads'] ) ) { $e[] = 'Cover nicht als edi-tiktok-<id>.jpg in die Mediathek übernommen'; }
if ( ( $GLOBALS['__meta'][ (int) $z[3] ]['_fs_social_quelle'] ?? '' ) !== $z[1] ) { $e[] = 'Quelle am Anhang nicht vermerkt'; }
$e ? array_map( 'fail', $e ) : ok( 'Link eingelesen: /photo/ -> /video/, Tracking-Parameter weg, Titel ohne HTML, Cover als Anhang edi-tiktok-<id>.jpg in der Mediathek' );
$erste_id = $z[3];

// --- 2. Erneutes Speichern: kein zweiter Abruf, kein zweites Bild -------------
$reset();
$z2 = fs_social_abruf( 'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111' );
( ! $GLOBALS['__http_calls'] && ! $GLOBALS['__sideloads'] && $z2[3] === $erste_id && 'Erste Fahrstunde auf der Autobahn' === $z2[2] )
	? ok( 'zweites Einlesen desselben Links: kein Netzabruf, derselbe Anhang' )
	: fail( 'zweites Einlesen ruft erneut ab oder legt ein zweites Bild an' );
$reset();
fs_social_abruf( 'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111', true );
( 1 === count( $GLOBALS['__http_calls'] ) ) ? ok( '„Beiträge aktualisieren“ (neu=true) ruft erneut ab' ) : fail( '„Beiträge aktualisieren“ ruft nicht erneut ab' );

// --- 3. Scheitern: Grund im Status, Marken-Kachel mit Link -------------------
$faelle = array(
	'privat/gelöscht (404)'     => array( $tiktok( 404 ), false, 'HTTP 404' ),
	'Rate-Limit (429)'          => array( $tiktok( 429 ), false, '429' ),
	'kein Netz'                 => array( null, false, 'keine Verbindung' ),
	'Vorschaubild nicht ladbar' => array( $tiktok( 200, array( 'title' => 'T', 'thumbnail_url' => 'https://p16.tiktokcdn.com/x.jpeg' ) ), true, 'Vorschaubild nicht ladbar' ),
);
$e = array();
foreach ( $faelle as $name => $f ) {
	$reset(); $GLOBALS['__http'] = $f[0]; $GLOBALS['__download_fail'] = $f[1];
	$z = fs_social_abruf( 'https://www.tiktok.com/@edi_fahrschule/video/7322222222222222222' );
	if ( 'ok' === $z[4] || false === strpos( $z[4], $f[2] ) ) { $e[] = "$name: Status „{$z[4]}“"; continue; }
	$h = $render( array( $z ) );
	if ( ! preg_match( '#<a class="fs-social__kachel fs-social__kachel--marke" href="https://www\.tiktok\.com/@edi_fahrschule/video/7322222222222222222" target="_blank" rel="noopener#', $h ) ) { $e[] = "$name: keine Marken-Kachel mit Link"; }
	if ( preg_match( '#<img[^>]*src=""#', $h ) ) { $e[] = "$name: leeres Bild"; }
}
$reset();
foreach ( array( 'https://www.instagram.com/p/abc/' => 'Instagram', 'https://example.com/video/1' => 'kein TikTok-Link' ) as $u => $erwartet ) {
	$z = fs_social_abruf( $u );
	if ( false === strpos( $z[4], $erwartet ) ) { $e[] = "$u: Status „{$z[4]}“"; }
}
if ( $GLOBALS['__http_calls'] ) { $e[] = 'Instagram/fremder Link löst einen Abruf aus'; }
$e ? array_map( 'fail', $e ) : ok( 'gescheitert (404, 429, kein Netz, Bild nicht ladbar), Instagram, fremder Link: Grund im Status, Marken-Kachel mit Link, nie ein leeres Bild' );

// --- 4. Editor-Hinweis nennt Link und Grund ----------------------------------
define( 'REST_REQUEST', true );
$h = $render( array( array( 'tiktok', 'https://www.tiktok.com/@edi_fahrschule/video/1', '', '', 'privat, gelöscht oder kein Video (HTTP 404)' ), array( 'tiktok', 'https://www.tiktok.com/@edi_fahrschule/video/2', '', '', '' ) ) );
( false !== strpos( $h, 'Link 1 (tiktok.com/@edi_fahrschule/video/1): privat, gelöscht oder kein Video (HTTP 404)' ) && false !== strpos( $h, 'Link 2: noch nicht eingelesen' ) )
	? ok( 'Editor nennt je Link, warum er nicht gelesen wurde bzw. dass er noch aussteht' )
	: fail( 'Editor-Hinweis nennt Link und Grund nicht' );

// --- 5. Beim Speichern: fehlende Zeilen werden nachgetragen ------------------
$reset();
$GLOBALS['__http'] = $tiktok( 200, array( 'title' => 'Beim Speichern', 'thumbnail_url' => 'https://p16.tiktokcdn.com/y.jpeg' ) );
$inhalt = '<!-- wp:fahrschule/instagram-slider {"items":[["tiktok","https://www.tiktok.com/@edi_fahrschule/video/7333333333333333333","","",""],["tiktok","https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111","Erste Fahrstunde auf der Autobahn","' . $erste_id . '","ok"]]} /-->';
$data   = fahrschule_social_beim_speichern( array( 'post_content' => $inhalt, 'post_type' => 'page' ) );
$neu    = parse_blocks( $data['post_content'] )[0]['attrs']['items'];
( 'Beim Speichern' === $neu[0][2] && 'ok' === $neu[0][4] && $neu[1][3] === $erste_id && 1 === count( $GLOBALS['__http_calls'] ) )
	? ok( 'beim Speichern: Zeile ohne Status eingelesen, eingelesene Zeile unberührt (genau ein Abruf)' )
	: fail( 'Speichern trägt fehlende Zeilen nicht nach oder ruft eingelesene erneut ab' );

// --- 6. Eine Funktion, kein Abruf beim Seitenaufruf --------------------------
$src = file_get_contents( $GLOBALS['__theme'] . '/inc/render-callbacks.php' );
( ! preg_match( '/wp_remote_|download_url|media_handle_sideload|media_sideload_image|oembed/i', $src ) )
	? ok( 'Render-Funktionen rufen nichts ab (kein wp_remote_*, kein Sideload, kein oEmbed)' )
	: fail( 'Netz- oder Mediathek-Aufruf in inc/render-callbacks.php' );

echo "\n";
if ( $fails ) { echo 'SOCIAL: ' . count( $fails ) . " Fehler\n"; exit( 1 ); }
echo "SOCIAL: bestanden\n";
