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

	return apply_filters( 'fahrschule_media', array(

		/* --- Marke (lokal, aus der alten Seite extrahiert) --- */
		'logo'          => 'edi-logo',
		'logo_certqua'  => 'edi-partner-certqua',
		// Trat auf jeder Seite der alten Website neben logo_certqua auf -
		// vermutlich eine lokale Kooperation/Partnerschaft (Standort
		// Gelsenkirchen). Noch ohne eigenen Platz im neuen Theme, siehe
		// SYSTEM-REPORT.md.
		'logo_schalke'  => 'edi-partner-schalke',

		/* --- Videos (Mediathek) ---
		 * Hero: MP4 (Pflicht), WebM (optional, kleiner) und ein Standbild -
		 * es ist Poster, Hintergrund, Editor-Vorschau und das Bild bei
		 * reduzierter Bewegung. Ohne beides zeigt der Hero Vollpink. */
		'video_hero'      => 'edi-video-hero',
		'video_hero_webm' => 'edi-video-hero-webm',
		'video_hero_bild' => 'edi-video-hero-standbild',
		'video_team'      => 'edi-video-team',

		/* --- Standorte ---
		 * Essen und Gelsenkirchen sind über die alte Seite eindeutig
		 * zugeordnet (image-1.jpg = Gelsenkirchen, image-2.jpg = Essen,
		 * per Referenz in den jeweiligen alten Unterseiten). Für Duisburg
		 * lag in der Bestandsaufnahme kein Originalfoto vor (die alte
		 * Seite nutzte dort einen Elementor-Bildschnitt ohne
		 * wiederverwendbaren Dateinamen) - bleibt extern. */
		'loc_duisburg'      => 'edi-standort-duisburg',
		'loc_essen'         => 'edi-standort-essen',
		'loc_gelsenkirchen' => 'edi-standort-gelsenkirchen',

		/* --- Fuhrpark (lokal) --- */
		'fleet_pkw'      => 'edi-fuhrpark-pkw',
		'fleet_lkw'      => 'edi-fuhrpark-lkw',
		'fleet_motorrad' => 'edi-fuhrpark-motorrad',
		'fleet_bus'      => 'edi-fuhrpark-bus',

		/* --- Räumlichkeiten (lokal) --- */
		'fac_klassenraum' => 'edi-raum-klassenraum',
		'fac_empfang'     => 'edi-raum-empfang',
		'fac_buero'       => 'edi-raum-buero',

		/* --- Absolventen: noch keine eigenen Fotos vorhanden --- */
		'grad_auto'     => 'edi-galerie-absolventen-001',
		'grad_jung'     => 'edi-galerie-absolventen-002',
		'grad_motorrad' => 'edi-galerie-absolventen-003',

		/* --- Team: kein Gruppenfoto je PDF-Gruppe -------------------------
		 * Jede Person hält ihr eigenes Foto (edi-team-<vorname>, Zweitfoto
		 * -b), gepflegt im Fahrlehrer-Finder. Die PDF-Gruppen (F9) sind dort
		 * eine Spalte - bis 2.x standen hier drei leere Gruppenfoto-Schlüssel. */

		/* --- Social-Feed: kein fester Schlüssel mehr ----------------------
		 * Jede Kachel des Social-Feeds nennt ihr Bild selbst (Medien-Slug wie
		 * edi-social-001, Spalte im Wiederholfeld). Bis 3.0 standen hier die
		 * leeren Schlüssel ig_1 bis ig_6 (davor Unsplash-Platzhalter). */
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
	return fs_media_wert( $map[ $key ], $fallback );
}

/**
 * Einen Medienwert auflösen - aus der Medienliste oder aus einer Zelle eines
 * Wiederholfelds: Attachment-ID (Mediathek-Auswahl, seit 3.4), URL bzw.
 * absoluter Pfad, oder Datei-Slug (Mediathek, dann Theme). Gespeicherte
 * Werte werden nie umgeschrieben - alle drei Formen lösen weiter auf.
 */
function fs_media_wert( $value, $fallback = '' ) {
	if ( '' === $value || null === $value ) { return $fallback; }

	// Ausdrückliche Attachment-ID.
	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_url( (int) $value );
		return $url ? $url : $fallback;
	}
	$value = (string) $value;

	// Ausdrückliche URL oder absoluter Pfad.
	if ( 0 === strpos( $value, 'http' ) || 0 === strpos( $value, '/' ) ) {
		return $value;
	}

	// Sonst ist der Wert ein Dateinamen-Schlüssel, der in der Mediathek
	// nachgeschlagen wird - siehe fs_media_by_slug().
	return fs_media_by_slug( $value, $fallback );
}

/**
 * Bild über seinen Dateinamen auflösen.
 *
 * WordPress leitet aus dem Dateinamen den Slug des Anhangs ab. Genau den
 * schlägt das Theme nach: wer eine Datei nach Schema in die Mediathek lädt,
 * sieht sie an ihrem Platz, ohne sie irgendwo zuzuordnen. Erst wenn in der
 * Mediathek nichts liegt, greift eine gleichnamige Datei im Theme; gibt es
 * auch die nicht, bleibt der Wert leer und der Block blendet sich aus.
 *
 * Die Trefferliste wird je Aufruf zwischengespeichert - eine Seite fragt
 * dieselben Schlüssel mehrfach ab.
 *
 * @param string $slug     Dateiname ohne Endung, z. B. 'edi-kurs-klasse-b'.
 * @param string $fallback Rückgabe, wenn nichts gefunden wird.
 */
function fs_media_by_slug( $slug, $fallback = '' ) {
	static $cache = array();

	$slug = sanitize_title( $slug );
	if ( '' === $slug ) { return $fallback; }
	if ( isset( $cache[ $slug ] ) ) { return $cache[ $slug ] ? $cache[ $slug ] : $fallback; }

	$url = '';

	// 1) Mediathek - der Weg, den der Kunde selbst bedienen kann.
	$attachment = get_page_by_path( $slug, OBJECT, 'attachment' );
	if ( $attachment ) {
		$url = (string) wp_get_attachment_url( $attachment->ID );
	}

	// 2) Mitgeliefertes Bild im Theme (Logo, Partnerlogos, Favicons).
	if ( '' === $url ) {
		foreach ( array( 'webp', 'jpg', 'png', 'svg', 'mp4', 'webm' ) as $ext ) {
			if ( file_exists( get_template_directory() . '/assets/images/' . $slug . '.' . $ext ) ) {
				$url = get_template_directory_uri() . '/assets/images/' . $slug . '.' . $ext;
				break;
			}
		}
	}

	$cache[ $slug ] = $url;
	return $url ? $url : $fallback;
}

/**
 * Bild einer Kurs-/Klassenseite.
 *
 * Der Teil nach 'edi-kurs-' ist exakt der Seiten-Slug. Eine neue Klassenseite
 * braucht damit keinen Eintrag in der Registry: Seite anlegen, Bild nach
 * Schema hochladen, fertig.
 *
 * @param string $page_slug Seiten-Slug, leer = aktuelle Seite.
 */
function fs_media_kurs( $page_slug = '' ) {
	if ( '' === $page_slug ) {
		$key  = fahrschule_current_key();
		$map  = fahrschule_page_map();
		$page_slug = ( $key && isset( $map[ $key ]['slug'] ) ) ? $map[ $key ]['slug'] : '';
	}
	if ( '' === $page_slug ) { return ''; }
	return fs_media_by_slug( 'edi-kurs-' . $page_slug );
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
	// Hero: Video und Standbild gehören in die Mediathek. Fehlt beides,
	// zeigt die Startseite nur Vollpink statt Bild.
	$fehlt = array();
	foreach ( array( 'video_hero' => 'edi-video-hero.mp4', 'video_hero_bild' => 'edi-video-hero-standbild (JPG/WebP)' ) as $key => $datei ) {
		if ( '' === fs_media( $key ) ) { $fehlt[] = $datei; }
	}
	if ( $fehlt ) {
		printf(
			'<div class="notice notice-warning"><p><strong>Fahrschule edi:</strong> Für den Startseiten-Hero fehlt in der Mediathek: %s. Datei mit genau diesem Namen hochladen – sie erscheint dann automatisch.</p></div>',
			esc_html( implode( ', ', $fehlt ) )
		);
	}
	if ( ! $external ) { return; }
	printf(
		'<div class="notice notice-warning"><p><strong>Fahrschule edi:</strong> %d Medien werden von fremden Servern geladen. Das sind Drittabrufe ohne Einwilligung (DSGVO). Bitte in die Mediathek übernehmen – siehe <code>inc/media.php</code>.</p></div>',
		(int) $external
	);
}
add_action( 'admin_notices', 'fahrschule_media_notice' );
