<?php
/**
 * Fahrschule edi – Zentrale Medien-Registry
 *
 * Ein Teil der Medien liegt inzwischen lokal im Theme
 * (assets/images/ - echte Fotos/Logos aus der Bestandsaufnahme der alten
 * Seite, siehe SYSTEM-REPORT.md). Was noch fehlt (Videos, Duisburg-Foto,
 * Team-Fotos, Instagram), steht weiterhin extern bzw. leer - siehe die
 * einzelnen Kommentare unten und die Übersicht in SYSTEM-REPORT.md.
 *
 * Vorgehen für die verbliebenen externen/leeren Einträge:
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
	$blob  = 'https://hebbkx1anhila5yf.public.blob.vercel-storage.com/';
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

		/* --- Videos: keine echten Dateien vorhanden, weiterhin extern --- */
		'video_hero'    => $blob . 'edi-kurz-small-SLaSoq6tEW9JYNFnWtDQe2X8FtONmx.mp4',
		'video_team'    => $blob . 'Crowd_Edi-NvbxpPojXyhtrf61GkyqLMemg2CrVT.mp4',

		/* --- Standorte ---
		 * Essen und Gelsenkirchen sind über die alte Seite eindeutig
		 * zugeordnet (image-1.jpg = Gelsenkirchen, image-2.jpg = Essen,
		 * per Referenz in den jeweiligen alten Unterseiten). Für Duisburg
		 * lag in der Bestandsaufnahme kein Originalfoto vor (die alte
		 * Seite nutzte dort einen Elementor-Bildschnitt ohne
		 * wiederverwendbaren Dateinamen) - bleibt extern. */
		'loc_duisburg'      => $blob . 'Edi_Duisburg-ATnPCdmjJuowLN0cPpb271DTMePQym.png',
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

		/* --- Absolventen: keine echten Dateien vorhanden, weiterhin extern --- */
		'grad_auto'     => $blob . 'absolventmitAuto-832dQfDX2zIz0OntredMyEETCkLi4C.jpg',
		'grad_jung'     => $blob . 'jungerAbsolvent-FQTh1T6pWLGlwvOa02qhYvx7ra3mMV.jpg',
		'grad_motorrad' => $blob . 'absolventMotorrad-bxWRUbSOCozui7jIyKUaVZFywOJEMu.jpg',

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

		/* --- Instagram-Beiträge (aktuell Stockfotos von Unsplash) ---------- */
		'ig_1' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500&h=500&fit=crop',
		'ig_2' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500&h=500&fit=crop',
		'ig_3' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=500&h=500&fit=crop',
		'ig_4' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=500&h=500&fit=crop',
		'ig_5' => 'https://images.unsplash.com/photo-1553882900-f2b06423ff1d?w=500&h=500&fit=crop',
		'ig_6' => 'https://images.unsplash.com/photo-1552821206-7cb5e8e6b5d5?w=500&h=500&fit=crop',
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
	$external = 0;
	foreach ( fahrschule_media_map() as $value ) {
		if ( is_string( $value ) && ( false !== strpos( $value, 'vercel-storage.com' ) || false !== strpos( $value, 'unsplash.com' ) ) ) {
			$external++;
		}
	}
	if ( ! $external ) { return; }
	printf(
		'<div class="notice notice-warning"><p><strong>Fahrschule edi:</strong> %d Medien liegen noch auf externen Prototyp-Servern (Vercel/Unsplash). Bitte vor dem Livegang in die Mediathek übernehmen – siehe <code>inc/media.php</code>.</p></div>',
		(int) $external
	);
}
add_action( 'admin_notices', 'fahrschule_media_notice' );
