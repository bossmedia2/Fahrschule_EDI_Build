<?php
/**
 * Fallback-Template.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
echo '<div class="fs-pagetop"></div>';

if ( have_posts() ) {
	echo '<div class="fs-section"><div class="fs-container">';
	while ( have_posts() ) {
		the_post();
		echo '<article class="fs-mb-8">';
		echo '<h2 class="fs-head" style="text-align:left;"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';
		echo '<div class="fs-rich">';
		the_excerpt();
		echo '</div></article>';
	}
	echo '</div></div>';
} else {
	echo '<div class="fs-section"><div class="fs-container"><p>Keine Inhalte gefunden.</p></div></div>';
}

get_footer();
