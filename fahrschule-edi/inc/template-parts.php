<?php
/**
 * Gemeinsamer Rumpf der auswählbaren Seitenvorlagen.
 *
 * Die drei Vorlagen (Übersicht, Bereich, Kurs) unterscheiden sich nur darin,
 * was sie VOR und NACH dem Editorinhalt automatisch ergänzen. Der Rahmen -
 * Seitenkopf, Brotkrumen, Editorinhalt mit Registry-Fallback - ist identisch
 * und steht deshalb hier statt dreifach in den Vorlagendateien.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Rendert eine Seite nach Vorlagenschema.
 *
 * @param array $args {
 *     @type string $before Block-Ausgabe vor dem Inhalt (bereits gerendert).
 *     @type string $after  Block-Ausgabe nach dem Inhalt (bereits gerendert).
 * }
 */
function fahrschule_render_template( $args = array() ) {
	$args = array_merge( array( 'before' => '', 'after' => '' ), $args );

	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php
		$page          = fahrschule_current_page();
		$editor_output = trim( (string) get_the_content() );

		// Ein eigener Hero im Inhalt ersetzt den Standard-Seitenkopf.
		$has_own_hero = ( false !== strpos( $editor_output, 'fahrschule/location-hero' )
			|| false !== strpos( $editor_output, 'fahrschule/testimonials-hero' )
			|| false !== strpos( $editor_output, 'fahrschule/video-hero' ) );

		if ( ! $has_own_hero ) {
			echo fs_block( 'fahrschule/page-hero' );
		}
		echo fs_breadcrumbs();

		echo $args['before']; // phpcs:ignore WordPress.Security.EscapeOutput -- fertig gerenderte Blöcke.

		if ( '' !== $editor_output ) {
			the_content();
		} elseif ( $page && ! empty( $page['blocks'] ) ) {
			// Kein Editorinhalt: der in inc/pages.php hinterlegte Stapel greift.
			foreach ( $page['blocks'] as $block ) {
				echo fs_block( $block[0], isset( $block[1] ) ? $block[1] : array() );
			}
		}

		echo $args['after']; // phpcs:ignore WordPress.Security.EscapeOutput -- fertig gerenderte Blöcke.
		?>
		</article>
		<?php
	endwhile;
}
