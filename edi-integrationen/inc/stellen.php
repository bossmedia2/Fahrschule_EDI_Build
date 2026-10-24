<?php
/**
 * edi-integrationen – Stellenanzeigen mit WP Job Manager.
 *
 * Die Fahrschule schreibt Stellen selbst im Backend aus (Job Listings → Neu).
 * Deshalb legt dieses Modul bewusst KEINE Seiten für das Einreichen von
 * Stellen durch Besucher oder ein Arbeitgeber-Dashboard an - der
 * Einrichtungsassistent von WP Job Manager kann übersprungen werden.
 *
 * Es sorgt nur für die Kategorien der Karriereseite und schaltet sie ein.
 * Angezeigt werden die Stellen vom Block fahrschule/jobs des Themes (Seite
 * /ueber-uns/karriere/), die Einzelansicht von der Vorlage
 * single-job_listing; beworben wird über das CF7-Formular „Bewerbung“.
 *
 * @package Edi_Integrationen
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Kategorien der Karriereseite (Briefing AP 6). */
function edi_stellen_kategorien() {
	return array(
		'fahrlehrer'               => 'Fahrlehrer',
		'buero-ansprechpartner'    => 'Büro & Ansprechpartner',
		'quereinstieg-ausbildung'  => 'Quereinstieg & Ausbildung',
	);
}

/** Legt fehlende Kategorien an; vorhandene bleiben, wie sie sind. */
function edi_stellen_einrichten() {
	if ( ! taxonomy_exists( 'job_listing_category' ) ) { return; }
	update_option( 'job_manager_enable_categories', 1 );
	foreach ( edi_stellen_kategorien() as $slug => $name ) {
		if ( ! term_exists( $slug, 'job_listing_category' ) ) {
			wp_insert_term( $name, 'job_listing_category', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'admin_init', 'edi_stellen_einrichten' );
