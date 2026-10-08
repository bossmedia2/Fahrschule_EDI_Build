<?php
/**
 * Fahrschule edi – Zentrale Medien-Registry
 *
 * Alle hinterlegten Medien liegen lokal im Theme (assets/images/ - echte
 * Fotos/Logos aus der Bestandsaufnahme der alten Seite, siehe
 * docs/SYSTEM-REPORT.md). Was noch fehlt (Videos, Duisburg-Foto, Team-Fotos,
 * Absolventen, Instagram), steht bewusst LEER statt auf einem fremden Server.
 *
 * Frühere Fassungen verwiesen hier auf einen Vercel-Prototyp-Speicher und auf
 * Unsplash. Beides sind Abrufe bei Dritten ohne Einwilligung (DSGVO) und sie
 * brechen, sobald der Prototyp abgeschaltet wird. Blöcke ohne Medium blenden
 * sich über fs_has_media() sauber aus - das ist der bessere Zustand.
 *
 * Vorgehen für die leeren Einträge:
 *   1. Datei in die WordPress-Mediathek hochladen.
 *   2. Unten je Schlüssel die neue URL eintragen ODER – besser – die
 *      Attachment-ID hinterlegen: 'logo' => 42
 *   3. Erledigt. Kein weiterer Codeeingriff nötig, da sämtliche Ausgaben
 *      über fs_media() laufen.
 *
 * Alternativ lässt sich die Liste von außen überschreiben:
 *   add_filter( 'fahrschule_media', function ( $m ) { $m['logo'] = 123; return $m; } );
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Zentrale Liste aller Medien. Werte: URL (string) oder Attachment-ID (int). */
function fahrschule_media_map() {
	$local = get_template_directory_uri() . '/assets/images/';

	return apply_filters( 'fahrschule_media', array(

		/* --- Marke (lokal, aus der alten Seite extrahiert) --- */
		'logo'          => $local . 'logo-edi.png',
		'logo_certqua'  => $local . 'logo-certqua.png',
		// Trat auf jeder Seite der alten Website neben logo_certqua auf -
		// vermutlich eine lokale Kooperation/Partnerschaft (Standort
		// Gelsenkirchen). Noch ohne eigenen Platz im neuen Theme, siehe
		// SYSTEM-REPORT.md.
		'logo_schalke'  => $local . 'logo-schalke.svg',

		/* --- Videos: noch keine eigenen Dateien vorhanden --- */
		'video_hero'    => '',
		'video_team'    => '',

		/* --- Standorte ---
		 * Essen und Gelsenkirchen sind über die alte Seite eindeutig
		 * zugeordnet (image-1.jpg = Gelsenkirchen, image-2.jpg = Essen,
		 * per Referenz in den jeweiligen alten Unterseiten). Für Duisburg
		 * lag in der Bestandsaufnahme kein Originalfoto vor (die alte
		 * Seite nutzte dort einen Elementor-Bildschnitt ohne
		 * wiederverwendbaren Dateinamen) - bleibt extern. */
		'loc_duisburg'      => '',
		'loc_essen'         => $local . 'loc-essen.jpg',
		'loc_gelsenkirchen' => $local . 'loc-gelsenkirchen.jpg',

		/* --- Fuhrpark (lokal) --- */
		'fleet_pkw'      => $local . 'fleet-pkw.jpg',
		'fleet_lkw'      => $local . 'fleet-lkw.jpg',
		'fleet_motorrad' => $local . 'fleet-motorrad.jpg',
		'fleet_bus'      => $local . 'fleet-bus.jpg',

		/* --- Räumlichkeiten (lokal) --- */
		'fac_klassenraum' => $local . 'fac-klassenraum.jpg',
		'fac_empfang'     => $local . 'fac-empfang.jpg',
		'fac_buero'       => $local . 'fac-buero.jpg',

		/* --- Absolventen: noch keine eigenen Fotos vorhanden --- */
		'grad_auto'     => '',
		'grad_jung'     => '',
		'grad_motorrad' => '',

		/* --- Team: noch keine Fotos vorhanden ---------------------------------
		 * Das PDF (Abschnitt F9) sieht je Gruppe Fotos vor: Büro &
		 * Ansprechpartner, Geschäftsleitung, Dozenten & Ausbilder – jeweils
		 * für Essen, Gelsenkirchen und Duisburg. Solange hier nichts
		 * hinterlegt ist, zeigt der Team-Block die Initialen der Gruppe –
		 * bewusst, statt ein fremdes Stockfoto.
		 * ------------------------------------------------------------------ */
		'team_buero'             => '',
		'team_geschaeftsleitung' => '',
		'team_dozenten'          => '',

		/* --- Instagram-Beiträge: bewusst leer -----------------------------
		 * Hier standen sechs Unsplash-Stockfotos ohne Bezug zur Fahrschule.
		 * Der Instagram-Block blendet sich aus, solange nichts hinterlegt ist. */
		'ig_1' => '',
		'ig_2' => '',
		'ig_3' => '',
		'ig_4' => '',
		'ig_5' => '',
		'ig_6' => '',
	) );
}

/**
 * Liefert die URL eines Mediums.
 * Ist eine Attachment-ID hinterlegt, wird die Mediathek-URL erzeugt.
 *
 * @param string $key      Schlüssel aus der Registry.
 * @param string $fallback Ersatz-URL, falls nichts hinterlegt ist.
 */
function fs_media( $key, $fallback = '' ) {
	$map = fahrschule_media_map();
	if ( ! isset( $map[ $key ] ) || '' === $map[ $key ] ) {
		return $fallback;
	}
	$value = $map[ $key ];

	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_url( (int) $value );
		return $url ? $url : $fallback;
	}
	return (string) $value;
}

/** Prüft, ob ein Medium hinterlegt ist (z. B. für Team-Fotos mit Initialen-Fallback). */
function fs_has_media( $key ) {
	return '' !== fs_media( $key );
}

/**
 * Hinweis im Adminbereich, solange Medien auf externen Prototyp-Speichern liegen.
 */
function fahrschule_media_notice() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$home     = wp_parse_url( home_url(), PHP_URL_HOST );
	$external = 0;
	foreach ( fahrschule_media_map() as $value ) {
		if ( ! is_string( $value ) || 0 !== strpos( $value, 'http' ) ) { continue; }
		$host = wp_parse_url( $value, PHP_URL_HOST );
		if ( $host && $host !== $home ) { $external++; }
	}
	if ( ! $external ) { return; }
	printf(
		'<div class="notice notice-warning"><p><strong>Fahrschule edi:</strong> %d Medien werden von fremden Servern geladen. Das sind Drittabrufe ohne Einwilligung (DSGVO). Bitte in die Mediathek übernehmen – siehe <code>inc/media.php</code>.</p></div>',
		(int) $external
	);
}
add_action( 'admin_notices', 'fahrschule_media_notice' );
