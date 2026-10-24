<?php
/**
 * Fahrschule edi – SEO-Grundlage
 *
 * Enthält:
 *  1. Zentrale Stammdaten (NAP: Name, Adresse, Telefon) als Single Source of Truth
 *  2. Meta-Description, Canonical, Robots-Steuerung
 *  3. Open Graph + Twitter Cards (Social Sharing)
 *  4. Strukturierte Daten (JSON-LD): Organization/DrivingSchool, LocalBusiness je
 *     Standort, WebSite+SearchAction, BreadcrumbList, FAQPage
 *  5. Titel-Optimierung, Performance-Hints (preconnect/dns-prefetch)
 *  6. robots.txt-Ausgabe (WordPress-Filter)
 *  7. Aufräumen von überflüssigem Head-Ballast
 *
 * Hinweis: Das Theme setzt bewusst KEIN eigenes Meta-Feld-UI um. Wird ein
 * SEO-Plugin (Yoast, Rank Math, SEOPress) aktiviert, deaktiviert sich die
 * Meta-/OG-/JSON-LD-Ausgabe dieses Themes automatisch, um Duplikate zu vermeiden.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* =========================================================================
 * 1) STAMMDATEN – zentrale Pflegestelle (NAP-Konsistenz ist ein Local-SEO-Faktor)
 * ====================================================================== */
function fahrschule_business_data() {
	return apply_filters( 'fahrschule_business_data', array(
		'name'        => 'Fahrschule edi',
		'legal_name'  => 'Fahrschule & Fahrlehrerfachschule edi GmbH',
		'description' => 'Fahrschule & Fahrlehrerfachschule edi: über 30 Jahre Erfahrung im Ruhrgebiet. Führerschein für Motorrad, PKW, LKW und Bus sowie Fahrlehrerausbildung in Duisburg, Essen und Gelsenkirchen.',
		'email'       => 'info@fahrschule-edi.de',
		'phone'       => '+49 203 5785703',
		'vat'         => 'DE195505422',
		// Geschäftsführung laut Impressum („Vertreten durch"). Einzige Quelle -
		// Impressum und Team-Seite (Gruppe Geschäftsleitung) lesen hier.
		'management'  => array( 'Gülsüm Alkan', 'Robert Peter Fiedler', 'Rainer Fritz Erich Grunwald' ),
		'founded'     => '1993',
		'currency'    => 'EUR',
		'logo'        => fs_media( 'logo' ),
		'social'      => array(
			'https://www.instagram.com/fahrschuleedi/',
			'https://www.tiktok.com/@edi_fahrschule',
		),
		/*
		 * Öffnungszeiten für die Organisation im Schema (siehe fahrschule_json_ld()) -
		 * entspricht dem Unternehmenssitz Duisburg, echt aus der alten Website
		 * übernommen (Kontaktseite, „Ansprechpartner und Öffnungszeiten"). Für die
		 * einzelnen Standorte gelten die abweichenden, jeweils eigenen Zeiten unten
		 * unter locations[...]['opening'].
		 */
		'opening'     => array(
			array( 'days' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday' ), 'open' => '13:00', 'close' => '20:00' ),
			array( 'days' => array( 'Friday' ), 'open' => '13:00', 'close' => '18:00' ),
		),
		'locations'   => array(
			'duisburg' => array(
				'city' => 'Duisburg', 'street' => 'Friedrich Ebert Str. 346', 'zip' => '47139',
				'phone' => '+49 203 5785703', 'email' => 'duisburg@fahrschule-edi.de',
				'lat' => 51.4762, 'lng' => 6.7523, 'slug' => 'duisburg',
				'contact' => '',
				'languages' => array( 'de' ),
				'opening' => array(
					array( 'days' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday' ), 'open' => '13:00', 'close' => '20:00' ),
					array( 'days' => array( 'Friday' ), 'open' => '13:00', 'close' => '18:00' ),
				),
			),
			'essen' => array(
				'city' => 'Essen', 'street' => 'Wilhelm Nieswandt Allee 153', 'zip' => '45326',
				'phone' => '+49 201 2899482', 'email' => 'essen@fahrschule-edi.de',
				'lat' => 51.4818, 'lng' => 7.0177, 'slug' => 'essen',
				'contact' => 'Betül Akar',
				'languages' => array( 'de' ),
				'opening' => array(
					array( 'days' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday' ), 'open' => '13:00', 'close' => '20:00' ),
					array( 'days' => array( 'Friday' ), 'open' => '13:00', 'close' => '18:00' ),
				),
			),
			'gelsenkirchen' => array(
				'city' => 'Gelsenkirchen', 'street' => 'Luitpoldstr. 4', 'zip' => '45879',
				'phone' => '+49 209 1487030', 'email' => 'gelsenkirchen@fahrschule-edi.de',
				'lat' => 51.5100, 'lng' => 7.0980, 'slug' => 'gelsenkirchen',
				'contact' => 'Aslan Seymen',
				/* Theorieunterricht auf Türkisch (siehe fs_render_theory_schedule()) - reales,
				   aus der alten Website übernommenes Angebot, nur für diesen Standort belegt. */
				'languages' => array( 'de', 'tr' ),
				'opening' => array(
					array( 'days' => array( 'Monday', 'Tuesday', 'Wednesday' ), 'open' => '08:00', 'close' => '20:00' ),
					array( 'days' => array( 'Thursday' ), 'open' => '13:00', 'close' => '20:00' ),
					array( 'days' => array( 'Friday' ), 'open' => '13:00', 'close' => '18:00' ),
				),
			),
		),
	) );
}

/** Wandelt eine Öffnungszeiten-Liste (siehe fahrschule_business_data()) in Schema.org-Form. */
function fahrschule_opening_hours_schema( array $hours ) {
	$out = array();
	foreach ( $hours as $o ) {
		$out[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => $o['days'],
			'opens'     => $o['open'],
			'closes'    => $o['close'],
		);
	}
	return $out;
}

/** Erkennt aktive SEO-Plugins, um doppelte Meta-Ausgaben zu vermeiden. */
function fahrschule_seo_plugin_active() {
	return (
		defined( 'WPSEO_VERSION' )              // Yoast SEO
		|| defined( 'RANK_MATH_VERSION' )       // Rank Math
		|| defined( 'SEOPRESS_VERSION' )        // SEOPress
		|| class_exists( 'All_in_One_SEO_Pack' ) // AIOSEO
	);
}

/* =========================================================================
 * 2) SEITEN-KONTEXT: Titel, Beschreibung, Bild
 * ====================================================================== */

/** Liefert eine saubere Meta-Description für die aktuelle Ansicht. */
function fahrschule_meta_description() {
	$biz  = fahrschule_business_data();
	$desc = '';

	if ( is_front_page() ) {
		$desc = 'Fahrschule & Fahrlehrerfachschule edi – seit über 30 Jahren Ihr Partner zum Führerschein im Ruhrgebiet. Motorrad, PKW, LKW, Bus und Fahrlehrerausbildung in Duisburg, Essen und Gelsenkirchen.';
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( ! empty( $post->post_excerpt ) ) {
			$desc = $post->post_excerpt;
		} else {
			// Auszug aus dem Inhalt; Blockkommentare und Shortcodes entfernen.
			$raw  = get_post_field( 'post_content', $post );
			$raw  = excerpt_remove_blocks( $raw );
			$raw  = wp_strip_all_tags( strip_shortcodes( $raw ) );
			$desc = $raw;
		}
		// Kuratierte Beschreibung aus der zentralen Seiten-Registry.
		$page = function_exists( 'fahrschule_current_page' ) ? fahrschule_current_page() : null;
		if ( $page && ! empty( $page['desc'] ) && ( '' === trim( $desc ) || strlen( trim( $desc ) ) < 60 ) ) {
			$desc = $page['desc'];
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	} elseif ( is_search() ) {
		$desc = sprintf( 'Suchergebnisse für „%s" auf der Website der Fahrschule edi.', get_search_query() );
	}

	if ( '' === trim( (string) $desc ) ) {
		$desc = $biz['description'];
	}

	$desc = wp_strip_all_tags( $desc );
	$desc = preg_replace( '/\s+/u', ' ', trim( $desc ) );

	// Auf ~160 Zeichen kürzen, ohne Wörter zu zerschneiden.
	if ( mb_strlen( $desc ) > 160 ) {
		$desc = mb_substr( $desc, 0, 157 );
		$cut  = mb_strrpos( $desc, ' ' );
		if ( $cut ) { $desc = mb_substr( $desc, 0, $cut ); }
		$desc .= '…';
	}
	return $desc;
}

/** Repräsentatives Bild (Beitragsbild, sonst Logo). */
function fahrschule_share_image() {
	$biz = fahrschule_business_data();
	if ( is_singular() && has_post_thumbnail() ) {
		$img = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
		if ( $img ) { return $img[0]; }
	}
	return $biz['logo'];
}

/** Titel-Zusatz: Standortseiten erhalten lokale Signale. */
function fahrschule_document_title_parts( $parts ) {
	if ( is_front_page() ) {
		$parts['title']   = 'Fahrschule & Fahrlehrerfachschule edi – Mit Kompetenz zum Führerschein';
		$parts['tagline']  = 'Duisburg · Essen · Gelsenkirchen';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'fahrschule_document_title_parts' );

/** Trennzeichen im Titel. */
function fahrschule_title_separator() { return '|'; }
add_filter( 'document_title_separator', 'fahrschule_title_separator' );

/* =========================================================================
 * 3) HEAD-AUSGABE: Meta, Canonical, Open Graph, Twitter, Performance-Hints
 * ====================================================================== */
function fahrschule_seo_head() {
	$biz = fahrschule_business_data();

	// Performance: Verbindungen zu externen Medien-Hosts früh aufbauen.
	// Verbindung zu externen Medien-Hosts nur vorbereiten, solange solche genutzt werden.
	$logo_url = fs_media( 'logo' );
	$host     = wp_parse_url( $logo_url, PHP_URL_HOST );
	if ( $host && $host !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		printf( '<link rel="preconnect" href="https://%s" crossorigin>' . "\n", esc_attr( $host ) );
	}
	echo '<meta name="theme-color" content="#d12487">' . "\n";
	echo '<meta name="format-detection" content="telephone=no">' . "\n";

	// Bei aktivem SEO-Plugin: Meta/OG/Canonical dem Plugin überlassen.
	if ( fahrschule_seo_plugin_active() ) {
		return;
	}

	$desc  = fahrschule_meta_description();
	$title = wp_get_document_title();
	$url   = fahrschule_current_url();
	$image = fahrschule_share_image();

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );

	// Canonical – verhindert Duplicate Content.
	if ( ! is_404() && ! is_search() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}

	// Paginierung: prev/next
	if ( is_paged() ) {
		$prev = get_previous_posts_page_link();
		if ( $prev ) { printf( '<link rel="prev" href="%s">' . "\n", esc_url( $prev ) ); }
	}

	/* ---- Open Graph ---- */
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
	printf( '<meta property="og:type" content="%s">' . "\n", is_singular() && ! is_front_page() ? 'article' : 'website' );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	echo '<meta property="og:image:alt" content="Logo der Fahrschule edi">' . "\n";

	/* ---- Twitter / X ---- */
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );

	/* ---- Geo-Angaben auf Standortseiten (Local SEO) ---- */
	$loc = fahrschule_current_location();
	if ( $loc ) {
		printf( '<meta name="geo.region" content="DE-NW">' . "\n" );
		printf( '<meta name="geo.placename" content="%s">' . "\n", esc_attr( $loc['city'] ) );
		printf( '<meta name="geo.position" content="%s;%s">' . "\n", esc_attr( $loc['lat'] ), esc_attr( $loc['lng'] ) );
		printf( '<meta name="ICBM" content="%s, %s">' . "\n", esc_attr( $loc['lat'] ), esc_attr( $loc['lng'] ) );
	}
}
add_action( 'wp_head', 'fahrschule_seo_head', 1 );

/** Aktuelle, kanonische URL ermitteln. */
function fahrschule_current_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}
	$request = isset( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : '';
	return $request ? home_url( '/' . $request . '/' ) : home_url( '/' );
}

/** Gibt die Standortdaten zurück, falls die aktuelle Seite eine Standortseite ist. */
function fahrschule_current_location() {
	if ( ! is_singular() ) { return null; }
	$post = get_queried_object();
	$slug = isset( $post->post_name ) ? $post->post_name : '';
	$biz  = fahrschule_business_data();
	return isset( $biz['locations'][ $slug ] ) ? $biz['locations'][ $slug ] : null;
}

/* =========================================================================
 * 4) ROBOTS-STEUERUNG (Meta-Robots)
 * ====================================================================== */
function fahrschule_wp_robots( $robots ) {
	// Große Bild-/Video-Vorschauen in den Suchergebnissen erlauben.
	$robots['max-image-preview'] = 'large';
	$robots['max-snippet']       = -1;
	$robots['max-video-preview'] = -1;

	// Dünne oder doppelte Ansichten aus dem Index halten.
	if ( is_search() || is_404() || ( is_paged() && is_home() ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = false;
	}
	if ( is_attachment() ) {
		$robots['noindex'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'fahrschule_wp_robots' );

/* =========================================================================
 * 5) STRUKTURIERTE DATEN (JSON-LD)
 * ====================================================================== */
function fahrschule_json_ld() {
	if ( fahrschule_seo_plugin_active() ) {
		return; // Schema dem Plugin überlassen.
	}

	$biz    = fahrschule_business_data();
	$home   = home_url( '/' );
	$org_id = $home . '#organization';
	$graph  = array();

	/* ---- Öffnungszeiten in Schema.org-Form (Unternehmenssitz Duisburg) ---- */
	$hours = fahrschule_opening_hours_schema( $biz['opening'] );

	/* ---- Organisation / Fahrschule ---- */
	$graph[] = array(
		'@type'         => array( 'DrivingSchool', 'EducationalOrganization', 'LocalBusiness' ),
		'@id'           => $org_id,
		'name'          => $biz['name'],
		'legalName'     => $biz['legal_name'],
		'description'   => $biz['description'],
		'url'           => $home,
		'logo'          => array( '@type' => 'ImageObject', 'url' => $biz['logo'] ),
		'image'         => $biz['logo'],
		'email'         => $biz['email'],
		'telephone'     => $biz['phone'],
		'vatID'         => $biz['vat'],
		'foundingDate'  => $biz['founded'],
		'inLanguage'    => 'de-DE',
		'currenciesAccepted' => $biz['currency'],
		'knowsAbout'    => array( 'Fahrausbildung', 'Verkehrssicherheit', 'Fahrlehrerausbildung', 'Berufskraftfahrer-Weiterbildung' ),
		'hasCredential' => array(
			'@type'               => 'EducationalOccupationalCredential',
			'credentialCategory'  => 'certification',
			'name'                => 'CERTQUA-Zertifizierung',
		),
		'sameAs'        => $biz['social'],
		'areaServed'    => array(
			array( '@type' => 'City', 'name' => 'Duisburg' ),
			array( '@type' => 'City', 'name' => 'Essen' ),
			array( '@type' => 'City', 'name' => 'Gelsenkirchen' ),
			array( '@type' => 'AdministrativeArea', 'name' => 'Ruhrgebiet' ),
		),
		'address'       => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'Friedrich Ebert Str. 346',
			'postalCode'      => '47139',
			'addressLocality' => 'Duisburg',
			'addressRegion'   => 'Nordrhein-Westfalen',
			'addressCountry'  => 'DE',
		),
		'openingHoursSpecification' => $hours,
		'hasOfferCatalog' => array(
			'@type' => 'OfferCatalog',
			'name'  => 'Führerscheinklassen',
			'itemListElement' => fahrschule_license_offers( $org_id ),
		),
	);

	/* ---- Website + interne Suche ---- */
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $home . '#website',
		'url'             => $home,
		'name'            => get_bloginfo( 'name' ),
		'inLanguage'      => 'de-DE',
		'publisher'       => array( '@id' => $org_id ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $home . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	/* ---- Einzelne Standorte als eigenständige LocalBusiness-Einträge ---- */
	foreach ( $biz['locations'] as $key => $loc ) {
		$graph[] = array(
			'@type'       => array( 'DrivingSchool', 'LocalBusiness' ),
			'@id'         => $home . '#' . $key,
			'name'        => $biz['name'] . ' ' . $loc['city'],
			'parentOrganization' => array( '@id' => $org_id ),
			'url'         => home_url( '/' . $loc['slug'] . '/' ),
			'image'       => $biz['logo'],
			'telephone'   => $loc['phone'],
			'email'       => $loc['email'],
			'priceRange'  => '€€',
			'currenciesAccepted' => $biz['currency'],
			'knowsLanguage' => $loc['languages'],
			'address'     => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $loc['street'],
				'postalCode'      => $loc['zip'],
				'addressLocality' => $loc['city'],
				'addressRegion'   => 'Nordrhein-Westfalen',
				'addressCountry'  => 'DE',
			),
			'geo' => array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $loc['lat'],
				'longitude' => $loc['lng'],
			),
			'openingHoursSpecification' => fahrschule_opening_hours_schema( $loc['opening'] ),
		);
	}

	/* ---- Aktuelle Seite ---- */
	if ( is_singular() && ! is_front_page() ) {
		$graph[] = array(
			'@type'      => 'WebPage',
			'@id'        => fahrschule_current_url() . '#webpage',
			'url'        => fahrschule_current_url(),
			'name'       => wp_get_document_title(),
			'description'=> fahrschule_meta_description(),
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'about'      => array( '@id' => $org_id ),
			'inLanguage' => 'de-DE',
			'breadcrumb' => array( '@id' => fahrschule_current_url() . '#breadcrumb' ),
		);

		/* ---- Breadcrumbs (hierarchisch aus der Seiten-Registry) ---- */
		$crumbs = array( array(
			'@type' => 'ListItem', 'position' => 1, 'name' => 'Startseite', 'item' => home_url( '/' ),
		) );
		$key = function_exists( 'fahrschule_current_key' ) ? fahrschule_current_key() : '';
		$map = function_exists( 'fahrschule_page_map' ) ? fahrschule_page_map() : array();
		$trail = array();
		$cur   = $key;
		while ( $cur && isset( $map[ $cur ] ) ) {
			array_unshift( $trail, $cur );
			$cur = $map[ $cur ]['parent'];
		}
		$pos = 1;
		foreach ( $trail as $k ) {
			$pos++;
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $pos,
				'name'     => $map[ $k ]['menu'],
				'item'     => fs_url( $k ),
			);
		}
		if ( ! $trail ) {
			$crumbs[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => get_the_title(), 'item' => fahrschule_current_url() );
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => fahrschule_current_url() . '#breadcrumb',
			'itemListElement' => $crumbs,
		);
	}

	/* ---- FAQ-Schema auf der Startseite (der FAQ-Block ist dort eingebunden) ---- */
	$faq_key = function_exists( 'fahrschule_current_key' ) ? fahrschule_current_key() : '';
	if ( 'faq' === $faq_key || 'preise' === $faq_key ) {
		$faqs = fahrschule_faq_data();
		if ( $faqs ) {
			$items = array();
			foreach ( $faqs as $f ) {
				$items[] = array(
					'@type'          => 'Question',
					'name'           => $f[0],
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
				);
			}
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => fahrschule_current_url() . '#faq',
				'mainEntity' => $items,
			);
		}
	}

	$data = array( '@context' => 'https://schema.org', '@graph' => $graph );

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'fahrschule_json_ld', 20 );

/** Führerscheinklassen als Angebotskatalog (Rich Results). */
function fahrschule_license_offers( $org_id = '' ) {
	$classes = array(
		'Klasse AM – Roller und Mopeds',
		'Klasse A1 – Leichtkraftrad bis 125 ccm',
		'Klasse A2 – Motorrad mittlerer Leistung',
		'Klasse A – Motorrad unbeschränkt',
		'Klasse BF17 – Begleitetes Fahren ab 17',
		'Klasse B – PKW bis 3.500 kg',
		'Klasse B197 – Automatik-Regelung',
		'Klasse BE – PKW mit Anhänger',
		'Klasse C1 / C1E – mittlerer LKW',
		'Klasse C / CE – LKW und Sattelzug',
		'Klasse D / DE – Bus',
		'ASF-Aufbauseminare und FES zum Punkteabbau',
		'Aus- und Weiterbildung für Berufskraftfahrer',
		'Fahrlehrerausbildung an der eigenen Fahrlehrerfachschule',
	);
	$out = array();
	foreach ( $classes as $c ) {
		$offered = array(
			'@type'       => array( 'Course', 'Service' ),
			'name'        => $c,
			'serviceType' => 'Fahrausbildung',
		);
		if ( $org_id ) {
			$offered['provider'] = array( '@id' => $org_id );
		}
		$out[] = array(
			'@type'       => 'Offer',
			'itemOffered' => $offered,
		);
	}
	return $out;
}

/**
 * FAQ-Daten – identisch zum FAQ-Block, damit Markup und Schema nicht auseinanderlaufen.
 * Wird von fs_render_faq() und vom FAQPage-Schema gemeinsam genutzt.
 */
function fahrschule_faq_data() {
	return array(
		array( 'Wie lange dauert die Fahrausbildung?', 'Die Dauer hängt von der Führerscheinklasse ab. Für Klasse B sind es ca. 32-40 Fahrstunden plus Theorieunterricht. Der genaue Zeitrahmen wird in der persönlichen Beratung festgelegt.' ),
		array( 'Ab wann kann ich mit der Ausbildung beginnen?', 'Das Mindestalter variiert je nach Führerscheinklasse. Klasse B ab 17 Jahren (BF17), Klasse A1 ab 16 Jahren, Klasse AM ab 15 Jahren. Sie können bereits vorher den Antrag stellen.' ),
		array( 'Welche Unterrichtsmaterialien erhalte ich?', 'Sie erhalten moderne Lernmaterialien, Zugang zu unserem Online-Lernportal und regelmäßige Theorieskripte. Alle Materialien sind aktuell und entsprechen den TÜV-Standards.' ),
		array( 'Kann ich flexible Unterrichtszeiten haben?', 'Ja! Wir bieten flexible Fahrstundentermine und Theorieunterricht zu verschiedenen Uhrzeiten an. Sprechen Sie mit unseren Beratern in Ihrer Filiale.' ),
		array( 'Was kostet die Ausbildung insgesamt?', 'Nutzen Sie unseren Führerschein-Rechner oben auf der Seite für eine genaue Kalkulation. Die Preise beinhalten alle Fahrstunden, Theorieunterricht und notwendigen Materialien.' ),
		array( 'Werden mehrere Sprachen unterstützt?', 'Ja! Unser Unterricht ist auch in mehreren Sprachen möglich. Melden Sie sich einfach bei uns an und teilen Sie Ihre bevorzugte Sprache mit.' ),
	);
}

/* =========================================================================
 * 6) ROBOTS.TXT
 *
 * WordPress liefert unter /robots.txt eine virtuelle Datei aus, solange keine
 * physische Datei im Web-Root liegt. Dieser Filter erzeugt die Ausgabe.
 *
 * Standard: Suchmaschinen (Google, Bing & Co.) dürfen crawlen – notwendig für
 * die SEO-Sichtbarkeit. Ausgesperrt werden KI-Trainings-Crawler, aggressive
 * SEO-/Scraping-Bots sowie interne WordPress-Pfade.
 *
 * Komplettsperre (z. B. Staging): in wp-config.php setzen
 *   define( 'FAHRSCHULE_BLOCK_ALL_BOTS', true );
 * ====================================================================== */
function fahrschule_robots_txt( $output, $public ) {
	$sitemap = home_url( '/wp-sitemap.xml' );

	// Von WordPress als "nicht öffentlich" markiert oder Komplettsperre gewünscht.
	if ( '0' === (string) $public || ( defined( 'FAHRSCHULE_BLOCK_ALL_BOTS' ) && FAHRSCHULE_BLOCK_ALL_BOTS ) ) {
		return "# Diese Website ist für Suchmaschinen gesperrt.\nUser-agent: *\nDisallow: /\n";
	}

	$lines = array();
	$lines[] = '# robots.txt – Fahrschule edi';
	$lines[] = '# Suchmaschinen sind erwünscht, KI-Scraper und Spam-Bots nicht.';
	$lines[] = '';
	$lines[] = '# --- Allgemeine Regeln ---';
	$lines[] = 'User-agent: *';
	$lines[] = 'Disallow: /wp-admin/';
	$lines[] = 'Allow: /wp-admin/admin-ajax.php';
	$lines[] = 'Disallow: /wp-login.php';
	$lines[] = 'Disallow: /xmlrpc.php';
	$lines[] = 'Disallow: /wp-json/';
	$lines[] = 'Disallow: /?s=';
	$lines[] = 'Disallow: /search/';
	$lines[] = 'Disallow: /*?replytocom=';
	$lines[] = 'Disallow: /*?attachment_id=';
	$lines[] = 'Allow: /wp-content/uploads/';
	$lines[] = 'Allow: /wp-content/themes/*.css';
	$lines[] = 'Allow: /wp-content/themes/*.js';
	$lines[] = '';

	/* KI-Trainings- und Scraping-Crawler aussperren */
	$ai_bots = array(
		'GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'CCBot', 'anthropic-ai', 'ClaudeBot',
		'Claude-Web', 'Google-Extended', 'Applebot-Extended', 'PerplexityBot',
		'Bytespider', 'Amazonbot', 'FacebookBot', 'Meta-ExternalAgent', 'cohere-ai',
		'Diffbot', 'ImagesiftBot', 'Omgilibot', 'Timpibot', 'YouBot',
	);
	$lines[] = '# --- KI-Trainings-Crawler ---';
	foreach ( $ai_bots as $bot ) {
		$lines[] = 'User-agent: ' . $bot;
		$lines[] = 'Disallow: /';
		$lines[] = '';
	}

	/* Aggressive SEO-/Analyse-Crawler (Serverlast, kein Nutzen) */
	$seo_bots = array(
		'AhrefsBot', 'SemrushBot', 'MJ12bot', 'DotBot', 'DataForSeoBot',
		'BLEXBot', 'PetalBot', 'SerpstatBot', 'ZoominfoBot', 'Barkrowler',
	);
	$lines[] = '# --- Aggressive SEO-/Scraping-Bots ---';
	foreach ( $seo_bots as $bot ) {
		$lines[] = 'User-agent: ' . $bot;
		$lines[] = 'Disallow: /';
		$lines[] = '';
	}

	$lines[] = '# --- Sitemap ---';
	$lines[] = 'Sitemap: ' . $sitemap;

	return implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'fahrschule_robots_txt', 10, 2 );

/* =========================================================================
 * 7) AUFRÄUMEN & KLEINIGKEITEN
 * ====================================================================== */
function fahrschule_head_cleanup() {
	remove_action( 'wp_head', 'wp_generator' );                        // WP-Version verbergen
	remove_action( 'wp_head', 'wlwmanifest_link' );                    // Windows Live Writer
	remove_action( 'wp_head', 'rsd_link' );                            // Really Simple Discovery
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );                // ?p=123-Shortlinks
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 ); // rel=prev/next für Beiträge
}
add_action( 'init', 'fahrschule_head_cleanup' );

/** Emoji-Skripte entfernen (Performance, keine Funktionseinbuße). */
function fahrschule_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'fahrschule_disable_emojis' );

/** Anhang-Seiten auf die Datei umleiten (verhindert dünne Indexseiten). */
function fahrschule_redirect_attachments() {
	if ( is_attachment() ) {
		$url = wp_get_attachment_url( get_queried_object_id() );
		if ( $url ) {
			wp_safe_redirect( $url, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'fahrschule_redirect_attachments' );

/** Sitemap: Anhänge und leere Taxonomien ausschließen. */
function fahrschule_sitemap_exclude_attachment( $post_types ) {
	unset( $post_types['attachment'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'fahrschule_sitemap_exclude_attachment' );
