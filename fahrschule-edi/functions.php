<?php
/**
 * Fahrschule edi – functions.php
 *
 * Enthält die ZENTRALE Registrierungsfunktion für alle Gutenberg-Blöcke und
 * Block-Patterns. Es werden bewusst KEINE Einzelverzeichnisse pro Block angelegt –
 * stattdessen beschreibt EIN strukturiertes Array (inc/blocks-config.php) alle
 * Blöcke, die per foreach-Schleife registriert werden.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'FAHRSCHULE_VERSION', '3.0.0' ); // bei Änderungen an assets/css|js erhöhen (Cache-Busting über ?ver=)
define( 'FAHRSCHULE_DIR', get_template_directory() );
define( 'FAHRSCHULE_URI', get_template_directory_uri() );

require_once FAHRSCHULE_DIR . '/inc/helpers.php';
require_once FAHRSCHULE_DIR . '/inc/pdf-texts.php';   // Kundentexte (PDF) – einzige Quelle für Blöcke + Editor-Defaults
require_once FAHRSCHULE_DIR . '/inc/media.php';
require_once FAHRSCHULE_DIR . '/inc/pages.php';
require_once FAHRSCHULE_DIR . '/inc/render-callbacks.php';
require_once FAHRSCHULE_DIR . '/inc/blocks-config.php';
require_once FAHRSCHULE_DIR . '/inc/seo.php';
require_once FAHRSCHULE_DIR . '/inc/forms.php';
require_once FAHRSCHULE_DIR . '/inc/security.php';

/* =========================================================================
 * 1) THEME-SETUP
 * ====================================================================== */
function fahrschule_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );

	// Editor-Stile landen im iframe von Block- UND Website-Editor. editor.css
	// macht dort sichtbar, was im Frontend erst JavaScript einblendet
	// (.fs-fade-up), und blendet Cookie-Banner und TikTok-Badge aus.
	$editor_styles = array( 'assets/css/main.css', 'assets/css/editor.css' );
	if ( file_exists( FAHRSCHULE_DIR . '/assets/fonts/fonts.css' ) ) {
		array_unshift( $editor_styles, 'assets/fonts/fonts.css' );
	}
	add_editor_style( $editor_styles );
}
add_action( 'after_setup_theme', 'fahrschule_setup' );

/* =========================================================================
 * 2) ASSETS (Frontend + Editor)
 *
 * WICHTIG: Styles/Skripte werden auf 'init' REGISTRIERT (nicht nur enqueued).
 * Nur so existieren die Handles auch im Block-Editor / Admin-Kontext, wo
 * register_block_type() sie über 'style' und 'editor_style' referenziert.
 * ====================================================================== */
function fahrschule_register_assets() {
	$deps = array();

	// Geist lokal, nur wenn die Dateien wirklich vorliegen (siehe assets/fonts/fonts.css).
	if ( file_exists( FAHRSCHULE_DIR . '/assets/fonts/geist-400.woff2' ) ) {
		wp_register_style( 'fahrschule-fonts', FAHRSCHULE_URI . '/assets/fonts/fonts.css', array(), FAHRSCHULE_VERSION );
		$deps[] = 'fahrschule-fonts';
	}

	wp_register_style( 'fahrschule-main', FAHRSCHULE_URI . '/assets/css/main.css', $deps, FAHRSCHULE_VERSION );
	wp_register_script( 'fahrschule-frontend', FAHRSCHULE_URI . '/assets/js/frontend.js', array(), FAHRSCHULE_VERSION, true );
	wp_register_script(
		'fahrschule-editor',
		FAHRSCHULE_URI . '/assets/js/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data' ),
		FAHRSCHULE_VERSION,
		true
	);
}
add_action( 'init', 'fahrschule_register_assets', 5 );

/* Die Fließtext-Schnittart vorladen, wenn sie vorliegt - ohne preload
   entdeckt der Browser die woff2 erst nach dem CSS-Parsing (kurzer
   Schriftwechsel beim ersten Aufbau). */
function fahrschule_font_preload() {
	$file = FAHRSCHULE_DIR . '/assets/fonts/geist-400.woff2';
	if ( ! file_exists( $file ) ) { return; }
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( FAHRSCHULE_URI . '/assets/fonts/geist-400.woff2' )
	);
}
add_action( 'wp_head', 'fahrschule_font_preload', 1 );

function fahrschule_assets() {
	wp_enqueue_style( 'fahrschule-main' );
	wp_enqueue_script( 'fahrschule-frontend' );
}
add_action( 'wp_enqueue_scripts', 'fahrschule_assets' );

/**
 * Editor-Skript: EINE Datei registriert generisch die edit()-Oberfläche aller Blöcke
 * (ServerSideRender-Vorschau + InspectorControls aus den 'fields' der Konfiguration).
 */
function fahrschule_editor_assets() {
	// Einziger Weg, auf dem editor.js lädt - auch im Website-Editor, wo ein
	// 'editor_script' an register_block_type() nicht zuverlässig greift.
	wp_enqueue_script( 'fahrschule-editor' );

	// Block-Meta für den generischen Editor zusammenstellen.
	$config = fahrschule_blocks_config();
	$editor_blocks = array();
	foreach ( $config as $name => $cfg ) {
		$fields = array();
		if ( ! empty( $cfg['fields'] ) ) {
			foreach ( $cfg['fields'] as $key => $f ) {
				$fields[] = array(
					'key'     => $key,
					'label'   => $f['label'],
					'control' => $f['control'],
					'default' => $f['default'],
					'options' => isset( $f['options'] ) ? $f['options'] : array(),
					'columns' => isset( $f['columns'] ) ? $f['columns'] : array(),
				);
			}
		}
		$editor_blocks[] = array(
			'name'        => $name,
			'title'       => $cfg['title'],
			'description' => isset( $cfg['description'] ) ? $cfg['description'] : '',
			'icon'        => isset( $cfg['icon'] ) ? $cfg['icon'] : 'block-default',
			'keywords'    => isset( $cfg['keywords'] ) ? $cfg['keywords'] : array(),
			'category'    => 'fahrschule',
			'fields'      => $fields,
		);
	}
	wp_localize_script( 'fahrschule-editor', 'FahrschuleBlocks', $editor_blocks );
}
add_action( 'enqueue_block_editor_assets', 'fahrschule_editor_assets' );

/* =========================================================================
 * 3) EIGENE BLOCK-/PATTERN-KATEGORIE
 * ====================================================================== */
function fahrschule_block_categories( $categories ) {
	return array_merge(
		array( array(
			'slug'  => 'fahrschule',
			'title' => __( 'Fahrschule edi', 'fahrschule-edi' ),
			'icon'  => 'car',
		) ),
		$categories
	);
}
add_filter( 'block_categories_all', 'fahrschule_block_categories' );

/* =========================================================================
 * 4) >>> ZENTRALE REGISTRIERUNG ALLER BLÖCKE & PATTERNS <<<
 *     Eine einzige Funktion, eine foreach-Schleife – wie im Briefing gefordert.
 * ====================================================================== */
function fahrschule_register_blocks_and_patterns() {

	// Pattern-Kategorie einmalig anlegen.
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category( 'fahrschule', array( 'label' => __( 'Fahrschule edi', 'fahrschule-edi' ) ) );
	}

	$config = fahrschule_blocks_config();

	foreach ( $config as $name => $cfg ) {

		/* --- a) Attribute aus den 'fields' ableiten (Single Source) --- */
		$attributes = array();
		$defaults   = array();
		if ( ! empty( $cfg['fields'] ) ) {
			foreach ( $cfg['fields'] as $key => $f ) {
				// Wiederholfelder sind Listen, keine Zeichenketten - sonst
				// verwirft der Block-Editor den Wert beim Speichern.
				$is_repeater = ( isset( $f['control'] ) && 'repeater' === $f['control'] );
				$attributes[ $key ] = array(
					'type'    => $is_repeater ? 'array' : 'string',
					'default' => $f['default'],
				);
				$defaults[ $key ] = $f['default'];
			}
		}

		/* --- b) Dynamischen Gutenberg-Block registrieren --- */
		register_block_type(
			$name,
			array(
				'api_version'     => 3,
				'title'           => $cfg['title'],
				'description'     => isset( $cfg['description'] ) ? $cfg['description'] : '',
				'category'        => 'fahrschule',
				'icon'            => isset( $cfg['icon'] ) ? $cfg['icon'] : 'block-default',
				'keywords'        => isset( $cfg['keywords'] ) ? $cfg['keywords'] : array(),
				'attributes'      => $attributes,
				'supports'        => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
				'render_callback' => $cfg['render'],
				'editor_style'    => 'fahrschule-main',
				'style'           => 'fahrschule-main',
			)
		);

		/* --- c) Passendes Block-Pattern registrieren (vordefiniertes Layout) --- */
		if ( ! empty( $cfg['pattern'] ) ) {
			$content = empty( $defaults )
				? '<!-- wp:' . $name . ' /-->'
				: '<!-- wp:' . $name . ' ' . wp_json_encode( $defaults ) . ' /-->';

			register_block_pattern(
				str_replace( 'fahrschule/', 'fahrschule-pattern/', $name ),
				array(
					'title'       => $cfg['title'] . ' (Pattern)',
					'description' => isset( $cfg['description'] ) ? $cfg['description'] : '',
					'categories'  => array( 'fahrschule' ),
					'keywords'    => isset( $cfg['keywords'] ) ? $cfg['keywords'] : array(),
					'content'     => $content,
				)
			);
		}
	}
}
add_action( 'init', 'fahrschule_register_blocks_and_patterns' );

/* =========================================================================
 * 5) Body-Klasse + Editor-Wrapper für korrekte Vorschau
 * ====================================================================== */
/*
 * Ohne JavaScript werden Scroll-Animationen übersprungen, damit alle Inhalte
 * sichtbar und crawlbar bleiben. Cookie-Banner und TikTok-Badge lassen sich
 * nur per JavaScript schließen und würden sonst dauerhaft den unteren
 * Bildrand belegen - ohne JS lädt das Theme ohnehin nichts
 * Einwilligungspflichtiges. (Stand bis 2.x in header.php.)
 */
function fahrschule_noscript() {
	echo "<noscript><style>.fs-fade-up{opacity:1 !important;animation:none !important}.fs-cookiebanner,.fs-tiktok{display:none !important}</style></noscript>\n";
}
add_action( 'wp_head', 'fahrschule_noscript', 20 );

/*
 * Hinweis im Backend, wenn ein Plugin fehlt, das die Website erwartet. Das
 * Theme läuft ohne sie weiter (Rückfall-Formulare, leere Stellenliste) -
 * der Hinweis sagt nur, was dann fehlt. Die Plugins liegen nicht im ZIP.
 */
function fahrschule_plugin_hinweis() {
	if ( ! current_user_can( 'activate_plugins' ) ) { return; }
	$fehlt = array();
	if ( ! defined( 'WPCF7_VERSION' ) ) { $fehlt[] = 'Contact Form 7 (Formulare)'; }
	if ( ! defined( 'FLAMINGO_VERSION' ) ) { $fehlt[] = 'Flamingo (Formular-Eingänge speichern)'; }
	if ( ! class_exists( 'WP_Job_Manager' ) ) { $fehlt[] = 'WP Job Manager (Stellenangebote)'; }
	if ( ! defined( 'EDI_INTEGRATIONEN_VERSION' ) ) { $fehlt[] = 'edi-integrationen (Begleit-Plugin aus dem Theme-Paket)'; }
	if ( ! $fehlt ) { return; }
	echo '<div class="notice notice-warning"><p><strong>Fahrschule edi:</strong> Diese Plugins fehlen oder sind nicht aktiv: '
		. esc_html( implode( ', ', $fehlt ) ) . '. Ohne sie nutzt die Website eingebaute Formulare bzw. zeigt keine Stellen.</p></div>';
}
add_action( 'admin_notices', 'fahrschule_plugin_hinweis' );

function fahrschule_body_class( $classes ) {
	$classes[] = 'fahrschule-theme';
	return $classes;
}
add_filter( 'body_class', 'fahrschule_body_class' );

/* =========================================================================
 * 6) Favicon aus dem echten Logo (vorab zugeschnittene Dateien der alten
 *    Seite, siehe assets/images/). Nur wenn WordPress kein eigenes Site-Icon
 *    gesetzt hat, damit eine spätere Einstellung im Customizer Vorrang hat.
 * ====================================================================== */
function fahrschule_favicon() {
	if ( has_site_icon() ) { return; }
	$dir  = FAHRSCHULE_DIR . '/assets/images/';
	$base = FAHRSCHULE_URI . '/assets/images/';
	$sizes = array( '32' => 'favicon-32.webp', '180' => 'favicon-180.webp', '192' => 'favicon-192.webp' );
	foreach ( $sizes as $size => $file ) {
		if ( ! file_exists( $dir . $file ) ) { continue; }
		$rel      = ( '180' === $size ) ? 'apple-touch-icon' : 'icon';
		$size_esc = esc_attr( $size );
		printf(
			'<link rel="%s" sizes="%sx%s" type="image/webp" href="%s">' . "\n",
			esc_attr( $rel ), $size_esc, $size_esc, esc_url( $base . $file )
		);
	}
}
add_action( 'wp_head', 'fahrschule_favicon', 1 );
