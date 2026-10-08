<?php
/**
 * Seiten-Router.
 *
 * Statt für jede Unterseite ein eigenes Template anzulegen, ermittelt dieser
 * Router die Seite über die zentrale Registry (inc/pages.php) und rendert den
 * dort hinterlegten Block-Stapel. Wurde im Editor eigener Inhalt gepflegt, hat
 * dieser Vorrang – die Registry dient dann nur noch als Fallback.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php

	$page          = fahrschule_current_page();
	$editor_output = trim( (string) get_the_content() );
	$has_own_hero  = ( false !== strpos( $editor_output, 'fahrschule/location-hero' )
		|| false !== strpos( $editor_output, 'fahrschule/testimonials-hero' )
		|| false !== strpos( $editor_output, 'fahrschule/video-hero' ) );

	if ( '' !== $editor_output ) {
		/* --- Im Editor gepflegter Inhalt hat Vorrang --- */
		if ( ! $has_own_hero ) {
			echo fs_block( 'fahrschule/page-hero' );
			echo fs_breadcrumbs();
		}
		the_content();

	} elseif ( $page && ! empty( $page['blocks'] ) ) {
		/* --- Standard: Block-Stapel aus der zentralen Registry --- */
		$first = isset( $page['blocks'][0][0] ) ? $page['blocks'][0][0] : '';
		$skip  = in_array( $first, array( 'fahrschule/location-hero', 'fahrschule/testimonials-hero' ), true );

		if ( ! $skip ) {
			echo fs_block( 'fahrschule/page-hero' );
		}
		echo fs_breadcrumbs();

		foreach ( $page['blocks'] as $block ) {
			$name  = $block[0];
			$attrs = isset( $block[1] ) ? $block[1] : array();
			echo fs_block( $name, $attrs );
		}

	} else {
		/* --- Unbekannte Seite ohne Inhalt --- */
		echo fs_block( 'fahrschule/page-hero' );
		echo fs_breadcrumbs();
	}
	?>
	</article>
	<?php
endwhile;

get_footer();
