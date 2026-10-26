<?php
/**
 * „Aus Startmuster neu aufbauen“ - für Seiten, die vor 3.2 angelegt wurden.
 *
 * Bis 3.1 standen Seitenkopf, Handlungsaufruf & Co. in der Vorlage; der
 * Seiteninhalt bestehender Seiten ist deshalb dünn. Die automatische
 * Seitenanlage überschreibt nie etwas (redaktionelle Arbeit ginge verloren).
 * Stattdessen gibt es diese ausdrückliche Aktion je Seite bzw. für eine
 * Auswahl in der Seitenliste:
 *
 *   1. Zeilenaktion oder Sammelaktion „Aus Startmuster neu aufbauen“
 *   2. Bestätigungsseite: warnt, nennt jede betroffene Seite
 *   3. erst der Knopf dort (POST, Nonce, edit_page je Seite) ersetzt den
 *      Inhalt - als normale Aktualisierung, die alte Fassung bleibt als
 *      Revision erhalten
 *
 * Quelle ist dieselbe wie bei der Seitenanlage: der Block-Stapel der Seite
 * aus der Registry samt Seitenrahmen, sonst das Startmuster ihrer Vorlage.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const FAHRSCHULE_NEUAUFBAU_SEITE = 'fahrschule-neu-aufbauen';

/** Neuer Inhalt einer Seite ('' = keine Quelle, Aktion wird nicht angeboten). */
function fahrschule_neuaufbau_inhalt( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'page' !== $post->post_type ) { return ''; }
	// Registry-Seite? Über den Pfad finden; die Startseite über page_on_front.
	$pfad = (int) get_option( 'page_on_front' ) === (int) $post->ID ? '' : trim( (string) get_page_uri( $post ), '/' );
	foreach ( fahrschule_page_map() as $key => $p ) {
		if ( fahrschule_page_path( $key ) === $pfad && ( '' !== $pfad || 'home' === $key ) ) {
			return fahrschule_page_content( $key );
		}
	}
	$muster = fahrschule_startmuster_fuer( (string) get_post_meta( $post->ID, '_wp_page_template', true ) );
	return '' === $muster ? '' : fahrschule_startmuster_markup( $muster );
}

/** Link auf die Bestätigungsseite für eine Auswahl von Seiten. */
function fahrschule_neuaufbau_link( $ids ) {
	return add_query_arg(
		array( 'page' => FAHRSCHULE_NEUAUFBAU_SEITE, 'ids' => implode( ',', array_map( 'intval', (array) $ids ) ) ),
		admin_url( 'options.php' )
	);
}

/** Zeilenaktion in der Seitenliste. */
function fahrschule_neuaufbau_zeilenaktion( $actions, $post ) {
	if ( current_user_can( 'edit_page', $post->ID ) && '' !== fahrschule_neuaufbau_inhalt( $post ) ) {
		$actions['fs_neu_aufbauen'] = '<a href="' . esc_url( fahrschule_neuaufbau_link( array( $post->ID ) ) ) . '">' . esc_html__( 'Aus Startmuster neu aufbauen', 'fahrschule-edi' ) . '</a>';
	}
	return $actions;
}
add_filter( 'page_row_actions', 'fahrschule_neuaufbau_zeilenaktion', 10, 2 );

/** Sammelaktion: führt nur auf die Bestätigungsseite, ersetzt selbst nichts. */
function fahrschule_neuaufbau_sammelaktion( $actions ) {
	$actions['fs_neu_aufbauen'] = __( 'Aus Startmuster neu aufbauen', 'fahrschule-edi' );
	return $actions;
}
add_filter( 'bulk_actions-edit-page', 'fahrschule_neuaufbau_sammelaktion' );

function fahrschule_neuaufbau_sammelaktion_ziel( $redirect, $action, $ids ) {
	return 'fs_neu_aufbauen' === $action ? fahrschule_neuaufbau_link( $ids ) : $redirect;
}
add_filter( 'handle_bulk_actions-edit-page', 'fahrschule_neuaufbau_sammelaktion_ziel', 10, 3 );

/** Versteckte Admin-Seite (unter options.php, erscheint in keinem Menü). */
function fahrschule_neuaufbau_menue() {
	add_submenu_page( 'options.php', __( 'Seiten neu aufbauen', 'fahrschule-edi' ), '', 'edit_pages', FAHRSCHULE_NEUAUFBAU_SEITE, 'fahrschule_neuaufbau_bestaetigung' );
}
add_action( 'admin_menu', 'fahrschule_neuaufbau_menue' );

/** Bestätigungsseite: zeigt nur an, ändert nichts. */
function fahrschule_neuaufbau_bestaetigung() {
	$ids    = array_filter( array_map( 'intval', explode( ',', isset( $_GET['ids'] ) ? (string) wp_unslash( $_GET['ids'] ) : '' ) ) );
	$seiten = array();
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( $p && current_user_can( 'edit_page', $id ) && '' !== fahrschule_neuaufbau_inhalt( $p ) ) { $seiten[] = $p; }
	}
	echo '<div class="wrap"><h1>' . esc_html__( 'Seiten aus Startmuster neu aufbauen', 'fahrschule-edi' ) . '</h1>';
	if ( ! $seiten ) {
		echo '<p>' . esc_html__( 'Keine der gewählten Seiten hat ein Startmuster.', 'fahrschule-edi' ) . '</p></div>';
		return;
	}
	echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'Achtung:', 'fahrschule-edi' ) . '</strong> '
		. esc_html__( 'Der Inhalt dieser Seiten wird vollständig durch den Ausgangsinhalt ersetzt – Seitenkopf, alle Abschnitte und Handlungsaufruf, wie bei einer neuen Installation. Eigene Änderungen im Inhalt gehen dabei verloren; die bisherige Fassung bleibt als Revision erhalten (Seite bearbeiten → Revisionen).', 'fahrschule-edi' )
		. '</p></div><ul style="list-style:disc;padding-left:1.5rem">';
	foreach ( $seiten as $p ) { echo '<li>' . esc_html( get_the_title( $p ) ) . '</li>'; }
	echo '</ul><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'fs_neu_aufbauen' );
	echo '<input type="hidden" name="action" value="fs_neu_aufbauen">'
		. '<input type="hidden" name="ids" value="' . esc_attr( implode( ',', wp_list_pluck( $seiten, 'ID' ) ) ) . '">'
		. '<input type="hidden" name="bestaetigt" value="1">';
	submit_button( sprintf( /* translators: %d: Anzahl */ _n( '%d Seite jetzt ersetzen', '%d Seiten jetzt ersetzen', count( $seiten ), 'fahrschule-edi' ), count( $seiten ) ), 'primary' );
	echo ' <a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">' . esc_html__( 'Abbrechen', 'fahrschule-edi' ) . '</a></form></div>';
}

/**
 * Ersetzt den Inhalt - nur mit gültiger Nonce, ausdrücklicher Bestätigung
 * und edit_page je Seite. Gibt die Zahl der ersetzten Seiten zurück.
 */
function fahrschule_neuaufbau_ausfuehren( $anfrage ) {
	if ( empty( $anfrage['bestaetigt'] ) || empty( $anfrage['_wpnonce'] ) || ! wp_verify_nonce( $anfrage['_wpnonce'], 'fs_neu_aufbauen' ) ) {
		return 0;
	}
	$n = 0;
	foreach ( array_filter( array_map( 'intval', explode( ',', (string) ( $anfrage['ids'] ?? '' ) ) ) ) as $id ) {
		if ( ! current_user_can( 'edit_page', $id ) ) { continue; }
		$inhalt = fahrschule_neuaufbau_inhalt( $id );
		if ( '' === $inhalt ) { continue; }
		$r = wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( $inhalt ) ), true );
		if ( $r && ! is_wp_error( $r ) ) { $n++; }
	}
	return $n;
}

function fahrschule_neuaufbau_post() {
	$n = fahrschule_neuaufbau_ausfuehren( wp_unslash( $_POST ) );
	wp_safe_redirect( add_query_arg( 'fs_neu_aufgebaut', $n, admin_url( 'edit.php?post_type=page' ) ) );
	exit;
}
add_action( 'admin_post_fs_neu_aufbauen', 'fahrschule_neuaufbau_post' );

function fahrschule_neuaufbau_hinweis() {
	if ( ! isset( $_GET['fs_neu_aufgebaut'] ) ) { return; }
	$n = (int) $_GET['fs_neu_aufgebaut'];
	printf(
		'<div class="notice %s is-dismissible"><p><strong>Fahrschule edi:</strong> %s</p></div>',
		$n ? 'notice-success' : 'notice-warning',
		esc_html( $n ? sprintf( '%d Seite(n) aus dem Startmuster neu aufgebaut. Die vorige Fassung steht in den Revisionen.', $n ) : 'Nichts ersetzt – Bestätigung fehlte oder keine berechtigte Seite.' )
	);
}
add_action( 'admin_notices', 'fahrschule_neuaufbau_hinweis' );
