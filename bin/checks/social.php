<?php
/**
 * TikTok-Video im Embed Player: kein Netzabruf, Iframe nur mit Zustimmung,
 * Platzhalter sonst.
 *
 *   1. Kein Netzabruf überhaupt: Theme und Plugin enthalten kein
 *      wp_remote_*, kein Sideload, keinen oEmbed-Aufruf; das Rendern löst
 *      keinen Abruf aus (der Stub notiert jeden).
 *   2. Iframe ausschließlich mit Zustimmung (fs_consent=all oder fs_tiktok=1),
 *      mit title, loading=lazy, allowfullscreen und der Player-URL aus der
 *      Dokumentation.
 *   3. Sonst der Platzhalter: kein <iframe>, kein <script> im HTML, Knopf
 *      „Video laden“ als echtes <button>.
 *   Dazu: Post-ID aus /photo/- und Tracking-URLs, unlesbare URL = leer mit
 *   Editor-Hinweis, Editor zeigt die erkannte ID ohne Iframe, Cookie-Banner
 *   und Datenschutzerklärung nennen TikTok.
 *
 * Aufruf: php social.php [<theme-dir>]
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

$cfg    = fahrschule_blocks_config();
$block  = function ( $a ) use ( $cfg ) { return call_user_func( $cfg['fahrschule/instagram-slider']['render'], $a ); };
$sauber = 'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111';
$GLOBALS['__http_calls'] = array();

// --- 1. Kein Netzabruf überhaupt ---------------------------------------------
$treffer = array();
foreach ( array( $GLOBALS['__theme'], dirname( $GLOBALS['__theme'] ) . '/edi-integrationen' ) as $dir ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		if ( 'php' !== $file->getExtension() ) { continue; }
		foreach ( file( $file->getPathname() ) as $n => $zeile ) {
			if ( preg_match( '#^\s*(\*|//|/\*)#', $zeile ) ) { continue; }
			if ( preg_match( '/\b(wp_remote_\w+|wp_safe_remote_\w+|download_url|media_sideload_image|media_handle_sideload)\s*\(|oembed/i', $zeile, $m ) ) {
				$treffer[] = basename( $file->getPathname() ) . ':' . ( $n + 1 ) . ' ' . $m[0];
			}
		}
	}
}
$block( array( 'videoUrl' => $sauber ) );
$_COOKIE['fs_consent'] = 'all'; $block( array( 'videoUrl' => $sauber ) ); unset( $_COOKIE['fs_consent'] );
if ( $treffer ) { fail( 'Netzabruf im Code: ' . implode( ', ', $treffer ) ); }
elseif ( $GLOBALS['__http_calls'] ) { fail( 'Rendern löst einen Abruf aus: ' . implode( ', ', $GLOBALS['__http_calls'] ) ); }
else { ok( 'kein Netzabruf: weder wp_remote_* noch Sideload noch oEmbed in Theme und Plugin; Rendern ruft nichts ab' ); }

// --- 2. Iframe nur mit Zustimmung --------------------------------------------
$e = array();
foreach ( array( 'fs_consent=all' => array( 'fs_consent', 'all' ), 'fs_tiktok=1 (Video laden, Sitzung)' => array( 'fs_tiktok', '1' ) ) as $name => $c ) {
	$_COOKIE = array( $c[0] => $c[1] );
	$h = $block( array( 'videoUrl' => $sauber, 'heading' => 'Folgen Sie uns' ) );
	if ( ! preg_match( '#<iframe\b([^>]*)>#', $h, $m ) ) { $e[] = "$name: kein Iframe"; continue; }
	$soll = 'https://www.tiktok.com/player/v1/7311111111111111111?music_info=0&description=0&rel=0&autoplay=0&timestamp=0&closed_caption=1&native_context_menu=0';
	if ( ! preg_match( '#src="([^"]+)"#', $m[1], $src ) || html_entity_decode( $src[1] ) !== $soll ) { $e[] = "$name: Player-URL weicht ab ({$m[1]})"; }
	foreach ( array( 'title="TikTok-Video', 'loading="lazy"', 'allowfullscreen', 'allow="fullscreen"' ) as $need ) {
		if ( false === strpos( $m[1], $need ) ) { $e[] = "$name: Iframe ohne $need"; }
	}
	if ( preg_match( '#<script|embed\.js|blockquote#i', $h ) ) { $e[] = "$name: fremdes Skript bzw. Profil-Embed"; }
	if ( false !== strpos( $h, 'data-fs-tt-load' ) ) { $e[] = "$name: Platzhalter vor dem Player trotz Zustimmung"; }
}
$_COOKIE = array( 'fs_consent' => 'essential' );
if ( false !== strpos( $block( array( 'videoUrl' => $sauber ) ), '<iframe' ) ) { $e[] = 'fs_consent=essential: Iframe im HTML'; }
$_COOKIE = array();
$e ? array_map( 'fail', $e ) : ok( 'Iframe nur mit Zustimmung (Banner oder „Video laden“): Player-URL laut Doku, title, loading=lazy, allowfullscreen; „Nur notwendige“ = kein Iframe' );

// --- 3. Platzhalter sonst ----------------------------------------------------
$h = $block( array( 'videoUrl' => $sauber ) );
$e = array();
if ( preg_match( '#<iframe|<script|embed\.js#i', $h ) ) { $e[] = 'Iframe oder Skript im HTML'; }
if ( ! preg_match( '#<button type="button" class="fs-btn fs-btn--primary" data-fs-tt-load>#', $h ) ) { $e[] = 'Knopf „Video laden“ fehlt oder ist kein <button>'; }
if ( false === strpos( $h, '@edi_fahrschule' ) || false === strpos( $h, 'fs-ttplayer__marke' ) ) { $e[] = 'Platzhalter ohne TikTok-Zeichen bzw. Handle'; }
if ( preg_match( '#data-src="([^"]+)"#', $h, $m ) && false === strpos( $m[1], '/player/v1/7311111111111111111' ) ) { $e[] = 'data-src ohne Player-URL'; }
// Die Player-Adresse steht nur im data-Attribut - der Browser lädt daraus nichts.
$ohne_data = preg_replace( '#data-src="[^"]*"#', '', $h );
if ( false !== strpos( $ohne_data, 'tiktok.com/player' ) ) { $e[] = 'Player-Adresse außerhalb von data-src'; }
$e ? array_map( 'fail', $e ) : ok( 'ohne Zustimmung: Platzhalter mit TikTok-Zeichen, Handle und <button> „Video laden“ - kein Iframe, kein Skript im HTML' );

// --- 4. Post-ID aus jeder Schreibweise ---------------------------------------
$e = array();
foreach ( array(
	$sauber,
	'https://www.tiktok.com/@edi_fahrschule/photo/7311111111111111111',
	'https://www.tiktok.com/@edi_fahrschule/video/7311111111111111111?is_from_webapp=1&sender_device=pc#kommentare',
	'https://m.tiktok.com/@edi_fahrschule/video/7311111111111111111/',
) as $u ) {
	if ( '7311111111111111111' !== fs_tiktok_id( $u ) ) { $e[] = "$u -> „" . fs_tiktok_id( $u ) . '“'; }
}
foreach ( array( 'https://www.tiktok.com/@edi_fahrschule', 'https://vm.tiktok.com/ZMabc/', 'https://example.com/video/7311111111111111111', 'kein Link' ) as $u ) {
	if ( '' !== fs_tiktok_id( $u ) ) { $e[] = "$u gilt fälschlich als lesbar"; }
}
$e ? array_map( 'fail', $e ) : ok( 'Post-ID: /photo/, Tracking-Parameter, Anker und m.tiktok.com ergeben dieselbe ID wie die saubere URL; Profil-, Kurz- und fremde Links gelten als unlesbar' );

// --- 5. Unlesbare URL: nichts im Frontend, Grund im Editor -------------------
$e = array();
if ( '' !== trim( $block( array( 'videoUrl' => 'https://vm.tiktok.com/ZMabc/' ) ) ) ) { $e[] = 'Frontend rendert trotz unlesbarer URL'; }
if ( '' !== trim( $block( array() ) ) ) { $e[] = 'Frontend rendert ohne URL'; }
define( 'REST_REQUEST', true );
$hint = $block( array( 'videoUrl' => 'https://vm.tiktok.com/ZMabc/' ) );
if ( false === strpos( $hint, 'nicht lesbar' ) || false === strpos( $hint, 'vm.tiktok.com/ZMabc' ) ) { $e[] = 'Editor nennt die unlesbare URL nicht'; }
// --- 6. Editor: Platzhalter mit erkannter ID, nie das Iframe ----------------
$_COOKIE = array( 'fs_consent' => 'all' );
$ed = $block( array( 'videoUrl' => $sauber ) );
$_COOKIE = array();
if ( false !== strpos( $ed, '<iframe' ) ) { $e[] = 'Editor lädt das Iframe'; }
if ( false === strpos( $ed, 'Erkannte Post-ID: 7311111111111111111' ) ) { $e[] = 'Editor zeigt die erkannte Post-ID nicht'; }
$e ? array_map( 'fail', $e ) : ok( 'unlesbare oder fehlende URL: Sektion leer, Editor nennt die URL; Editor zeigt Platzhalter mit erkannter Post-ID, auch mit Zustimmung kein Iframe' );

// --- 7. Banner und Datenschutzerklärung nennen TikTok ------------------------
$banner = call_user_func( $cfg['fahrschule/cookie-banner']['render'], array() );
ob_start(); fs_legal_datenschutz(); $ds = ob_get_clean();
$e = array();
if ( false === strpos( $banner, 'TikTok' ) ) { $e[] = 'Cookie-Banner nennt TikTok nicht'; }
foreach ( array( 'TikTok Technology Limited' => 'Anbieter', 'IP-Adresse' => 'Daten', 'Drittl' => 'Drittland', 'Art. 6 Abs. 1 lit. a' => 'Rechtsgrundlage' ) as $need => $was ) {
	if ( false === strpos( $ds, $need ) ) { $e[] = "Datenschutzerklärung ohne $was"; }
}
$e ? array_map( 'fail', $e ) : ok( 'Cookie-Banner nennt TikTok; Datenschutzerklärung nennt Anbieter, Daten, Drittland und Rechtsgrundlage' );

echo "\n";
if ( $fails ) { echo 'TIKTOK-PLAYER: ' . count( $fails ) . " Fehler\n"; exit( 1 ); }
echo "TIKTOK-PLAYER: bestanden\n";
