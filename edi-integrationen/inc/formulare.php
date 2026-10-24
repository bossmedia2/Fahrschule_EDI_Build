<?php
/**
 * edi-integrationen – Formulare über Contact Form 7 (+ Flamingo).
 *
 * Legt die vier Formulare der Website einmalig an und hält ihre IDs in
 * Optionen fest, damit der Formular-Block des Themes sie ohne Handarbeit
 * findet:
 *
 *   kontakt           /kontakt/
 *   anmeldung         /anmeldung/
 *   schnellanmeldung  Standortseiten (Standort kommt als Shortcode-Attribut)
 *   bewerbung         Stellenanzeigen (Stelle kommt als Shortcode-Attribut)
 *
 * Ein bestehendes Formular wird nie überschrieben - wer es im Backend
 * anpasst, behält seine Änderungen. Gelöschte Formulare legt der nächste
 * Admin-Aufruf neu an.
 *
 * Grundsätze:
 * - Keine E-Mail-Adresse im Formular: Den Empfänger setzt
 *   wpcf7_before_send_mail aus den Stammdaten des Themes
 *   (fahrschule_business_data(), inc/seo.php).
 * - Standort- und Klassenliste sind keine Kopien im Formulartext, sondern
 *   kommen über CF7-"data:"-Optionen live aus Stammdaten bzw. PDF F3.
 * - Spamschutz ohne Drittanbieter (kein reCAPTCHA, kein Turnstile): eigenes
 *   Honeypot-Feld [edi_honeypot] + wpcf7_spam.
 * - Flamingo speichert jede Einsendung von selbst, sobald es aktiv ist.
 *
 * @package Edi_Integrationen
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Name des Honeypot-Felds - darf kein Mensch ausfüllen. */
const EDI_HONEYPOT = 'edi_website';

/** Einwilligungen, Pflicht auf jedem Formular. */
function edi_formular_einwilligung() {
	$ds = function_exists( 'fs_url' ) ? fs_url( 'datenschutz' ) : home_url( '/datenschutz/' );
	return '<div class="fs-field fs-einwilligung">[acceptance datenschutz] Ich habe die <a href="' . esc_url( $ds ) . '">Datenschutzerklärung</a> gelesen und bin mit der Verarbeitung meiner Angaben zur Bearbeitung der Anfrage einverstanden. *[/acceptance]</div>'
		. "\n" . '<div class="fs-field fs-einwilligung">[acceptance alter] Ich bin mindestens 16 Jahre alt. Bin ich jünger, ist eine erziehungsberechtigte Person mit der Übermittlung einverstanden (Art. 8 DSGVO). *[/acceptance]</div>';
}

/** Ein Feld im Markup des Themes (.fs-field, Beschriftung über dem Feld). */
function edi_formular_feld( $label, $tag, $id ) {
	return '<div class="fs-field"><label for="' . $id . '">' . $label . '</label>' . $tag . '</div>';
}

/**
 * Die vier Formulare: Titel, Formular-Markup, Betreff, Mailtext.
 * Feldnamen sind für alle gleich, damit Empfängerlogik und Flamingo sie
 * einheitlich lesen.
 */
function edi_formulare() {
	$name    = edi_formular_feld( 'Name *', '[text* your-name id:fs-cf7-name autocomplete:name]', 'fs-cf7-name' );
	$mail    = edi_formular_feld( 'E-Mail *', '[email* your-email id:fs-cf7-email autocomplete:email]', 'fs-cf7-email' );
	$tel     = edi_formular_feld( 'Telefon', '[tel your-phone id:fs-cf7-phone autocomplete:tel]', 'fs-cf7-phone' );
	$tel_p   = edi_formular_feld( 'Telefon *', '[tel* your-phone id:fs-cf7-phone autocomplete:tel]', 'fs-cf7-phone' );
	$ort     = edi_formular_feld( 'Standort', '[select standort id:fs-cf7-standort include_blank data:edi_standorte]', 'fs-cf7-standort' );
	$ort_p   = edi_formular_feld( 'Standort *', '[select* standort id:fs-cf7-standort include_blank data:edi_standorte]', 'fs-cf7-standort' );
	$klasse  = edi_formular_feld( 'Führerscheinklasse', '[select klasse id:fs-cf7-klasse include_blank data:edi_klassen]', 'fs-cf7-klasse' );
	$msg     = edi_formular_feld( 'Nachricht *', '[textarea* your-message id:fs-cf7-message]', 'fs-cf7-message' );
	$msg_o   = edi_formular_feld( 'Nachricht', '[textarea your-message id:fs-cf7-message]', 'fs-cf7-message' );
	$hp      = '[edi_honeypot]';
	$ok      = edi_formular_einwilligung();
	$fuss    = "\n\n-- \nGesendet über das Formular „%s“ auf [_site_url]";

	return array(
		'kontakt' => array(
			'title'   => 'edi: Kontakt',
			'form'    => implode( "\n", array( $hp, $name, $mail, $tel, $ort, $msg, $ok, '[submit class:fs-btn class:fs-btn--lg class:fs-btn--primary class:fs-btn--block "Nachricht senden"]' ) ),
			'subject' => 'Kontaktanfrage – Fahrschule edi [standort]',
			'body'    => "Name: [your-name]\nE-Mail: [your-email]\nTelefon: [your-phone]\nStandort: [standort]\n\nNachricht:\n[your-message]" . sprintf( $fuss, 'Kontakt' ),
		),
		'anmeldung' => array(
			'title'   => 'edi: Anmeldung',
			'form'    => implode( "\n", array( $hp, $name, $mail, $tel_p, $ort_p, $klasse, $msg_o, $ok, '[submit class:fs-btn class:fs-btn--lg class:fs-btn--primary class:fs-btn--block "Anmeldung absenden"]' ) ),
			'subject' => 'Anmeldung – Fahrschule edi [standort]',
			'body'    => "Name: [your-name]\nE-Mail: [your-email]\nTelefon: [your-phone]\nStandort: [standort]\nFührerscheinklasse: [klasse]\n\nNachricht:\n[your-message]" . sprintf( $fuss, 'Anmeldung' ),
		),
		'schnellanmeldung' => array(
			'title'   => 'edi: Schnellanmeldung (Standort)',
			// Standort kommt aus dem Shortcode: [contact-form-7 id="…" standort="Essen"]
			'form'    => implode( "\n", array( $hp, '[hidden standort default:shortcode_attr]', $name, $mail, $tel, $klasse, $ok, '[submit class:fs-btn class:fs-btn--primary-white class:fs-btn--block "Kostenloses Beratungsgespräch"]' ) ),
			'subject' => 'Schnellanmeldung – Fahrschule edi [standort]',
			'body'    => "Standort: [standort]\nName: [your-name]\nE-Mail: [your-email]\nTelefon: [your-phone]\nFührerscheinklasse: [klasse]" . sprintf( $fuss, 'Schnellanmeldung' ),
		),
		'bewerbung' => array(
			'title'   => 'edi: Bewerbung',
			// Stelle kommt aus dem Shortcode (Vorlage single-job_listing).
			'form'    => implode( "\n", array( $hp, '[hidden stelle default:shortcode_attr]', '[hidden stelle_id default:shortcode_attr]', $name, $mail, $tel, $ort,
				edi_formular_feld( 'Lebenslauf / Unterlagen (PDF, höchstens 10 MB)', '[file bewerbung id:fs-cf7-datei limit:10mb filetypes:pdf]', 'fs-cf7-datei' ),
				$msg_o, $ok, '[submit class:fs-btn class:fs-btn--lg class:fs-btn--primary class:fs-btn--block "Bewerbung senden"]' ) ),
			'subject' => 'Bewerbung: [stelle] – Fahrschule edi',
			'body'    => "Stelle: [stelle] (ID [stelle_id])\nName: [your-name]\nE-Mail: [your-email]\nTelefon: [your-phone]\nStandort: [standort]\n\nNachricht:\n[your-message]" . sprintf( $fuss, 'Bewerbung' ),
			'attach'  => '[bewerbung]',
		),
	);
}

/**
 * Legt fehlende Formulare an. Bestehende (Option gesetzt und Formular
 * vorhanden) bleiben unangetastet.
 *
 * @return array<string,int> Art => Formular-ID
 */
function edi_formulare_anlegen() {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) { return array(); }
	$ids = array();
	foreach ( edi_formulare() as $art => $f ) {
		$id = (int) get_option( 'edi_cf7_' . $art, 0 );
		if ( $id && wpcf7_contact_form( $id ) ) { $ids[ $art ] = $id; continue; }

		$cf   = WPCF7_ContactForm::get_template( array( 'title' => $f['title'] ) );
		$mail = $cf->prop( 'mail' );
		$mail['subject']            = $f['subject'];
		$mail['body']               = $f['body'];
		$mail['recipient']          = '[_site_admin_email]'; // wird beim Senden aus den Stammdaten ersetzt
		$mail['additional_headers'] = 'Reply-To: [your-name] <[your-email]>';
		$mail['attachments']        = isset( $f['attach'] ) ? $f['attach'] : '';
		$mail['use_html']           = false;
		$cf->set_properties( array(
			'form'     => $f['form'],
			'mail'     => $mail,
			'mail_2'   => array_merge( (array) $cf->prop( 'mail_2' ), array( 'active' => false ) ),
			'messages' => array_merge( (array) $cf->prop( 'messages' ), array(
				'mail_sent_ok'         => 'Danke! Ihre Nachricht ist bei uns angekommen. Wir melden uns bei Ihnen.',
				'mail_sent_ng'         => 'Ihre Nachricht konnte nicht gesendet werden. Bitte rufen Sie uns an oder versuchen Sie es später erneut.',
				'validation_error'     => 'Bitte prüfen Sie die markierten Felder.',
				'accept_terms'         => 'Bitte bestätigen Sie beide Einwilligungen.',
				'invalid_required'     => 'Bitte füllen Sie dieses Feld aus.',
				'upload_file_too_large' => 'Die Datei ist größer als 10 MB.',
				'upload_file_type_invalid' => 'Bitte laden Sie eine PDF-Datei hoch.',
				'spam'                 => 'Ihre Nachricht wurde als Spam erkannt.',
			) ),
		) );
		$cf->save();
		if ( $cf->id() ) {
			update_option( 'edi_cf7_' . $art, (int) $cf->id(), false );
			$ids[ $art ] = (int) $cf->id();
		}
	}
	return $ids;
}
// Bei Aktivierung (edi-integrationen.php) und im Admin, falls CF7 später
// dazukommt oder ein Formular gelöscht wurde.
add_action( 'admin_init', 'edi_formulare_anlegen' );

/** Formular-ID einer Art - für den Formular-Block des Themes. */
function edi_formular_id( $art ) {
	$id = (int) get_option( 'edi_cf7_' . sanitize_key( $art ), 0 );
	return ( $id && function_exists( 'wpcf7_contact_form' ) && wpcf7_contact_form( $id ) ) ? $id : 0;
}

/* -------------------------------------------------------------------------
 * Dynamische Listen (CF7 "data:"-Option) - eine Quelle statt Kopie
 * ---------------------------------------------------------------------- */
add_filter( 'wpcf7_form_tag_data_option', function ( $data, $options, $args ) {
	if ( in_array( 'edi_standorte', (array) $options, true ) && function_exists( 'fahrschule_business_data' ) ) {
		$data = array_merge( (array) $data, array_values( array_column( fahrschule_business_data()['locations'], 'city' ) ) );
	}
	if ( in_array( 'edi_klassen', (array) $options, true ) && function_exists( 'fahrschule_pdf' ) ) {
		$k = array();
		foreach ( fahrschule_pdf( 'f3' )['groups'] as $gruppe => $klassen ) {
			foreach ( $klassen as $kl ) { $k[] = 'Klasse ' . $kl; }
		}
		$data = array_merge( (array) $data, $k );
	}
	return $data;
}, 10, 3 );

/** Shortcode-Attribute, die die Formulare als versteckte Felder lesen. */
add_filter( 'shortcode_atts_wpcf7', function ( $out, $pairs, $atts ) {
	foreach ( array( 'standort', 'stelle', 'stelle_id' ) as $k ) {
		if ( isset( $atts[ $k ] ) ) { $out[ $k ] = sanitize_text_field( $atts[ $k ] ); }
	}
	return $out;
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Empfänger aus den Stammdaten
 * ---------------------------------------------------------------------- */

/**
 * Empfänger für einen Standort: dessen Adresse aus inc/seo.php, sonst die
 * allgemeine. Nur bekannte Standorte - ein manipulierter Feldwert landet
 * bei info@, nie bei einer fremden Adresse.
 */
function edi_formular_empfaenger( $standort ) {
	if ( ! function_exists( 'fahrschule_business_data' ) ) {
		return get_option( 'admin_email' );
	}
	$b   = fahrschule_business_data();
	$key = sanitize_key( (string) $standort );
	return ! empty( $b['locations'][ $key ]['email'] ) ? $b['locations'][ $key ]['email'] : $b['email'];
}

add_action( 'wpcf7_before_send_mail', function ( $contact_form, &$abort = false, $submission = null ) {
	$arten = array();
	foreach ( array_keys( edi_formulare() ) as $art ) { $arten[ (int) get_option( 'edi_cf7_' . $art, 0 ) ] = $art; }
	if ( ! isset( $arten[ (int) $contact_form->id() ] ) ) { return; } // fremdes Formular: nicht anfassen
	if ( ! $submission && class_exists( 'WPCF7_Submission' ) ) { $submission = WPCF7_Submission::get_instance(); }
	$posted   = $submission ? (array) $submission->get_posted_data() : array();
	$standort = isset( $posted['standort'] ) ? $posted['standort'] : '';
	if ( is_array( $standort ) ) { $standort = reset( $standort ); }
	$mail              = $contact_form->prop( 'mail' );
	$mail['recipient'] = edi_formular_empfaenger( $standort );
	$contact_form->set_properties( array( 'mail' => $mail ) );
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Spamschutz ohne Drittanbieter: Honeypot
 * ---------------------------------------------------------------------- */
add_action( 'wpcf7_init', function () {
	wpcf7_add_form_tag( 'edi_honeypot', function () {
		return '<span class="fs-hp" aria-hidden="true"><label>Bitte leer lassen'
			. '<input type="text" name="' . EDI_HONEYPOT . '" value="" tabindex="-1" autocomplete="off"></label></span>';
	} );
} );

add_filter( 'wpcf7_spam', function ( $spam ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- CF7 prüft selbst
	return $spam || ! empty( $_POST[ EDI_HONEYPOT ] );
} );
