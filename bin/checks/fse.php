<?php
/**
 * Block-Vorlagen offline rendern - so, wie WordPress es mit einem Block-Theme tut.
 *
 * Ohne WordPress-Installation lässt sich nicht prüfen, was ein Besucher sieht,
 * wenn die Seite aus templates/*.html, parts/*.html und post_content entsteht.
 * Diese Datei bildet genau diesen Weg nach:
 *
 *   fs_fse_parse()   Block-Grammatik von WP_Block_Parser (Kommentar-Begrenzer,
 *                    JSON-Attribute, verschachtelte Blöcke, innerContent mit
 *                    Platzhaltern für innere Blöcke)
 *   fs_fse_render()  wie render_block(): Template-Part lädt parts/<slug>.html,
 *                    post-content rendert den Seiteninhalt, statische Blöcke
 *                    setzen ihr gespeichertes HTML zusammen, eigene Blöcke
 *                    laufen durch ihre Render-Funktion
 *
 * Kern-Blöcke, die WordPress dynamisch rendert (Suche, Abfrage, Beitragstitel),
 * werden vereinfacht nachgebildet - es geht um den Rahmen, nicht um WP selbst.
 */

/** Parser nach der Grammatik von WP_Block_Parser (wp-includes/class-wp-block-parser.php). */
function fs_fse_parse( $doc ) {
	$re = '/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s';
	$root  = array( 'innerBlocks' => array(), 'innerContent' => array(), 'innerHTML' => '' );
	$stack = array();
	$pos   = 0;
	$cur   = &$root;
	$open  = array( &$root );
	while ( preg_match( $re, $doc, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
		$start = $m[0][1];
		$len   = strlen( $m[0][0] );
		$html  = substr( $doc, $pos, $start - $pos );
		$top   = &$open[ count( $open ) - 1 ];
		if ( '' !== $html ) {
			$top['innerHTML']     .= $html;
			$top['innerContent'][] = $html;
		}
		$name  = ( isset( $m['namespace'][0] ) && '' !== $m['namespace'][0] && -1 !== $m['namespace'][1] ? $m['namespace'][0] : 'core/' ) . $m['name'][0];
		$attrs = ( isset( $m['attrs'][0] ) && -1 !== $m['attrs'][1] && '' !== $m['attrs'][0] ) ? json_decode( $m['attrs'][0], true ) : array();
		if ( null === $attrs ) {
			throw new RuntimeException( "Ungültiges JSON in Block $name: " . $m['attrs'][0] );
		}
		$closer = isset( $m['closer'][0] ) && -1 !== $m['closer'][1] && '/' === $m['closer'][0];
		$void   = isset( $m['void'][0] ) && -1 !== $m['void'][1] && '/' === $m['void'][0];
		if ( $closer ) {
			if ( count( $open ) < 2 ) { throw new RuntimeException( "Schließender Block ohne Öffnung: $name" ); }
			$done = array_pop( $open );
			if ( $done['blockName'] !== $name ) {
				throw new RuntimeException( "Block {$done['blockName']} wird als $name geschlossen" );
			}
			$parent                   = &$open[ count( $open ) - 1 ];
			$parent['innerBlocks'][]  = $done;
			$parent['innerContent'][] = null;
		} else {
			$block = array( 'blockName' => $name, 'attrs' => $attrs ?: array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
			if ( $void ) {
				$top['innerBlocks'][]  = $block;
				$top['innerContent'][] = null;
			} else {
				$open[] = $block;
			}
		}
		unset( $top, $parent );
		$pos = $start + $len;
	}
	if ( count( $open ) > 1 ) {
		throw new RuntimeException( 'Nicht geschlossener Block: ' . $open[ count( $open ) - 1 ]['blockName'] );
	}
	$tail = substr( $doc, $pos );
	if ( '' !== trim( $tail ) ) {
		$open[0]['innerContent'][] = $tail;
	}
	// Freies HTML auf oberster Ebene wird wie bei WP zu einem Block ohne Namen.
	$out = array();
	$i   = 0;
	foreach ( $open[0]['innerContent'] as $chunk ) {
		if ( null === $chunk ) {
			$out[] = $open[0]['innerBlocks'][ $i++ ];
		} elseif ( '' !== trim( $chunk ) ) {
			$out[] = array( 'blockName' => null, 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => $chunk, 'innerContent' => array( $chunk ) );
		}
	}
	return $out;
}

/** Inhalt der gerade "geöffneten" Seite für core/post-content. */
function fs_fse_set_content( $content ) { $GLOBALS['__fse_content'] = $content; }

function fs_fse_render_list( $blocks, $theme ) {
	$out = '';
	foreach ( $blocks as $b ) { $out .= fs_fse_render( $b, $theme ); }
	return $out;
}

function fs_fse_render( $b, $theme ) {
	$name = $b['blockName'];
	$a    = $b['attrs'];
	$inner = function () use ( $b, $theme ) {
		$html = '';
		$i    = 0;
		foreach ( $b['innerContent'] as $chunk ) {
			$html .= null === $chunk ? fs_fse_render( $b['innerBlocks'][ $i++ ], $theme ) : $chunk;
		}
		return $html;
	};
	switch ( $name ) {
		case null:
			return $b['innerHTML'];
		case 'core/template-part':
			$file = $theme . '/parts/' . $a['slug'] . '.html';
			if ( ! is_file( $file ) ) { throw new RuntimeException( "Template-Part fehlt: parts/{$a['slug']}.html" ); }
			$tag = isset( $a['tagName'] ) ? $a['tagName'] : 'div';
			return "<$tag class=\"wp-block-template-part\">" . fs_fse_render_list( fs_fse_parse( file_get_contents( $file ) ), $theme ) . "</$tag>";
		case 'core/post-content':
			$c = isset( $GLOBALS['__fse_content'] ) ? $GLOBALS['__fse_content'] : '';
			return '<div class="entry-content wp-block-post-content is-layout-flow">' . fs_fse_render_list( fs_fse_parse( $c ), $theme ) . '</div>';
		case 'core/post-title':
			$tag = 'h' . ( isset( $a['level'] ) ? (int) $a['level'] : 2 );
			return "<$tag class=\"wp-block-post-title\">" . esc_html( get_the_title() ) . "</$tag>";
		case 'core/query-title':
			return '<h1 class="wp-block-query-title">' . ( 'search' === ( $a['type'] ?? '' ) ? 'Suchergebnisse' : 'Archiv' ) . '</h1>';
		case 'core/search':
			// Markup wie render_block_core_search() (Knopf außen, Beschriftung verborgen).
			return '<form role="search" method="get" action="/" class="wp-block-search__button-outside wp-block-search__text-button wp-block-search"><label class="wp-block-search__label screen-reader-text" for="wp-block-search__input-1">' . esc_html( $a['label'] ?? 'Suche' ) . '</label><div class="wp-block-search__inside-wrapper"><input class="wp-block-search__input" id="wp-block-search__input-1" placeholder="" value="" type="search" name="s" required /><button aria-label="' . esc_attr( $a['buttonText'] ?? 'Suchen' ) . '" class="wp-block-search__button wp-element-button" type="submit">' . esc_html( $a['buttonText'] ?? 'Suchen' ) . '</button></div></form>';
		case 'core/post-template':
		case 'core/post-excerpt':
		case 'core/post-date':
		case 'core/query-pagination':
		case 'core/query-pagination-previous':
		case 'core/query-pagination-next':
			return '';                    // keine Beiträge im Test
		case 'core/query-no-results':
			return $inner();
	}
	if ( 0 === strpos( (string) $name, 'core/' ) ) {
		// Statische Kern-Blöcke (group, heading, paragraph, query-Hülle):
		// gespeichertes HTML mit gerenderten inneren Blöcken.
		return $inner();
	}
	return render_block( $b );
}

/** Rendert eine Vorlage aus templates/ mit dem gegebenen Seiteninhalt. */
function fs_fse_render_template( $theme, $slug, $content ) {
	$file = $theme . '/templates/' . $slug . '.html';
	if ( ! is_file( $file ) ) { throw new RuntimeException( "Vorlage fehlt: templates/$slug.html" ); }
	fs_fse_set_content( $content );
	return fs_fse_render_list( fs_fse_parse( file_get_contents( $file ) ), $theme );
}
