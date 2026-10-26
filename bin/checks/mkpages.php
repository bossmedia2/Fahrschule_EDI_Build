<?php
/**
 * Baut aus den vom Smoke-Test gerenderten Seiten (pages.json) statische
 * HTML-Dateien, damit die Browserprüfung an einem echten Dokument misst.
 * Arbeitsverzeichnis aus FS_OUT. Ohne FS_OUT (Aufruf allein) wird ein
 * Temp-Verzeichnis angelegt und der Smoke-Test dorthin ausgeführt.
 */
$theme = $argv[1] ?? dirname( __DIR__, 2 ) . '/fahrschule-edi';
$out   = getenv( 'FS_OUT' );
if ( ! $out ) {
	$out = sys_get_temp_dir() . '/fs-mkpages-' . getmypid();
	@mkdir( $out, 0777, true );
	putenv( 'FS_OUT=' . $out );
	exec( 'php ' . escapeshellarg( __DIR__ . '/smoke.php' ) . ' ' . escapeshellarg( $theme ) . ' >/dev/null 2>&1' );
	echo "Seiten in $out\n";
}
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
	// .wp-site-blocks: der Rahmen, den template-canvas.php um jede Block-Vorlage legt.
	. '<div class="wp-site-blocks">%s</div><script src="frontend.js"></script></body></html>';

foreach ( $pages as $key => $html ) {
	// Theme-URL auf den lokalen Pfad biegen, damit Bilder wirklich laden.
	$html = str_replace( '/wp-content/themes/fahrschule-edi/assets/', 'assets/', $html );
	// Die Block-Vorlage bringt <main> selbst mit. WordPress setzt dort die
	// Sprungmarke für den Skip-Link (the_block_template_skip_link) - hier
	// dasselbe, damit der Link aus dem Seitenrahmen ein Ziel hat.
	if ( 1 !== substr_count( $html, '<main' ) ) {
		fwrite( STDERR, "  FEHLER: $key hat nicht genau ein <main>\n" );
		exit( 1 );
	}
	$html = preg_replace( '#<main(?![^>]*\bid=)#', '<main id="content"', $html, 1 );
	file_put_contents( $out . '/' . $key . '.html', sprintf( $tpl, htmlspecialchars( $key ), $html ) );
}
echo "  ok: " . count( $pages ) . " Seiten gerendert nach $out\n";
