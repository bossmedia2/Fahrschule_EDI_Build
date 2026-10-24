<?php
/**
 * Baut aus den vom Smoke-Test gerenderten Seiten (pages.json) statische
 * HTML-Dateien, damit die Browserprüfung an einem echten Dokument misst.
 * Erwartet FS_OUT = Arbeitsverzeichnis.
 */
$out   = getenv( 'FS_OUT' );
$theme = $argv[1];
$pages = json_decode( file_get_contents( $out . '/pages.json' ), true );

@mkdir( $out . '/assets/images', 0777, true );
foreach ( glob( $theme . '/assets/images/*' ) as $img ) {
	copy( $img, $out . '/assets/images/' . basename( $img ) );
}
copy( $theme . '/assets/css/main.css', $out . '/main.css' );
copy( $theme . '/assets/js/frontend.js', $out . '/frontend.js' );

$tpl = '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
	. '<meta name="viewport" content="width=device-width, initial-scale=1">'
	. '<title>%s</title><link rel="stylesheet" href="main.css">'
	. '<noscript><style>.fs-fade-up{opacity:1 !important;animation:none !important}'
	. '.fs-cookiebanner,.fs-tiktok{display:none !important}</style></noscript>'
	. '</head><body class="fahrschule-theme"><a class="fs-skip" href="#content">Zum Inhalt springen</a>'
	. '%s<script src="frontend.js"></script></body></html>';

foreach ( $pages as $key => $html ) {
	// Theme-URL auf den lokalen Pfad biegen, damit Bilder wirklich laden.
	$html = str_replace( '/wp-content/themes/fahrschule-edi/assets/', 'assets/', $html );
	// Die Marke setzt smoke.php hinter die Navigation. Früher suchte hier ein
	// Regex das erste "</div></div>" nach dem Overlay - das lag im Schubfach,
	// <main> landete mitten darin und verbog dessen Layout in der Prüfung.
	if ( false === strpos( $html, '<!--fs-main-->' ) ) {
		fwrite( STDERR, "  FEHLER: $key ohne <!--fs-main--> Marke\n" );
		exit( 1 );
	}
	$html = str_replace( '<!--fs-main-->', '<main id="content">', $html ) . '</main>';
	file_put_contents( $out . '/' . $key . '.html', sprintf( $tpl, htmlspecialchars( $key ), $html ) );
}
echo "  ok: " . count( $pages ) . " Seiten gerendert nach $out\n";
