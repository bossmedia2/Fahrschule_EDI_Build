<?php
/**
 * Fahrschule & Fahrlehrerfachschule edi – Zentrale Seiten-Registry
 *
 * Die Website ist als klassische Seitenstruktur mit echten Unterseiten aufgebaut
 * (KEINE Onepage-/Section-Landingpage). Diese Datei ist die einzige Stelle, an der
 * Seiten, URLs, Navigation und die jeweiligen Block-Stapel definiert werden.
 *
 * Daraus ergeben sich automatisch:
 *   - alle internen Verlinkungen (fs_url) → keine toten Links möglich
 *   - die Navigation inkl. Dropdowns (Desktop) und Drawer (Mobil)
 *   - der Seiten-Router in page.php
 *   - Breadcrumbs (sichtbar + JSON-LD)
 *   - die automatische Seitenanlage bei Theme-Aktivierung
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Alle Seiten der Website.
 *
 * key      => interner Schlüssel (für fs_url)
 * slug     => URL-Segment
 * parent   => key der übergeordneten Seite (oder '')
 * title    => Seitentitel (<title>, H1 falls kein 'pdf')
 * menu     => Beschriftung in der Navigation
 * pdf      => PDF-Abschnitt der Seite (inc/pdf-texts.php): Label und
 *             Überschrift erscheinen im Seitenkopf, der Abschnitt selbst
 *             folgt als erster Block (mit 'head' => '0', ohne doppelten Kopf)
 * lead     => Einleitungstext im Seitenkopf (nur Seiten ohne 'pdf')
 * desc     => Meta-Description
 * blocks   => Block-Stapel, der die Seite rendert
 * nav      => Navigationsgruppe: 'main' | 'sub' | 'none'
 */
function fahrschule_page_map() {
	return apply_filters( 'fahrschule_page_map', array(

		/* ---------------- Startseite ---------------- */
		'home' => array(
			'slug' => '', 'parent' => '', 'nav' => 'none',
			'title' => 'Fahrschule edi – Mit Kompetenz zum Führerschein',
			'menu'  => 'Startseite',
			'desc'  => 'Fahrschule & Fahrlehrerfachschule edi: seit über 30 Jahren Ihr Partner zum Führerschein im Ruhrgebiet. Standorte in Duisburg, Essen und Gelsenkirchen.',
			'blocks' => array(),
		),

		/* ---------------- Ausbildung ---------------- */
		'klassen' => array(
			'slug' => 'fuehrerscheinklassen', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Führerscheinklassen',
			'menu'  => 'Führerscheinklassen',
			'pdf'   => 'f3',
			'desc'  => 'Alle Führerscheinklassen bei der Fahrschule edi: Motorrad (AM, A1, A2, A), PKW (BF17, B, B197, BE), LKW (C1, C1E, C, CE) und Bus (D, DE).',
			'blocks' => array(
				array( 'fahrschule/license-overview', array( 'head' => '0' ) ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'preise' => array(
			'slug' => 'preise', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Preise & Führerschein-Rechner',
			'menu'  => 'Preise & Rechner',
			'lead'  => 'Transparente Richtpreise: Kalkulieren Sie die Kosten für Ihre Wunschklasse in wenigen Klicks.',
			'desc'  => 'Führerschein-Rechner der Fahrschule edi: Richtpreise für alle Klassen transparent berechnen – inklusive Fahrstunden und Theorie.',
			/* 'license-classes' bewusst hierher verschoben (nicht nach
			   /fuehrerscheinklassen/): Es teilt sich den 6-Klassen/
			   Richtpreis-Datensatz mit 'calculator' und stand zuvor im
			   Widerspruch zur vollständigen PDF-Klassenliste auf
			   /fuehrerscheinklassen/ (siehe Content-Audit). */
			'blocks' => array(
				array( 'fahrschule/license-classes', array() ),
				array( 'fahrschule/calculator', array() ),
				array( 'fahrschule/faq', array() ),
			),
		),
		'leistungen' => array(
			'slug' => 'leistungen', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Unsere Leistungen',
			'menu'  => 'Leistungen',
			'pdf'   => 'f4',
			'desc'  => 'Leistungen der Fahrschule edi: Antragstellung, Führerscheinfinanzierung, Erste-Hilfe-Kurse, Passbilder, moderne Lernsoftware und persönliche Betreuung.',
			'blocks' => array(
				array( 'fahrschule/services', array( 'head' => '0' ) ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'moderne-ausbildung' => array(
			'slug' => 'moderne-ausbildung', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Moderne Ausbildung',
			'menu'  => 'Moderne Ausbildung',
			'pdf'   => 'f7',
			'desc'  => 'Moderne Fahrausbildung bei edi: innovative Fahrsimulatoren, moderner Fuhrpark und aktuelle Lernsoftware für Theorie und Praxis.',
			'blocks' => array(
				array( 'fahrschule/modern-training', array( 'head' => '0' ) ),
				array( 'fahrschule/fleet-facilities', array() ),
			),
		),
		'theorieunterricht' => array(
			'slug' => 'theorieunterricht', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Theorieunterricht',
			'menu'  => 'Theorieunterricht',
			'lead'  => 'Feste Wochentermine für den Theorieunterricht an allen drei Standorten.',
			'desc'  => 'Theorieunterricht bei der Fahrschule edi: wöchentliche Termine in Duisburg, Essen und Gelsenkirchen – in Gelsenkirchen auch in türkischer Sprache.',
			'blocks' => array(
				array( 'fahrschule/theory-schedule', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),

		/* ---------------- Beruf & Weiterbildung ---------------- */
		'berufskraftfahrer' => array(
			'slug' => 'berufskraftfahrer', 'parent' => '', 'nav' => 'sub', 'group' => 'beruf',
			'title' => 'Berufskraftfahrer',
			'menu'  => 'Berufskraftfahrer',
			'pdf'   => 'f5',
			'desc'  => 'Ausbildung und Weiterbildung für Berufskraftfahrer bei der Fahrschule edi: LKW-Führerscheine, Ladungssicherung und wirtschaftliches Fahren.',
			'blocks' => array(
				array( 'fahrschule/professional-drivers', array( 'head' => '0' ) ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'fahrlehrerausbildung' => array(
			'slug' => 'fahrlehrerausbildung', 'parent' => '', 'nav' => 'sub', 'group' => 'beruf',
			'title' => 'Fahrlehrerfachschule',
			'menu'  => 'Fahrlehrerausbildung',
			'lead'  => 'Werden Sie Fahrlehrerin oder Fahrlehrer – Ihre Ausbildung an unserer eigenen Fahrlehrerfachschule.',
			'desc'  => 'Fahrlehrerfachschule edi: Ausbildung zum Fahrlehrer nach dem Fahrlehrergesetz. Fahrlehrerlaubnis BE, A, CE und DE im Ruhrgebiet.',
			'blocks' => array(
				array( 'fahrschule/instructor-training', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'asf-fes' => array(
			'slug' => 'asf-fes', 'parent' => '', 'nav' => 'sub', 'group' => 'beruf',
			'title' => 'ASF & FES – Seminare',
			'menu'  => 'ASF & FES',
			'pdf'   => 'f6',
			'desc'  => 'ASF-Aufbauseminare und FES zum Punkteabbau bei der Fahrschule edi – mit individueller Beratung rund um die Fahrerlaubnis.',
			'blocks' => array(
				array( 'fahrschule/seminars', array( 'head' => '0' ) ),
				array( 'fahrschule/cta', array() ),
			),
		),

		/* ---------------- Standorte ---------------- */
		'standorte' => array(
			'slug' => 'standorte', 'parent' => '', 'nav' => 'main', 'group' => 'standorte',
			'title' => 'Unsere Standorte',
			'menu'  => 'Standorte',
			'pdf'   => 'f8',
			'desc'  => 'Die Fahrschule edi finden Sie in Duisburg, Essen und Gelsenkirchen. Adressen, Telefonnummern und Öffnungszeiten aller Standorte.',
			'blocks' => array(
				array( 'fahrschule/locations', array( 'head' => '0' ) ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'duisburg' => array(
			'slug' => 'duisburg', 'parent' => 'standorte', 'nav' => 'sub', 'group' => 'standorte',
			'title' => 'Fahrschule edi Duisburg',
			'menu'  => 'Duisburg',
			'desc'  => 'Fahrschule edi in Duisburg, Friedrich Ebert Str. 346: Führerschein für PKW, Motorrad, LKW und Bus. Sicherheitstraining für Hafen und Logistik.',
			'blocks' => array(
				array( 'fahrschule/location-hero', array(
					'city' => 'Duisburg', 'icon' => 'shield', 'badge' => 'Hafen & Logistik',
					'heading' => 'Sicher fahren in Duisburg. Sicher fahren im Hafen. Sicher fahren bei edi.',
					'lead' => 'Spezialisiert auf Sicherheitstraining und Fahrpraxis im dichten Stadtverkehr. Ideal für logistische Anforderungen im Duisburger Hafen.',
					'address' => fahrschule_pdf_location( 'Duisburg' )['street'], 'zip' => fahrschule_pdf_location( 'Duisburg' )['zip'],
					'phone' => fahrschule_pdf_location( 'Duisburg' )['phone'], 'email' => fahrschule_pdf_location( 'Duisburg' )['email'],
					'hours' => str_replace( "\n", ', ', fahrschule_location_hours_text( 'Duisburg' ) ),
				) ),
				array( 'fahrschule/location-content', array(
					'city' => 'Duisburg',
					'img1' => fs_media( 'loc_duisburg' ),
				) ),
				array( 'fahrschule/location-calculator', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'essen' => array(
			'slug' => 'essen', 'parent' => 'standorte', 'nav' => 'sub', 'group' => 'standorte',
			'title' => 'Fahrschule edi Essen',
			'menu'  => 'Essen',
			'desc'  => 'Fahrschule edi in Essen, Wilhelm Nieswandt Allee 153: moderne Fahrausbildung für alle Führerscheinklassen mit erfahrenen Fahrlehrern.',
			'blocks' => array(
				array( 'fahrschule/location-hero', array(
					'city' => 'Essen', 'icon' => 'truck', 'badge' => 'Modernität & Vielfalt',
					'heading' => 'Fahrschule edi Essen – Modernität und Vielfalt im Herzen des Ruhrgebiets.',
					'lead' => 'Moderne Ausbildung für alle Klassen mitten in Essen. Vielfältige Angebote, erfahrene Fahrlehrer und eine entspannte Lernatmosphäre.',
					'address' => fahrschule_pdf_location( 'Essen' )['street'], 'zip' => fahrschule_pdf_location( 'Essen' )['zip'],
					'phone' => fahrschule_pdf_location( 'Essen' )['phone'], 'email' => fahrschule_pdf_location( 'Essen' )['email'],
					'hours' => str_replace( "\n", ', ', fahrschule_location_hours_text( 'Essen' ) ),
					'contact' => 'Betül Akar',
				) ),
				array( 'fahrschule/location-content', array(
					'city' => 'Essen',
					'img1' => fs_media( 'loc_essen' ),
				) ),
				array( 'fahrschule/location-calculator', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'gelsenkirchen' => array(
			'slug' => 'gelsenkirchen', 'parent' => 'standorte', 'nav' => 'sub', 'group' => 'standorte',
			'title' => 'Fahrschule edi Gelsenkirchen',
			'menu'  => 'Gelsenkirchen',
			'desc'  => 'Fahrschule edi in Gelsenkirchen, Luitpoldstr. 4: Führerschein mit persönlicher Betreuung – Motorrad, PKW, LKW und Bus.',
			'blocks' => array(
				array( 'fahrschule/location-hero', array(
					'city' => 'Gelsenkirchen', 'icon' => 'award', 'badge' => 'Zentrum & Tradition',
					'heading' => 'Fahrschule edi Gelsenkirchen – Zentrum und Tradition für Ihren Führerschein.',
					'lead' => 'Traditionsreiche Fahrschule im Zentrum von Gelsenkirchen. Persönliche Betreuung und bewährte Ausbildung auf modernem Niveau.',
					'address' => fahrschule_pdf_location( 'Gelsenkirchen' )['street'], 'zip' => fahrschule_pdf_location( 'Gelsenkirchen' )['zip'],
					'phone' => fahrschule_pdf_location( 'Gelsenkirchen' )['phone'], 'email' => fahrschule_pdf_location( 'Gelsenkirchen' )['email'],
					'hours' => str_replace( "\n", ', ', fahrschule_location_hours_text( 'Gelsenkirchen' ) ),
					'contact' => 'Aslan Seymen',
				) ),
				array( 'fahrschule/location-content', array(
					'city' => 'Gelsenkirchen',
					'img1' => fs_media( 'loc_gelsenkirchen' ),
				) ),
				array( 'fahrschule/location-calculator', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),

		/* ---------------- Über uns ---------------- */
		'ueber-uns' => array(
			'slug' => 'ueber-uns', 'parent' => '', 'nav' => 'main', 'group' => 'ueberuns',
			'title' => 'Über uns',
			'menu'  => 'Über uns',
			'pdf'   => 'f2',
			'desc'  => 'Über die Fahrschule & Fahrlehrerfachschule edi: seit vielen Jahren im Ruhrgebiet zuhause, mit erfahrenen Fahrlehrern und persönlicher Betreuung.',
			'blocks' => array(
				array( 'fahrschule/about-intro', array( 'head' => '0' ) ),
				array( 'fahrschule/about-timeline', array() ),
				array( 'fahrschule/about-philosophy', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'team' => array(
			'slug' => 'team', 'parent' => 'ueber-uns', 'nav' => 'sub', 'group' => 'ueberuns',
			'title' => 'Unser Team',
			'menu'  => 'Team',
			'pdf'   => 'f9',
			'desc'  => 'Das Team der Fahrschule edi: engagierte Fahrlehrer und Mitarbeiter mit langjähriger Erfahrung an drei Standorten im Ruhrgebiet.',
			'blocks' => array(
				array( 'fahrschule/about-team', array( 'head' => '0' ) ),
				array( 'fahrschule/team-spirit', array() ),
			),
		),
		'erfahrungen' => array(
			'slug' => 'erfahrungen', 'parent' => 'ueber-uns', 'nav' => 'sub', 'group' => 'ueberuns',
			'title' => 'Erfahrungen & Bewertungen',
			'menu'  => 'Erfahrungen',
			'desc'  => 'Über 10.000 zufriedene Fahrschüler: Bewertungen und Erfahrungsberichte von Absolventen der Fahrschule edi im Ruhrgebiet.',
			'blocks' => array(
				array( 'fahrschule/testimonials-hero', array() ),
				array( 'fahrschule/graduates-gallery', array() ),
				array( 'fahrschule/testimonials-detailed', array() ),
				array( 'fahrschule/instagram-slider', array() ),
			),
		),

		/* ---------------- Service ---------------- */
		'faq' => array(
			'slug' => 'faq', 'parent' => '', 'nav' => 'main', 'group' => '',
			'title' => 'Häufige Fragen',
			'menu'  => 'FAQ',
			'lead'  => 'Antworten auf die Fragen, die uns am häufigsten erreichen.',
			'desc'  => 'Häufige Fragen zur Fahrausbildung bei edi: Dauer, Mindestalter, Kosten, Unterrichtsmaterialien und Sprachen.',
			'blocks' => array(
				array( 'fahrschule/faq', array() ),
				array( 'fahrschule/cta', array() ),
			),
		),
		'kontakt' => array(
			'slug' => 'kontakt', 'parent' => '', 'nav' => 'main', 'group' => '',
			'title' => 'Kontakt',
			'menu'  => 'Kontakt',
			'lead'  => 'Rufen Sie uns an, schreiben Sie uns oder kommen Sie direkt in einer unserer Filialen vorbei.',
			'desc'  => 'Kontakt zur Fahrschule edi: Telefon, E-Mail und Adressen der Standorte Duisburg, Essen und Gelsenkirchen.',
			'blocks' => array(
				array( 'fahrschule/cta', array() ),
				array( 'fahrschule/contact-form', array() ),
				array( 'fahrschule/locations', array() ),
			),
		),
		'anmeldung' => array(
			'slug' => 'anmeldung', 'parent' => '', 'nav' => 'none',
			'title' => 'Jetzt anmelden',
			'menu'  => 'Anmeldung',
			'pdf'   => 'f10',
			'desc'  => 'Melden Sie sich jetzt bei der Fahrschule edi an. Wir beraten Sie persönlich zur passenden Führerscheinklasse.',
			'blocks' => array(
				/* Der Link „Fahrschule edi Anmeldung" (F10) führt hier direkt zum Formular darunter. */
				array( 'fahrschule/anmeldung', array( 'head' => '0', 'btn_url' => '#kontaktformular' ) ),
				array( 'fahrschule/contact-form', array(
					'heading' => 'Jetzt unverbindlich anmelden',
					'lead'    => 'Füllen Sie das Formular aus – wir melden uns kurzfristig zurück und klären alle weiteren Schritte.',
				) ),
				array( 'fahrschule/locations', array() ),
			),
		),

		/* ---------------- Rechtliches ---------------- */
		'impressum' => array(
			'slug' => 'impressum', 'parent' => '', 'nav' => 'none',
			'title' => 'Impressum', 'menu' => 'Impressum',
			'desc'  => 'Impressum der Fahrschule & Fahrlehrerfachschule edi GmbH, Duisburg.',
			'blocks' => array( array( 'fahrschule/legal', array( 'kind' => 'impressum' ) ) ),
		),
		'datenschutz' => array(
			'slug' => 'datenschutz', 'parent' => '', 'nav' => 'none',
			'title' => 'Datenschutz', 'menu' => 'Datenschutz',
			'desc'  => 'Datenschutzerklärung der Fahrschule edi – Informationen zur Verarbeitung personenbezogener Daten.',
			'blocks' => array( array( 'fahrschule/legal', array( 'kind' => 'datenschutz' ) ) ),
		),
	) );
}

/** Navigationsgruppen (Dropdown-Struktur). */
function fahrschule_nav_groups() {
	return apply_filters( 'fahrschule_nav_groups', array(
		'ausbildung' => array( 'label' => 'Ausbildung', 'parent' => null ),
		'beruf'      => array( 'label' => 'Beruf & Weiterbildung', 'parent' => null ),
		'standorte'  => array( 'label' => 'Standorte', 'parent' => 'standorte' ),
		'ueberuns'   => array( 'label' => 'Über uns', 'parent' => 'ueber-uns' ),
	) );
}

/**
 * Liefert den vollständigen Pfad einer Seite (inkl. Elternseite).
 */
function fahrschule_page_path( $key ) {
	$map = fahrschule_page_map();
	if ( ! isset( $map[ $key ] ) ) { return ''; }
	$page = $map[ $key ];
	$path = $page['slug'];
	if ( ! empty( $page['parent'] ) && isset( $map[ $page['parent'] ] ) ) {
		$parent_slug = $map[ $page['parent'] ]['slug'];
		if ( $parent_slug ) { $path = $parent_slug . '/' . $path; }
	}
	return $path;
}

/**
 * Zentrale URL-Auflösung. Verhindert tote Links: Alle internen Verweise laufen
 * hierüber, statt Pfade im Markup zu wiederholen.
 */
function fs_url( $key ) {
	if ( 'home' === $key ) { return home_url( '/' ); }
	$path = fahrschule_page_path( $key );
	if ( '' === $path ) { return home_url( '/' ); }

	// Existiert die Seite wirklich, deren echten Permalink verwenden.
	$page = get_page_by_path( $path );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( '/' . $path . '/' );
}

/**
 * Löst Link-Werte auf. Werte, die mit "@" beginnen, sind Seitenschlüssel
 * (z. B. "@anmeldung"); alles andere wird als normale URL behandelt.
 */
function fs_link( $value ) {
	$value = (string) $value;
	if ( '' === $value ) { return home_url( '/' ); }
	if ( '@' === substr( $value, 0, 1 ) ) {
		return fs_url( substr( $value, 1 ) );
	}
	return $value;
}

/** Ermittelt den Seitenschlüssel der aktuell angezeigten Seite. */
function fahrschule_current_key() {
	if ( is_front_page() ) { return 'home'; }
	if ( ! is_singular( 'page' ) ) { return ''; }
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) { return ''; }
	foreach ( fahrschule_page_map() as $key => $page ) {
		if ( $page['slug'] === $post->post_name ) { return $key; }
	}
	return '';
}

/** Gibt die Definition der aktuellen Seite zurück. */
function fahrschule_current_page() {
	$key = fahrschule_current_key();
	$map = fahrschule_page_map();
	return ( $key && isset( $map[ $key ] ) ) ? $map[ $key ] : null;
}

/**
 * Sichtbare Breadcrumb-Navigation (ergänzt das BreadcrumbList-Schema).
 */
function fs_breadcrumbs() {
	$key = fahrschule_current_key();
	if ( ! $key || 'home' === $key ) { return ''; }
	$map = fahrschule_page_map();
	if ( ! isset( $map[ $key ] ) ) { return ''; }

	$trail = array();
	$cur   = $key;
	while ( $cur && isset( $map[ $cur ] ) ) {
		array_unshift( $trail, $cur );
		$cur = $map[ $cur ]['parent'];
	}

	ob_start(); ?>
	<nav class="fs-crumbs" aria-label="Brotkrumen-Navigation">
		<div class="fs-container">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Startseite</a></li>
				<?php foreach ( $trail as $i => $k ) :
					$is_last = ( $i === count( $trail ) - 1 ); ?>
					<li<?php echo $is_last ? ' aria-current="page"' : ''; ?>>
						<?php if ( $is_last ) : ?>
							<span><?php echo esc_html( $map[ $k ]['menu'] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( fs_url( $k ) ); ?>"><?php echo esc_html( $map[ $k ]['menu'] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</nav>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 * AUTOMATISCHE SEITENANLAGE BEI THEME-AKTIVIERUNG
 *
 * Legt alle Seiten der Registry an (sofern nicht vorhanden), setzt die
 * Eltern-Kind-Beziehungen und definiert die Startseite. Dadurch ist die
 * Seitenstruktur direkt nach der Aktivierung vollständig und verlinkt.
 * ====================================================================== */
function fahrschule_create_pages() {
	$map = fahrschule_page_map();
	$ids = array();

	// 1. Durchlauf: Elternseiten zuerst.
	foreach ( array( false, true ) as $children_pass ) {
		foreach ( $map as $key => $page ) {
			$has_parent = ! empty( $page['parent'] );
			if ( $has_parent !== $children_pass ) { continue; }

			if ( 'home' === $key ) {
				$slug  = 'startseite';
				$title = 'Startseite';
			} else {
				$slug  = $page['slug'];
				$title = $page['title'];
			}

			$existing = get_page_by_path( fahrschule_page_path( $key ) ? fahrschule_page_path( $key ) : $slug );
			if ( $existing instanceof WP_Post ) {
				$ids[ $key ] = $existing->ID;
				continue;
			}

			$args = array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '', // Inhalt kommt aus dem Block-Stapel der Registry.
			);
			if ( $has_parent && isset( $ids[ $page['parent'] ] ) ) {
				$args['post_parent'] = $ids[ $page['parent'] ];
			}
			$id = wp_insert_post( $args );
			if ( ! is_wp_error( $id ) ) { $ids[ $key ] = $id; }
		}
	}

	// Startseite festlegen.
	if ( isset( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'fahrschule_create_pages' );
