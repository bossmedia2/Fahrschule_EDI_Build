<?php
/**
 * Template Name: Übersicht / Landingpage
 *
 * Für Einstiegsseiten, die in mehrere Unterthemen verzweigen: Seitenkopf,
 * frei befüllbarer Inhalt, abschließender Handlungsaufruf. Die Teaser werden
 * bewusst NICHT automatisch gesetzt - auf einer Landingpage entscheidet die
 * Redaktion, worauf verwiesen wird.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
fahrschule_render_template( array(
	'after' => fs_block( 'fahrschule/cta' ),
) );
get_footer();
