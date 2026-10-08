<?php
/**
 * edi-integrationen – Odoo-Lead-Webhook (optionale CRM-Anbindung).
 *
 * Kein fest verdrahteter Odoo-JSONRPC-Client (crm.lead.create per externer
 * API) – dafür bräuchte es Datenbankname, Benutzer-ID und Passwort/API-Key
 * einer konkreten, self-hosted Odoo-Instanz, die wir nicht kennen und nicht
 * raten dürfen. Stattdessen ein sauberer, dokumentierter ausgehender
 * Webhook: ein JSON-POST an eine in den Plugin-Einstellungen hinterlegte
 * URL, mit optionalem Bearer-Token als gemeinsamem Geheimnis. Auf der
 * Odoo-Seite nimmt entweder eine eigene Automatisierungsregel (Odoo ≥ 17:
 * Einstellungen → Technisch → Automatisierung → Webhooks) oder ein kleines
 * Controller-Modul diesen Webhook entgegen und legt daraus den Lead an.
 *
 * Ohne hinterlegte URL: no-op, kein Fehler, kein Log-Eintrag.
 *
 * UNGETESTET gegen eine echte Odoo-Instanz, mangels Zugangsdaten. Vor dem
 * produktiven Einsatz einmal mit der echten Webhook-URL des Kunden
 * gegenprüfen (z. B. über einen Dienst wie webhook.site als Zwischenstopp).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Sendet einen Lead an Odoo, sofern eine Webhook-URL hinterlegt ist.
 *
 * Bewusst nicht-blockierend (fire-and-forget, siehe $args unten) und
 * ausdrücklich fehlertolerant: ein falsch konfiguriertes oder down
 * liegendes CRM darf niemals den E-Mail-Versand verzögern oder die
 * Weiterleitung des Formulars verhindern. Die E-Mail bleibt der
 * verlässliche Kanal, Odoo ist ein Zusatzkanal obendrauf – deshalb wird der
 * Rückgabewert von wp_remote_post() hier nicht einmal ausgewertet.
 *
 * @param string $formular Kurzname des Formulars, z. B. 'schnellanmeldung'.
 * @param array  $felder   Bereits sanitierte Formularfelder
 *                         (name, email, telefon, stadt, klasse, ...).
 */
function fahrschule_odoo_send_lead( string $formular, array $felder ): void {
	$url = get_option( 'fahrschule_odoo_url', '' );
	if ( ! $url ) { return; } // keine Anbindung eingerichtet - stillschweigend nichts tun

	$payload = array(
		'source'       => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
		'form'         => $formular,
		'submitted_at' => current_time( 'c' ),
	) + $felder;

	$headers = array( 'Content-Type' => 'application/json' );
	$key     = get_option( 'fahrschule_odoo_key', '' );
	if ( $key ) {
		$headers['Authorization'] = 'Bearer ' . $key;
	}

	wp_remote_post( esc_url_raw( $url ), array(
		// Fire-and-forget: der extrem kurze Timeout ist beabsichtigt, nicht
		// versehentlich zu knapp - bei blocking=false schreibt WordPress die
		// Anfrage noch vollständig auf die Leitung, wartet aber nicht auf
		// die Antwort. So bleibt die Weiterleitung des Formulars unabhängig
		// von der Erreichbarkeit des CRM.
		'timeout'  => 0.01,
		'blocking' => false,
		'headers'  => $headers,
		'body'     => wp_json_encode( $payload ),
	) );
}

add_action( 'admin_init', function () {
	register_setting( 'edi_integrationen', 'fahrschule_odoo_url', array( 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
	register_setting( 'edi_integrationen', 'fahrschule_odoo_key', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
} );
