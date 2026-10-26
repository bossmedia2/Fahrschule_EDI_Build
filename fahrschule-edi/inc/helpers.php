<?php
/**
 * Fahrschule edi – Helfer-Funktionen
 * Inline-SVG-Icons (lucide-Stil), Block-Render-Wrapper, kleine Utilities.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Gibt ein Inline-SVG-Icon zurück (stroke = currentColor).
 * Ersetzt die lucide-react Icons aus dem React-Projekt.
 */
function fs_icon( $name, $size = 20, $extra_class = '' ) {
	$paths = array(
		'arrow-right'   => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
		'play'          => '<polygon points="5 3 19 12 5 21 5 3"/>',
		'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'          => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/>',
		'map-pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
		'clock'         => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
		'chevron-down'  => '<polyline points="6 9 12 15 18 9"/>',
		'chevron-left'  => '<polyline points="15 18 9 12 15 6"/>',
		'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
		'menu'          => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
		'x'             => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
		'star'          => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
		'heart'         => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
		'zap'           => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
		'users'         => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
		'truck'         => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
		'award'         => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
		'check-circle'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
		'graduation'    => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
		'navigation'    => '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',
		'instagram'     => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
		'tiktok'        => '<path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.1 1.75 2.9 2.9 0 0 1 2.31-4.64 2.88 2.88 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-.96-.1z"/>',
	);

	$inner = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
	// instagram ist ein Umriss-Icon (Kamera): gefüllt würde es ein volles Quadrat.
	$fill  = in_array( $name, array( 'play', 'star', 'tiktok', 'navigation', 'heart' ), true ) ? 'currentColor' : 'none';
	$stroke = 'currentColor';

	return sprintf(
		'<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="%s" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $extra_class ),
		(int) $size,
		(int) $size,
		esc_attr( $fill ),
		esc_attr( $stroke ),
		$inner // SVG path data – statisch, kein User-Input.
	);
}

/* =========================================================================
 * EDITIERBARKEIT: leere Felder sollen verschwinden, nicht leere Hüllen lassen
 *
 * Ein Attribut hat drei Zustände, die auseinandergehalten werden müssen:
 *   - nicht gesetzt  -> WordPress liefert die Vorgabe aus der Registrierung
 *   - gesetzt        -> der Redakteurswert gilt
 *   - leer gesetzt   -> der Redakteur hat das Feld BEWUSST geleert
 *
 * Der dritte Fall ist der, an dem das Theme bisher scheiterte: `''` lief
 * unverändert in `esc_html()` und hinterließ <h2></h2> oder eine leere
 * Badge. Darum liest Rendercode Felder nicht mehr direkt, sondern über
 * fs_has()/fs_el() - dann fällt mit dem Wert auch sein Element weg.
 * ====================================================================== */

/**
 * Wie wp_parse_args, aber ausdrücklich ohne Filtern: ein leerer Wert ist eine
 * Aussage („dieses Feld soll weg") und darf nicht zur Vorgabe zurückfallen.
 */
function fs_attrs( $a, $defaults = array() ) {
	return array_merge( $defaults, (array) $a );
}

/** Hat das Feld einen ausgebbaren Wert? Leerstring und reiner Leerraum zählen nicht. */
function fs_has( $d, $key ) {
	return isset( $d[ $key ] ) && '' !== trim( (string) $d[ $key ] );
}

/**
 * Gibt ein Element samt Inhalt aus - oder nichts, wenn der Inhalt leer ist.
 *
 * @param string $tag     Elementname, z. B. 'h2'.
 * @param string $value   Roher Text; wird escaped.
 * @param string $attr    Fertige Attribute, z. B. 'class="fs-eyebrow"'.
 * @param string $raw     Bereits sicheres HTML statt $value (z. B. aus fs_highlight()).
 */
function fs_el( $tag, $value, $attr = '', $raw = null ) {
	$inner = ( null !== $raw ) ? $raw : esc_html( (string) $value );
	if ( '' === trim( wp_strip_all_tags( (string) $inner ) ) && null === $raw ) { return ''; }
	if ( null !== $raw && '' === trim( wp_strip_all_tags( (string) $raw ) ) ) { return ''; }
	$tag = preg_replace( '/[^a-z0-9]/', '', strtolower( $tag ) );
	return sprintf( '<%1$s%2$s>%3$s</%1$s>', $tag, $attr ? ' ' . $attr : '', $inner );
}

/**
 * Zeilen eines Wiederholfelds in eine saubere Liste bringen.
 *
 * Der Block-Editor liefert Zeilen je nach Weg als Liste oder als Objekt mit
 * numerischen Schlüsseln; beides wird hier vereinheitlicht. Vollständig leere
 * Zeilen fallen weg - so löscht ein Redakteur einen Eintrag, indem er ihn
 * leert, und bekommt keine leere Karte im Frontend.
 *
 * @param mixed $value Attributwert.
 * @param int   $cols  Erwartete Spaltenzahl; fehlende Spalten werden ergänzt.
 * @return array Liste numerisch indizierter Zeilen.
 */
function fs_rows( $value, $cols = 0 ) {
	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		$value   = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
	}
	if ( ! is_array( $value ) ) { return array(); }

	$out = array();
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) { $row = array( $row ); }
		$row = array_values( $row );
		if ( $cols > 0 ) {
			$row = array_slice( array_pad( $row, $cols, '' ), 0, $cols );
		}
		// Zeile ganz leer? Dann hat der Redakteur sie gelöscht.
		$has = false;
		foreach ( $row as $cell ) {
			if ( '' !== trim( (string) $cell ) ) { $has = true; break; }
		}
		if ( $has ) { $out[] = $row; }
	}
	return $out;
}

/**
 * Gibt einen Container nur aus, wenn er Inhalt hat.
 *
 * Nötig, weil ein geleertes Feld zwar sein eigenes Element verliert, der
 * umgebende Kasten aber stehen bliebe - .fs-head bringt 3rem Abstand mit und
 * hinterlässt sonst eine sichtbare Lücke ohne Inhalt.
 */
function fs_wrap( $tag, $inner, $attr = '' ) {
	if ( '' === trim( (string) $inner ) ) { return ''; }
	$tag = preg_replace( '/[^a-z0-9]/', '', strtolower( $tag ) );
	return sprintf( '<%1$s%2$s>%3$s</%1$s>', $tag, $attr ? ' ' . $attr : '', $inner );
}

/** Mehrzeiligen Text als Absätze - oder nichts, wenn nichts da ist. */
function fs_paragraphs( $text, $attr = '' ) {
	$text = trim( (string) $text );
	if ( '' === $text ) { return ''; }
	$out = '';
	foreach ( preg_split( '/\n\s*\n/', $text ) as $p ) {
		$p = trim( $p );
		if ( '' === $p ) { continue; }
		$out .= fs_el( 'p', '', $attr, nl2br( esc_html( $p ) ) );
	}
	return $out;
}

/**
 * Button nur ausgeben, wenn Beschriftung UND Ziel gesetzt sind. Ein Knopf ohne
 * Beschriftung ist eine unsichtbare Tabfalle, einer ohne Ziel führt ins Leere.
 */
function fs_button( $label, $url, $class = 'fs-btn fs-btn--primary' ) {
	if ( '' === trim( (string) $label ) || '' === trim( (string) $url ) ) { return ''; }
	return sprintf(
		'<a class="%s" href="%s">%s</a>',
		esc_attr( $class ), esc_url( fs_link( $url ) ), esc_html( $label )
	);
}

/**
 * Rendert einen registrierten dynamischen Block mit optionalen Attributen.
 * Wird in den Seiten-Templates verwendet, damit die Seiten ohne manuelles
 * Einfügen im Editor sofort funktionieren.
 */
function fs_block( $name, $attrs = array() ) {
	return render_block(
		array(
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}

/** Mehrzeiligen Text mit Zeilenumbrüchen sicher ausgeben. */
function fs_nl2br( $text ) {
	return nl2br( esc_html( $text ) );
}

/**
 * Zentrale Hauptrufnummer (Standort Duisburg, zugleich allgemeine Nummer).
 *
 * Stand vorher zweimal wörtlich in der Navigation und ein drittes Mal in
 * inc/pdf-texts.php - eine Korrektur hätte die anderen Stellen nicht
 * mitgezogen. Für die strukturierten Daten gilt weiterhin die eigene,
 * standortgenaue Fassung in fahrschule_business_data() (inc/seo.php).
 *
 * @param string $format 'display' für die lesbare Schreibweise, 'link' für tel:.
 */
function fahrschule_phone( $format = 'display' ) {
	$number = apply_filters( 'fahrschule_phone', '0203 5785703' );
	return ( 'link' === $format ) ? 'tel:' . fs_tel( '+49' . ltrim( $number, '0' ) ) : $number;
}

/** Telefonnummer in tel:-Link säubern. */
function fs_tel( $number ) {
	return preg_replace( '/[^0-9+]/', '', $number );
}

/**
 * Reale Öffnungszeiten je Standort, aus der alten Website übernommen
 * (Kontaktseite, „Ansprechpartner und Öffnungszeiten"). EINE Quelle für alle
 * Ausgabeorte (fs_render_locations(), fs_render_location_hero()-Default,
 * inc/blocks-config.php) - vorher an mehreren Stellen unabhängig
 * hinterlegt, wodurch eine Korrektur die andere nicht mit aktualisierte.
 * Für inc/seo.php (JSON-LD) gilt weiterhin die eigene, strukturierte Fassung
 * in fahrschule_business_data() - diese Funktion liefert nur den
 * menschenlesbaren Text fürs Frontend.
 */
function fahrschule_location_hours_text( $city ) {
	$hours = array(
		'gelsenkirchen' => "Mo – Mi: 08:00 – 20:00 Uhr\nDo: 13:00 – 20:00 Uhr\nFr: 13:00 – 18:00 Uhr",
		'essen'         => "Mo – Do: 13:00 – 20:00 Uhr\nFr: 13:00 – 18:00 Uhr",
		'duisburg'      => "Mo – Do: 13:00 – 20:00 Uhr\nFr: 13:00 – 18:00 Uhr",
	);
	$key = sanitize_key( $city );
	return isset( $hours[ $key ] ) ? $hours[ $key ] : '';
}

/**
 * Hebt ein Wort innerhalb einer Überschrift farblich hervor.
 *
 * Ersetzt die zuvor 13-fach duplizierte str_replace-Logik. Wichtig: Es wird auf
 * dem BEREITS escapten Text gesucht, damit Sonderzeichen (&, ", ') zuverlässig
 * matchen und die Ausgabe sicher bleibt.
 *
 * @param string $heading   Vollständige Überschrift (Rohtext).
 * @param string $highlight Hervorzuhebendes Teilwort (Rohtext, optional).
 * @return string Sicheres HTML.
 */
function fs_highlight( $heading, $highlight = '' ) {
	$safe = esc_html( $heading );
	if ( '' === trim( (string) $highlight ) ) {
		return $safe;
	}
	$needle = esc_html( $highlight );
	if ( false === strpos( $safe, $needle ) ) {
		return $safe; // Wort nicht enthalten – unverändert ausgeben.
	}
	return str_replace( $needle, '<span class="fs-accent">' . $needle . '</span>', $safe );
}

/* -------------------------------------------------------------------------
 * Kontext für Blöcke, die von der geöffneten Seite abhängen
 * ---------------------------------------------------------------------- */

/**
 * Die Seite bzw. der Beitrag, auf dem gerade gerendert wird - im Frontend die
 * Hauptabfrage, in der Editor-Vorschau (REST-Block-Renderer mit post_id) der
 * bearbeitete Beitrag. Im Website-Editor (Vorlage ohne Seite) null.
 *
 * @return WP_Post|null
 */
function fs_context_post() {
	if ( is_singular() ) {
		$q = get_queried_object();
		return $q instanceof WP_Post ? $q : null;
	}
	if ( fs_is_editor_preview() && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
		return $GLOBALS['post'];
	}
	return null;
}

/** Läuft das Rendern für die Vorschau im Block- oder Website-Editor? */
function fs_is_editor_preview() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * Hinweis an Stelle eines Blocks, der ohne Seitenkontext nichts ausgeben
 * kann - NUR in der Editor-Vorschau. Im Frontend bleibt es bei '' (der
 * Abschnitt entfällt), damit nie ein Hinweistext auf der Website landet.
 */
function fs_editor_hint( $title, $text ) {
	if ( ! fs_is_editor_preview() ) { return ''; }
	return '<div class="fs-editor-hint"><strong>' . esc_html( $title ) . '</strong> ' . esc_html( $text ) . '</div>';
}

/*
 * Fläche je Abschnitt (Feld „Fläche“, an jedem Inhaltsblock).
 *
 * Vier Flächen: vollpink (Signaturpink, Vorgabe) · hellpink (Rosé) ·
 * leiste (kräftiges Pink, für schmale Bänder) · foto (Bild mit Abdunkler).
 * Das Feld setzt NUR den Kontext: data-flaeche am ersten <section> des
 * Blocks. Hintergrund und alle --c-*-Tokens kommen aus main.css
 * (section[data-flaeche=…]); die Regeln dort sind spezifischer als die
 * Grundfläche, die ein Block von sich aus mitbringt.
 */
const FS_FLAECHEN = array( 'vollpink', 'hellpink', 'leiste', 'foto' );

/** Feldwert normalisieren; brand/light sind die Werte bis Theme 3.0. */
function fs_flaeche_wert( $v ) {
	$alt = array( 'brand' => 'vollpink', 'light' => 'hellpink', 'deep' => 'leiste' );
	$v   = isset( $alt[ $v ] ) ? $alt[ $v ] : (string) $v;
	return in_array( $v, FS_FLAECHEN, true ) ? $v : 'vollpink';
}

/**
 * Setzt die gewählte Fläche auf den ersten <section> eines gerenderten Blocks.
 * Foto ohne auflösbares Bild fällt auf Vollpink zurück (mit Editor-Hinweis) -
 * nie eine leere, dunkle Fläche.
 */
function fs_flaeche_anwenden( $html, $a ) {
	if ( '' === trim( (string) $html ) ) { return $html; }
	$f     = fs_flaeche_wert( isset( $a['flaeche'] ) ? $a['flaeche'] : 'vollpink' );
	$style = '';
	$hint  = '';
	if ( 'foto' === $f ) {
		$bild = fs_media_by_slug( isset( $a['flaeche_bild'] ) ? trim( (string) $a['flaeche_bild'] ) : '' );
		if ( '' === $bild ) {
			$f    = 'vollpink';
			$hint = fs_editor_hint( 'Fläche „Foto“:', 'Kein Bild gefunden – bitte einen Medien-Slug eintragen. Bis dahin Vollpink.' );
		} else {
			$style = '--fs-flaeche-bild:url(' . esc_url( $bild ) . ')';
		}
	}
	$out = preg_replace_callback( '#<section\b([^>]*)>#', function ( $m ) use ( $f, $style ) {
		$attrs = preg_replace( '#\sdata-flaeche="[^"]*"#', '', $m[1] );
		if ( '' !== $style ) {
			if ( preg_match( '#\sstyle="([^"]*)"#', $attrs ) ) {
				$attrs = preg_replace( '#\sstyle="([^"]*)"#', ' style="$1;' . esc_attr( $style ) . '"', $attrs, 1 );
			} else {
				$attrs .= ' style="' . esc_attr( $style ) . '"';
			}
		}
		return '<section' . $attrs . ' data-flaeche="' . $f . '">';
	}, $html, 1 );
	return $hint . $out;
}

/** "A, B und C" - Aufzählung für Fließtext. */
function fs_aufzaehlung( $teile ) {
	$teile = array_values( array_filter( array_map( 'trim', (array) $teile ), 'strlen' ) );
	if ( count( $teile ) < 2 ) { return implode( '', $teile ); }
	$letzt = array_pop( $teile );
	return implode( ', ', $teile ) . ' und ' . $letzt;
}
