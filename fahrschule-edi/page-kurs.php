<?php
/**
 * Template Name: Kurs / Führerscheinklasse
 *
 * Ebene 2 der Struktur. Nach dem Inhalt folgen die Geschwisterklassen des
 * eigenen Bereichs und der Anmeldeabschnitt - auf einer Klassenseite ist der
 * nächste sinnvolle Schritt immer die Anmeldung oder der Vergleich.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$fs_page   = fahrschule_current_page();
$fs_parent = ( $fs_page && ! empty( $fs_page['parent'] ) ) ? $fs_page['parent'] : '';

fahrschule_render_template( array(
	'after' => ( $fs_parent
			? fs_block( 'fahrschule/bereich-uebersicht', array(
				'bereich' => $fs_parent,
				'eyebrow' => 'Weitere Klassen',
				'heading' => 'Andere Klassen in diesem Bereich',
			) )
			: '' )
		. fs_block( 'fahrschule/anmeldung' ),
) );
get_footer();
