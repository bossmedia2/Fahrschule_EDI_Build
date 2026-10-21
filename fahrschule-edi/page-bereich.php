<?php
/**
 * Template Name: Bereich (mit Klassen-Übersicht)
 *
 * Ebene 1 der Struktur. Hängt automatisch die Karten aller Seiten an, die
 * dieser Seite in inc/pages.php untergeordnet sind - wer eine Klassenseite
 * anlegt und ihr 'parent' setzt, muss die Liste nirgends nachpflegen.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
fahrschule_render_template( array(
	'after' => fs_block( 'fahrschule/bereich-uebersicht' ) . fs_block( 'fahrschule/cta' ),
) );
get_footer();
