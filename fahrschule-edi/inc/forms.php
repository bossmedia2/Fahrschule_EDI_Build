<?php
/**
 * Fahrschule edi – eingebauter Formular-Handler (Versand via wp_mail).
 *
 * RÜCKFALL: Ist Contact Form 7 mit dem Begleit-Plugin edi-integrationen aktiv,
 * laufen alle Formulare über CF7 (fs_formular() in inc/render-callbacks.php)
 * und dieser Handler bleibt ungenutzt. Ohne CF7 verschickt er Kontakt- und
 * Schnellanmeldung weiterhin - die Website verliert nie ihre Formulare.
 * Sicher: Nonce-Check + Honeypot + beide Einwilligungen (Datenschutz,
 * Art. 8 DSGVO). Leitet zurück mit ?fs_schnell= bzw. ?fs_kontakt=ok|error.
 *
 * Optionaler Zusatzkanal: ist das companion-plugin edi-integrationen aktiv,
 * geht dieselbe Anfrage zusätzlich als Lead an Odoo (fahrschule_odoo_send_lead()).
 * Ohne das Plugin läuft der Versand unverändert nur über wp_mail() – siehe
 * den function_exists()-Aufruf unten.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Standort-E-Mail aus den Stammdaten (inc/seo.php) - dieselbe Quelle wie
 * Seite, JSON-LD und die CF7-Formulare. Unbekannter Standort: allgemeine
 * Adresse, nie eine fremde.
 */
function fahrschule_standort_email( $stadt ) {
	$b   = fahrschule_business_data();
	$key = sanitize_key( (string) $stadt );
	return ! empty( $b['locations'][ $key ]['email'] ) ? $b['locations'][ $key ]['email'] : $b['email'];
}

/** Beide Pflicht-Einwilligungen gesetzt (Datenschutz, Art. 8 DSGVO)? */
function fahrschule_formular_einwilligungen_ok() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce prüft der Aufrufer
	return ! empty( $_POST['fs_privacy'] ) && ! empty( $_POST['fs_alter'] );
}

/**
 * "Schnelle Anmeldung" auf den drei Standort-Seiten
 * (fs_render_location_hero() in inc/render-callbacks.php). Eine
 * Handler-Funktion für alle drei Städte – die Stadt kommt als verstecktes
 * Feld mit dem Formular mit.
 */
function fahrschule_handle_schnellanmeldung() {
	$back = ! empty( $_POST['fs_back'] ) ? esc_url_raw( wp_unslash( $_POST['fs_back'] ) ) : fs_url( 'standorte' );

	// Honeypot: Bot ausgefüllt → still wegwerfen, ohne Fehler zu zeigen.
	if ( ! empty( $_POST['fs_hp'] ) ) {
		wp_safe_redirect( add_query_arg( 'fs_schnell', 'ok', $back ) );
		exit;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'fahrschule_schnellanmeldung' ) ) {
		wp_safe_redirect( add_query_arg( 'fs_schnell', 'error', $back ) );
		exit;
	}

	if ( ! fahrschule_formular_einwilligungen_ok() ) {
		wp_safe_redirect( add_query_arg( 'fs_schnell', 'error', $back ) );
		exit;
	}

	$stadt  = isset( $_POST['fs_stadt'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_stadt'] ) ) : '';
	$name   = isset( $_POST['fs_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_name'] ) ) : '';
	$email  = isset( $_POST['fs_email'] ) ? sanitize_email( wp_unslash( $_POST['fs_email'] ) ) : '';
	$phone  = isset( $_POST['fs_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_phone'] ) ) : '';
	$klasse = isset( $_POST['fs_klasse'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_klasse'] ) ) : '';

	if ( ! $name || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'fs_schnell', 'error', $back ) );
		exit;
	}

	$to = fahrschule_standort_email( $stadt );

	// Nur Klassen aus PDF F3 - dieselbe Liste wie im Formular.
	$alle = array();
	foreach ( fahrschule_pdf( 'f3' )['groups'] as $kl ) { $alle = array_merge( $alle, $kl ); }
	$klasse_text = in_array( $klasse, $alle, true ) ? 'Klasse ' . $klasse : '(nicht angegeben)';

	$subject = 'Neue Schnellanmeldung – Fahrschule edi ' . ( $stadt ? $stadt : '(Standort unbekannt)' );
	$body    = "Standort: {$stadt}\nName: {$name}\nE-Mail: {$email}\nTelefon: {$phone}\nFührerscheinklasse: {$klasse_text}\n";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$ok = wp_mail( $to, $subject, $body, $headers );

	// Zusatzkanal CRM: optionales companion-plugin edi-integrationen.
	if ( function_exists( 'fahrschule_odoo_send_lead' ) ) {
		fahrschule_odoo_send_lead( 'schnellanmeldung', array(
			'name'    => $name,
			'email'   => $email,
			'telefon' => $phone,
			'stadt'   => $stadt,
			'klasse'  => $klasse_text,
		) );
	}

	wp_safe_redirect( add_query_arg( 'fs_schnell', $ok ? 'ok' : 'error', $back ) );
	exit;
}
add_action( 'admin_post_fahrschule_schnellanmeldung', 'fahrschule_handle_schnellanmeldung' );
add_action( 'admin_post_nopriv_fahrschule_schnellanmeldung', 'fahrschule_handle_schnellanmeldung' );

/**
 * Allgemeines Kontakt-/Anmeldeformular (fs_render_contact_form() in
 * inc/render-callbacks.php) - eingebunden auf den Seiten /kontakt/ und
 * /anmeldung/. Eine Handler-Funktion für beide, da es sich inhaltlich um
 * dieselbe Anfrage handelt (Kontaktaufnahme mit optionalem Standort- und
 * Klasseninteresse) - lediglich die Rücksprung-URL unterscheidet sich.
 */
function fahrschule_handle_kontakt() {
	$back = ! empty( $_POST['fs_back'] ) ? esc_url_raw( wp_unslash( $_POST['fs_back'] ) ) : fs_url( 'kontakt' );

	// Honeypot: Bot ausgefüllt → still wegwerfen, ohne Fehler zu zeigen.
	if ( ! empty( $_POST['fs_hp'] ) ) {
		wp_safe_redirect( add_query_arg( 'fs_kontakt', 'ok', $back ) );
		exit;
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'fahrschule_kontakt' ) ) {
		wp_safe_redirect( add_query_arg( 'fs_kontakt', 'error', $back ) );
		exit;
	}

	if ( ! fahrschule_formular_einwilligungen_ok() ) {
		wp_safe_redirect( add_query_arg( 'fs_kontakt', 'error', $back ) );
		exit;
	}

	$name    = isset( $_POST['fs_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_name'] ) ) : '';
	$email   = isset( $_POST['fs_email'] ) ? sanitize_email( wp_unslash( $_POST['fs_email'] ) ) : '';
	$phone   = isset( $_POST['fs_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_phone'] ) ) : '';
	$stadt   = isset( $_POST['fs_stadt'] ) ? sanitize_text_field( wp_unslash( $_POST['fs_stadt'] ) ) : '';
	$message = isset( $_POST['fs_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fs_message'] ) ) : '';

	if ( ! $name || ! is_email( $email ) || ! $message ) {
		wp_safe_redirect( add_query_arg( 'fs_kontakt', 'error', $back ) );
		exit;
	}

	$to      = fahrschule_standort_email( $stadt );
	$subject = 'Neue Kontaktanfrage – Fahrschule edi' . ( $stadt ? ' ' . $stadt : '' );
	$body    = "Name: {$name}\nE-Mail: {$email}\nTelefon: {$phone}\n" . ( $stadt ? "Standort-Interesse: {$stadt}\n" : '' ) . "\nNachricht:\n{$message}\n";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$ok = wp_mail( $to, $subject, $body, $headers );

	// Zusatzkanal CRM: optionales companion-plugin edi-integrationen.
	if ( function_exists( 'fahrschule_odoo_send_lead' ) ) {
		fahrschule_odoo_send_lead( 'kontakt', array(
			'name'    => $name,
			'email'   => $email,
			'telefon' => $phone,
			'stadt'   => $stadt,
			'message' => $message,
		) );
	}

	wp_safe_redirect( add_query_arg( 'fs_kontakt', $ok ? 'ok' : 'error', $back ) );
	exit;
}
add_action( 'admin_post_fahrschule_kontakt', 'fahrschule_handle_kontakt' );
add_action( 'admin_post_nopriv_fahrschule_kontakt', 'fahrschule_handle_kontakt' );
