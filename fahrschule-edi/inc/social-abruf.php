<?php
/**
 * Social-Feed: Beitrag per Link einlesen - TikTok über oEmbed.
 *
 * Der Redakteur fügt nur den Video-Link ein. Titel und Vorschaubild holt
 * fs_social_abruf() beim Speichern im Editor - NIE beim Seitenaufruf. So geht
 * von Besuchern keine Anfrage (samt IP-Adresse) an TikTok, und die Seite
 * hängt nicht an deren Antwortzeit. Das Vorschaubild wird in die Mediathek
 * übernommen: TikToks Bild-URLs sind signiert und laufen ab, nur die lokale
 * Kopie hält. Titel und Attachment-ID landen in den Block-Attributen und
 * wandern so mit dem Seiteninhalt.
 *
 * Der gesamte Abruf steckt in EINER Funktion (fs_social_abruf). TikTok hat
 * „Embed Videos“ als abzulösen markiert; kommt ein Ersatz, betrifft er nur
 * diese Funktion. Bereits gefüllte Kacheln brechen dann nicht - ihr Cover
 * liegt lokal. bin/checks/refs.py sperrt Netz- und Mediathek-Aufrufe
 * außerhalb dieser Funktion.
 *
 * Instagram: Dessen oEmbed verlangt eine Facebook-App mit Token und ist per
 * Link-Einfügen nicht zu bedienen. Instagram-Links ergeben eine Marken-Kachel.
 *
 * Aufrufer:
 *   - REST fahrschule/v1/social-abruf (Editor: beim Einfügen eines Links
 *     und Knopf „Beiträge aktualisieren“),
 *   - wp_insert_post_data (beim Speichern: Zeilen mit Link, aber ohne
 *     Status werden nachgetragen).
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Liest einen Social-Link ein.
 *
 * @param string $url Link aus dem Editor.
 * @param bool   $neu true = Vorschaubild erneut holen, auch wenn schon eins da ist.
 * @return array [ plattform, link, titel, bild_id, status ] - Spaltenfolge des
 *               Wiederholfelds; status 'ok' oder der Grund des Scheiterns.
 */
function fs_social_abruf( $url, $neu = false ) {
	$url   = trim( (string) $url );
	$teile = wp_parse_url( $url );
	$host  = strtolower( isset( $teile['host'] ) ? $teile['host'] : '' );

	if ( preg_match( '/(^|\.)instagram\.com$/', $host ) ) {
		return array( 'instagram', $url, '', '', 'Instagram: wird nicht eingelesen (Token nötig) – Marken-Kachel mit Link' );
	}
	if ( '' === $url || ! preg_match( '/(^|\.)tiktok\.com$/', $host ) ) {
		return array( 'tiktok', $url, '', '', 'kein TikTok-Link' );
	}

	// 1. Normalisieren: Foto-Karussells lehnt der Endpunkt als /photo/ ab;
	//    Tracking-Parameter (?is_from_webapp=…) und Anker fallen weg.
	$pfad = str_replace( '/photo/', '/video/', isset( $teile['path'] ) ? $teile['path'] : '' );
	$link = 'https://' . $host . $pfad;
	$vid  = preg_match( '#/video/(\d+)#', $pfad, $m ) ? $m[1] : md5( $link );

	// Schon übernommen? Dann ohne Netz aus der Mediathek.
	if ( ! $neu ) {
		$da = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_fs_social_quelle', 'meta_value' => $link ) );
		if ( $da ) {
			$id = (int) ( is_object( $da[0] ) ? $da[0]->ID : $da[0] );
			return array( 'tiktok', $link, (string) get_post_meta( $id, '_fs_social_titel', true ), (string) $id, 'ok' );
		}
	}

	// 2. oEmbed: kein Schlüssel, kein Token.
	$antwort = wp_remote_get( 'https://www.tiktok.com/oembed?url=' . rawurlencode( $link ), array( 'timeout' => 8 ) );
	if ( is_wp_error( $antwort ) ) {
		return array( 'tiktok', $link, '', '', 'keine Verbindung: ' . $antwort->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $antwort );
	if ( 429 === $code ) {
		return array( 'tiktok', $link, '', '', 'zu viele Anfragen (HTTP 429) – später „Beiträge aktualisieren“' );
	}
	if ( 200 !== $code ) {
		return array( 'tiktok', $link, '', '', sprintf( 'privat, gelöscht oder kein Video (HTTP %d)', $code ) );
	}
	$daten = json_decode( (string) wp_remote_retrieve_body( $antwort ), true );
	$titel = is_array( $daten ) && isset( $daten['title'] ) ? trim( wp_strip_all_tags( (string) $daten['title'] ) ) : '';
	$bild  = is_array( $daten ) && isset( $daten['thumbnail_url'] ) ? (string) $daten['thumbnail_url'] : '';
	if ( '' === $bild ) {
		return array( 'tiktok', $link, $titel, '', 'TikTok liefert kein Vorschaubild' );
	}

	// 3. Vorschaubild in die Mediathek. Wie media_sideload_image(), aber mit
	//    eigenem Dateinamen: TikToks signierte URLs enden oft nicht auf .jpg,
	//    daran scheitert media_sideload_image().
	if ( ! function_exists( 'media_handle_sideload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$tmp = download_url( $bild, 15 );
	if ( is_wp_error( $tmp ) ) {
		return array( 'tiktok', $link, $titel, '', 'Vorschaubild nicht ladbar: ' . $tmp->get_error_message() );
	}
	$id = media_handle_sideload( array( 'name' => 'edi-tiktok-' . $vid . '.jpg', 'tmp_name' => $tmp ), 0, $titel );
	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
		return array( 'tiktok', $link, $titel, '', 'Vorschaubild nicht übernommen: ' . $id->get_error_message() );
	}
	update_post_meta( $id, '_fs_social_quelle', $link );
	update_post_meta( $id, '_fs_social_titel', $titel );
	update_post_meta( $id, '_wp_attachment_image_alt', $titel );

	return array( 'tiktok', $link, $titel, (string) $id, 'ok' );
}

/** REST: der Editor liest einen Link ein (beim Einfügen und per Knopf). */
function fahrschule_social_rest() {
	register_rest_route( 'fahrschule/v1', '/social-abruf', array(
		'methods'             => 'POST',
		'permission_callback' => static function () { return current_user_can( 'edit_posts' ); },
		'args'                => array(
			'url' => array( 'type' => 'string', 'required' => true ),
			'neu' => array( 'type' => 'boolean', 'default' => false ),
		),
		'callback'            => static function ( $req ) {
			return rest_ensure_response( fs_social_abruf( $req['url'], (bool) $req['neu'] ) );
		},
	) );
}
add_action( 'rest_api_init', 'fahrschule_social_rest' );

/**
 * Beim Speichern: Zeilen mit Link, aber ohne Status, nachtragen - falls der
 * Editor sie nicht schon eingelesen hat (z. B. Speichern direkt nach dem
 * Einfügen). Dank der Mediathek-Prüfung kostet ein zweites Speichern keinen
 * Abruf.
 */
function fahrschule_social_beim_speichern( $data, $postarr = array() ) {
	$inhalt = wp_unslash( (string) $data['post_content'] );
	if ( false === strpos( $inhalt, 'wp:fahrschule/instagram-slider' ) ) { return $data; }
	$geaendert = false;
	$durch     = static function ( $bloecke ) use ( &$durch, &$geaendert ) {
		foreach ( $bloecke as &$b ) {
			if ( 'fahrschule/instagram-slider' === $b['blockName'] && ! empty( $b['attrs']['items'] ) && is_array( $b['attrs']['items'] ) ) {
				foreach ( $b['attrs']['items'] as &$z ) {
					$z = array_values( (array) $z ) + array( '', '', '', '', '' );
					// Neues Format: Spalte 2 ist ein Link; ohne Status noch nicht eingelesen.
					if ( preg_match( '#^https?://#', (string) $z[1] ) && '' === (string) $z[4] ) {
						$z         = fs_social_abruf( $z[1] );
						$geaendert = true;
					}
				}
				unset( $z );
			}
			if ( ! empty( $b['innerBlocks'] ) ) { $b['innerBlocks'] = $durch( $b['innerBlocks'] ); }
		}
		unset( $b );
		return $bloecke;
	};
	$bloecke = $durch( parse_blocks( $inhalt ) );
	if ( $geaendert ) { $data['post_content'] = wp_slash( serialize_blocks( $bloecke ) ); }
	return $data;
}
add_filter( 'wp_insert_post_data', 'fahrschule_social_beim_speichern', 10, 2 );
