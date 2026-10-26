<?php
/**
 * Schreibt die Block- und Seitentabelle in docs/BLOCKS-PATTERNS.md neu -
 * aus fahrschule_blocks_config() und fahrschule_page_map(), damit die Doku
 * nie hinter dem Code herläuft. Ersetzt nur den Bereich zwischen den Markern
 * <!-- tabelle:bloecke --> … <!-- /tabelle:bloecke --> bzw. :seiten.
 *
 *   php bin/docs-tabellen.php            Doku aktualisieren
 *   php bin/docs-tabellen.php --pruefen  nur prüfen (Exit 1, wenn veraltet)
 */
$root = dirname( __DIR__ );
$GLOBALS['__theme'] = $root . '/fahrschule-edi';
require $root . '/bin/checks/wp-stub.php';
define( 'FAHRSCHULE_DIR', $GLOBALS['__theme'] ); define( 'FAHRSCHULE_URI', '/u' ); define( 'FAHRSCHULE_VERSION', '1' );
foreach ( array( 'helpers', 'pdf-texts', 'media', 'pages', 'seo', 'render-callbacks', 'blocks-config' ) as $f ) {
	require $GLOBALS['__theme'] . '/inc/' . $f . '.php';
}
// Nie stumm scheitern: Ohne Blockkonfiguration lässt sich keine Tabelle bauen.
try { fahrschule_blocks_config(); fahrschule_page_map(); }
catch ( Throwable $e ) {
	printf( "  FEHLER: Tabellen nicht erzeugbar - erwartet: Blockkonfiguration und Seitenliste ladbar; vorgefunden: %s (%s:%d)\n", $e->getMessage(), basename( $e->getFile() ), $e->getLine() );
	exit( 1 );
}

$cell = function ( $s ) { return str_replace( array( '|', "\n" ), array( '\|', ' ' ), (string) $s ); };

// Wo steht ein Block? Seiteninhalt (Registry) und Vorlagen/Parts.
$wo = array();
foreach ( fahrschule_page_map() as $key => $pg ) {
	foreach ( $pg['blocks'] ?? array() as $b ) { $wo[ $b[0] ][ '/' . fahrschule_page_path( $key ) ] = true; }
}
foreach ( array_merge( glob( $GLOBALS['__theme'] . '/templates/*.html' ), glob( $GLOBALS['__theme'] . '/parts/*.html' ) ) as $f ) {
	preg_match_all( '#<!-- wp:(fahrschule/[a-z0-9-]+)#', file_get_contents( $f ), $m );
	foreach ( $m[1] as $b ) { $wo[ $b ][ basename( dirname( $f ) ) . '/' . basename( $f ) ] = true; }
}

$z = array( '| Block (`fahrschule/…`) | Titel | Felder | Leer = ausgeblendet | Verwendet in |', '|---|---|---|---|---|' );
foreach ( fahrschule_blocks_config() as $name => $c ) {
	$felder = array();
	foreach ( $c['fields'] ?? array() as $k => $f ) { $felder[] = $f['label'] . ( 'repeater' === $f['control'] ? ' (Liste)' : '' ); }
	$orte = array_keys( $wo[ $name ] ?? array() );
	sort( $orte );
	$z[] = '| `' . substr( $name, 11 ) . '` | ' . $cell( $c['title'] ) . ' | ' . $cell( $felder ? implode( ' · ', $felder ) : '–' )
		. ' | ' . ( ! empty( $c['may_be_empty'] ) ? 'ja' : 'nein' ) . ' | ' . $cell( $orte ? implode( ', ', $orte ) : 'nur als Pattern/Block einfügbar' ) . ' |';
}
$bloecke = implode( "\n", $z );

$z = array( '| Seite | URL | Vorlage | Seiteninhalt (bei Anlage geschrieben) |', '|---|---|---|---|' );
foreach ( fahrschule_page_map() as $key => $pg ) {
	$path = fahrschule_page_path( $key );
	$tpl  = $pg['template'] ?? 'page';
	$inh  = array_map( function ( $b ) { return substr( $b[0], 11 ); }, $pg['blocks'] ?? array() );
	if ( ! $inh && '' !== ( $sm = fahrschule_startmuster_fuer( $tpl ) ) ) {
		$inh = array( 'Startmuster „' . fahrschule_startmuster()[ $sm ][0] . '“: ' . implode( ', ', array_map( function ( $b ) { return substr( $b[0], 11 ); }, fahrschule_startmuster()[ $sm ][3] ) ) );
	}
	$z[]  = '| ' . $cell( $pg['menu'] ) . ' | `/' . $path . ( $path ? '/' : '' ) . '` | `' . $tpl . '` | ' . $cell( $inh ? implode( ', ', $inh ) : '– (alles aus der Vorlage)' ) . ' |';
}
$seiten = implode( "\n", $z );

$doc  = $root . '/docs/BLOCKS-PATTERNS.md';
$alt  = file_get_contents( $doc );
$neu  = $alt;
foreach ( array( 'bloecke' => $bloecke, 'seiten' => $seiten ) as $k => $tab ) {
	$neu = preg_replace_callback( '#(<!-- tabelle:' . $k . ' -->).*?(<!-- /tabelle:' . $k . ' -->)#s', function ( $m ) use ( $tab ) {
		return $m[1] . "\n" . $tab . "\n" . $m[2];
	}, $neu, 1, $n );
	if ( 1 !== $n ) { echo "  FEHLER: erwartet Marker <!-- tabelle:$k --> … <!-- /tabelle:$k --> in docs/BLOCKS-PATTERNS.md; vorgefunden: keiner\n"; exit( 2 ); }
}
if ( in_array( '--pruefen', $argv, true ) ) {
	if ( $neu !== $alt ) {
		// Erste abweichende Zeile zeigen: was der Code erwartet, was in der Doku steht.
		$a = explode( "\n", $alt ); $b = explode( "\n", $neu );
		for ( $i = 0; $i < max( count( $a ), count( $b ) ) && ( $a[ $i ] ?? null ) === ( $b[ $i ] ?? null ); $i++ ) {}
		echo "  FEHLER: docs/BLOCKS-PATTERNS.md ist veraltet - php bin/docs-tabellen.php ausführen\n";
		echo '    Zeile ' . ( $i + 1 ) . "\n    erwartet:    " . mb_substr( $b[ $i ] ?? '(nichts)', 0, 160 ) . "\n    vorgefunden: " . mb_substr( $a[ $i ] ?? '(nichts)', 0, 160 ) . "\n";
		exit( 1 );
	}
	echo '  ok: docs/BLOCKS-PATTERNS.md entspricht Blockkonfiguration (' . count( fahrschule_blocks_config() ) . ' Blöcke) und Seitenliste (' . count( fahrschule_page_map() ) . " Seiten)\n";
	exit( 0 );
}
file_put_contents( $doc, $neu );
echo "docs/BLOCKS-PATTERNS.md aktualisiert\n";
