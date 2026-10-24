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
 *   - der Seiteninhalt bei der Anlage (post_content) und die Vorlage je Seite
 *   - Breadcrumbs (sichtbar + JSON-LD)
 *   - die automatische Seitenanlage bei Theme-Aktivierung
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Baut einen Klassen-Eintrag der Registry. Eine Klassenseite besteht aus
 * Seitenkopf (Vorlage), dem Block kurs-details (Inhalt) und den Geschwister-
 * klassen plus Anmeldung (Vorlage page-kurs). Leere Fakten bleiben weg.
 *
 * @param string $bereich Registry-Schlüssel des Bereichs (pkw, motorrad, lkw-bus).
 * @param string $klasse  Klassenbezeichnung, z. B. "C1E".
 * @param string $icon    Icon der Kachel.
 * @param string $lead    Einleitung im Seitenkopf (Klassendefinition).
 * @param array  $fakten  Felder für kurs-details (alter, theorie, ...).
 */
function fahrschule_klasse( $bereich, $klasse, $icon, $lead, $fakten ) {
	$titel = 'Klasse ' . $klasse;
	return array(
		'slug' => 'klasse-' . strtolower( $klasse ), 'parent' => $bereich, 'nav' => 'none', 'group' => 'ausbildung',
		'template' => 'page-kurs',
		'title' => $titel, 'menu' => $titel, 'icon' => $icon,
		'lead'  => $lead,
		'desc'  => 'Führerschein ' . $titel . ' bei der Fahrschule edi in Duisburg, Essen und Gelsenkirchen: ' . $lead,
		'blocks' => array(
			array( 'fahrschule/kurs-details', array_merge(
				array( 'heading' => 'Das Wichtigste zur ' . $titel, 'pruefung' => 'Theoretische und praktische Prüfung' ),
				$fakten
			) ),
		),
	);
}

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
 * template => Block-Vorlage (templates/<template>.html). Die Vorlage liefert
 *             Seitenkopf, Brotkrumen und ihre festen Zusatzblöcke (CTA,
 *             Bereichskacheln ...) - diese stehen deshalb NICHT in 'blocks'.
 * blocks   => Seiteninhalt; wird bei der Seitenanlage als Block-Markup in
 *             post_content geschrieben und gehört danach dem Editor
 * nav      => Navigationsgruppe: 'main' | 'sub' | 'none'
 */
function fahrschule_page_map() {
	return apply_filters( 'fahrschule_page_map', array(

		/* ---------------- Startseite ---------------- */
		'home' => array(
			'slug' => '', 'template' => 'front-page', 'parent' => '', 'nav' => 'none',
			'title' => 'Fahrschule edi – Mit Kompetenz zum Führerschein',
			'menu'  => 'Startseite',
			'desc'  => 'Fahrschule & Fahrlehrerfachschule edi: seit über 30 Jahren Ihr Partner zum Führerschein im Ruhrgebiet. Standorte in Duisburg, Essen und Gelsenkirchen.',
			'blocks' => array(
				array( 'fahrschule/video-hero', array() ),
				array( 'fahrschule/about-intro', array() ),
				array( 'fahrschule/page-teasers', array() ),
				array( 'fahrschule/locations', array() ),
				array( 'fahrschule/team-spirit', array() ),
				array( 'fahrschule/testimonials', array() ),
				array( 'fahrschule/anmeldung', array() ),
			),
		),

		/* ---------------- Ausbildung ---------------- */
		'klassen' => array(
			'slug' => 'fuehrerscheinklassen', 'template' => 'page-uebersicht', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Führerscheinklassen',
			'menu'  => 'Führerscheinklassen',
			'pdf'   => 'f3',
			'desc'  => 'Alle Führerscheinklassen bei der Fahrschule edi: Motorrad (AM, A1, A2, A), PKW (BF17, B, B197, BE), LKW (C1, C1E, C, CE) und Bus (D, DE).',
			'blocks' => array(
				array( 'fahrschule/license-overview', array( 'head' => '0' ) ),
			),
		),
		'preise' => array(
			'slug' => 'preise', 'template' => 'page', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
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
			'slug' => 'leistungen', 'template' => 'page-uebersicht', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Unsere Leistungen',
			'menu'  => 'Leistungen',
			'pdf'   => 'f4',
			'desc'  => 'Leistungen der Fahrschule edi: Antragstellung, Führerscheinfinanzierung, Erste-Hilfe-Kurse, Passbilder, moderne Lernsoftware und persönliche Betreuung.',
			'blocks' => array(
				array( 'fahrschule/services', array( 'head' => '0' ) ),
			),
		),
		'moderne-ausbildung' => array(
			'slug' => 'moderne-ausbildung', 'template' => 'page', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
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
			'slug' => 'theorieunterricht', 'template' => 'page-theorie', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung',
			'title' => 'Theorieunterricht',
			'menu'  => 'Theorieunterricht',
			'lead'  => 'Feste Wochentermine für den Theorieunterricht an allen drei Standorten.',
			'desc'  => 'Theorieunterricht bei der Fahrschule edi: wöchentliche Termine in Duisburg, Essen und Gelsenkirchen – in Gelsenkirchen auch in türkischer Sprache.',
			'blocks' => array(
				array( 'fahrschule/theory-schedule', array() ),
			),
		),

		/* ---------------- Beruf & Weiterbildung ---------------- */
		'berufskraftfahrer' => array(
			'slug' => 'berufskraftfahrer', 'template' => 'page-beruf', 'parent' => '', 'nav' => 'sub', 'group' => 'beruf',
			'title' => 'Berufskraftfahrer',
			'menu'  => 'Berufskraftfahrer',
			'pdf'   => 'f5',
			'desc'  => 'Ausbildung und Weiterbildung für Berufskraftfahrer bei der Fahrschule edi: LKW-Führerscheine, Ladungssicherung und wirtschaftliches Fahren.',
			'blocks' => array(
				array( 'fahrschule/professional-drivers', array( 'head' => '0' ) ),
			),
		),
		'fahrlehrerausbildung' => array(
			'slug' => 'fahrlehrerausbildung', 'template' => 'page-beruf', 'parent' => '', 'nav' => 'sub', 'group' => 'beruf',
			'title' => 'Fahrlehrerfachschule',
			'menu'  => 'Fahrlehrerausbildung',
			'lead'  => 'Werden Sie Fahrlehrerin oder Fahrlehrer – Ihre Ausbildung an unserer eigenen Fahrlehrerfachschule.',
			'desc'  => 'Fahrlehrerfachschule edi: Ausbildung zum Fahrlehrer nach dem Fahrlehrergesetz. Fahrlehrerlaubnis BE, A, CE und DE im Ruhrgebiet.',
			'blocks' => array(
				array( 'fahrschule/instructor-training', array() ),
			),
		),
		'asf-fes' => array(
			'slug' => 'asf-fes', 'template' => 'page-beruf', 'parent' => '', 'nav' => 'sub', 'group' => 'beruf',
			'title' => 'ASF & FES – Seminare',
			'menu'  => 'ASF & FES',
			'pdf'   => 'f6',
			'desc'  => 'ASF-Aufbauseminare und FES zum Punkteabbau bei der Fahrschule edi – mit individueller Beratung rund um die Fahrerlaubnis.',
			'blocks' => array(
				array( 'fahrschule/seminars', array( 'head' => '0' ) ),
			),
		),


		/* ---------------- Bereiche (Ebene 1) ----------------
		   Die Klassen-Kacheln liefert die Vorlage page-bereich aus der
		   Seitenhierarchie (Block bereich-uebersicht), nicht der Inhalt. */
		'pkw' => array(
			'slug' => 'pkw', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung', 'template' => 'page-bereich',
			'title' => 'PKW', 'menu' => 'PKW', 'icon' => 'award',
			'lead'  => 'Alle Pkw-Klassen: vom begleiteten Fahren ab 17 bis zum Anhänger.',
			'desc'  => 'Pkw-Führerschein bei der Fahrschule edi: Klassen BF17, B, B197, BE und B96 – Ausbildung in Duisburg, Essen und Gelsenkirchen.',
			'blocks' => array(),
		),
		'motorrad' => array(
			'slug' => 'motorrad', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung', 'template' => 'page-bereich',
			'title' => 'Motorrad', 'menu' => 'Motorrad', 'icon' => 'zap',
			'lead'  => 'Vom Roller bis zur offenen Maschine – alle Zweiradklassen.',
			'desc'  => 'Motorradführerschein bei der Fahrschule edi: Klassen AM, A1, A2 und A – Ausbildung in Duisburg, Essen und Gelsenkirchen.',
			'blocks' => array(),
		),
		'lkw-bus' => array(
			'slug' => 'lkw-bus', 'parent' => '', 'nav' => 'sub', 'group' => 'ausbildung', 'template' => 'page-bereich',
			'title' => 'LKW & Bus', 'menu' => 'LKW & Bus', 'icon' => 'truck',
			'lead'  => 'Berufliche Fahrerlaubnisse für Güter- und Personenverkehr.',
			'desc'  => 'Lkw- und Busführerschein bei der Fahrschule edi: Klassen C1, C1E, C, CE, D und DE – Ausbildung im Ruhrgebiet.',
			'blocks' => array(),
		),

		/* ---------------- Klassen (Ebene 2) ----------------
		   Klassenliste nach PDF F3 (inc/pdf-texts.php), dazu B96.
		   QUELLEN je Zahl - nichts ohne Beleg (harte Regel 3):
		   - Mindestalter: § 10 Abs. 1 FeV; BF17: § 48a FeV
		   - Klassendefinition (lead): § 6 Abs. 1 FeV; B197/B96: Schlüssel-
		     zahlen nach Anlage 9 FeV
		   - Theorie (Grund- + Zusatzstoff): § 4 FahrschAusbO mit Anlage 1
		   - Pflichtfahrten (besondere Ausbildungsfahrten): § 5 FahrschAusbO
		     mit Anlage 4 - nur dort eingetragen, wo der Umfang sicher ist
		   - Prüfung: §§ 15-17 FeV (theoretische und praktische Prüfung)
		   - Voraussetzungen: Sehtest § 12, Erste Hilfe § 19, Antrag § 21,
		     Vorbesitz § 9, ärztliche Untersuchung Anlagen 5 und 6 FeV
		   - Fahrstunden-Richtwert und Richtpreis: nur aus dem ursprünglichen
		     Build (Klassen-Karten/Rechner) für B, BE, A, A1, AM, C.
		   Alle übrigen Felder bleiben leer und werden ausgeblendet. Werte
		   vor dem Livegang vom Inhaber bestätigen lassen (SETUP.md). */
		'klasse-bf17' => fahrschule_klasse( 'pkw', 'BF17', 'award', 'Begleitetes Fahren ab 17 – Ausbildung wie Klasse B.', array(
			'alter'           => '17 Jahre',
			'theorie'         => '14 Doppelstunden (12 Grundstoff, 2 Zusatzstoff)',
			'pflichtfahrten'  => '12 × 45 Min.: 5 Überland, 4 Autobahn, 3 bei Dunkelheit',
			'voraussetzungen' => 'Sehtest, Erste-Hilfe-Kurs, biometrisches Lichtbild und Antrag bei der Fahrerlaubnisbehörde. Bis zum 18. Geburtstag nur mit eingetragener Begleitperson.',
		) ),
		'klasse-b' => fahrschule_klasse( 'pkw', 'B', 'award', 'Pkw bis 3.500 kg zulässige Gesamtmasse und höchstens acht Sitzplätze außer dem Fahrersitz.', array(
			'alter'           => '18 Jahre (17 mit BF17)',
			'theorie'         => '14 Doppelstunden (12 Grundstoff, 2 Zusatzstoff)',
			'pflichtfahrten'  => '12 × 45 Min.: 5 Überland, 4 Autobahn, 3 bei Dunkelheit',
			'dauer'           => '32-40 Std.',
			'preis'           => '€1.650 – €2.100',
			'voraussetzungen' => 'Sehtest, Erste-Hilfe-Kurs, biometrisches Lichtbild und Antrag bei der Fahrerlaubnisbehörde Ihres Wohnorts.',
		) ),
		'klasse-b197' => fahrschule_klasse( 'pkw', 'B197', 'award', 'Ausbildung und Prüfung im Automatikfahrzeug – ohne Beschränkung auf Automatik (Schlüsselzahl 197).', array(
			'alter'           => '18 Jahre (17 mit BF17)',
			'voraussetzungen' => 'Wie bei Klasse B. Zusätzlich Fahrstunden im Schaltwagen und eine Testfahrt als Nachweis der Schaltkompetenz.',
		) ),
		'klasse-be' => fahrschule_klasse( 'pkw', 'BE', 'truck', 'Pkw mit Anhänger über 750 kg – Wohnwagen, Pferdeanhänger, Bootstrailer.', array(
			'alter'           => '18 Jahre',
			'dauer'           => '20-30 Std.',
			'preis'           => '€900 – €1.500',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse B.',
		) ),
		// B96 steht nicht in PDF F3 - Angebot vom Kunden bestätigen lassen.
		'klasse-b96' => fahrschule_klasse( 'pkw', 'B96', 'truck', 'Gespann bis 4.250 kg zulässige Gesamtmasse – mit Fahrerschulung statt Prüfung (Schlüsselzahl 96).', array(
			'alter'           => '18 Jahre',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse B.',
		) ),
		'klasse-am' => fahrschule_klasse( 'motorrad', 'AM', 'zap', 'Roller und Kleinkrafträder bis 45 km/h.', array(
			'alter'           => '15 Jahre',
			'dauer'           => '12-18 Std.',
			'preis'           => '€600 – €900',
			'voraussetzungen' => 'Sehtest, Erste-Hilfe-Kurs, biometrisches Lichtbild und Antrag bei der Fahrerlaubnisbehörde.',
		) ),
		'klasse-a1' => fahrschule_klasse( 'motorrad', 'A1', 'zap', 'Leichtkrafträder bis 125 cm³ und 11 kW.', array(
			'alter'           => '16 Jahre',
			'theorie'         => '16 Doppelstunden (12 Grundstoff, 4 Zusatzstoff)',
			'pflichtfahrten'  => '12 × 45 Min.: 5 Überland, 4 Autobahn, 3 bei Dunkelheit',
			'dauer'           => '15-25 Std.',
			'preis'           => '€800 – €1.200',
			'voraussetzungen' => 'Sehtest, Erste-Hilfe-Kurs, biometrisches Lichtbild und Antrag bei der Fahrerlaubnisbehörde.',
		) ),
		'klasse-a2' => fahrschule_klasse( 'motorrad', 'A2', 'zap', 'Motorräder bis 35 kW.', array(
			'alter'           => '18 Jahre',
			'theorie'         => '16 Doppelstunden (12 Grundstoff, 4 Zusatzstoff)',
			'pflichtfahrten'  => '12 × 45 Min.: 5 Überland, 4 Autobahn, 3 bei Dunkelheit',
			'voraussetzungen' => 'Sehtest, Erste-Hilfe-Kurs, biometrisches Lichtbild und Antrag bei der Fahrerlaubnisbehörde.',
		) ),
		'klasse-a' => fahrschule_klasse( 'motorrad', 'A', 'zap', 'Motorräder ohne Leistungsbeschränkung.', array(
			'alter'           => '24 Jahre (20 nach zwei Jahren A2)',
			'theorie'         => '16 Doppelstunden (12 Grundstoff, 4 Zusatzstoff)',
			'pflichtfahrten'  => '12 × 45 Min.: 5 Überland, 4 Autobahn, 3 bei Dunkelheit',
			'dauer'           => '25-35 Std.',
			'preis'           => '€1.200 – €1.800',
			'voraussetzungen' => 'Sehtest, Erste-Hilfe-Kurs, biometrisches Lichtbild und Antrag bei der Fahrerlaubnisbehörde.',
		) ),
		'klasse-c1' => fahrschule_klasse( 'lkw-bus', 'C1', 'truck', 'Lkw von 3.500 bis 7.500 kg zulässige Gesamtmasse.', array(
			'alter'           => '18 Jahre',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse B, ärztliche Untersuchung und Sehtest nach Anlage 5 und 6 FeV.',
		) ),
		'klasse-c1e' => fahrschule_klasse( 'lkw-bus', 'C1E', 'truck', 'C1-Fahrzeug mit Anhänger über 750 kg, Zug bis 12.000 kg.', array(
			'alter'           => '18 Jahre',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse C1.',
		) ),
		'klasse-c' => fahrschule_klasse( 'lkw-bus', 'C', 'truck', 'Lkw über 3.500 kg zulässige Gesamtmasse.', array(
			'alter'           => '21 Jahre (18 mit Berufskraftfahrer-Qualifikation)',
			'dauer'           => '30-40 Std.',
			'preis'           => '€1.800 – €2.500',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse B, ärztliche Untersuchung und Sehtest nach Anlage 5 und 6 FeV.',
		) ),
		'klasse-ce' => fahrschule_klasse( 'lkw-bus', 'CE', 'truck', 'Lkw mit Anhänger über 750 kg – Sattelzug und Hängerzug.', array(
			'alter'           => '21 Jahre (18 mit Berufskraftfahrer-Qualifikation)',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse C.',
		) ),
		'klasse-d' => fahrschule_klasse( 'lkw-bus', 'D', 'truck', 'Busse mit mehr als acht Sitzplätzen außer dem Fahrersitz.', array(
			'alter'           => '24 Jahre (21 mit Berufskraftfahrer-Qualifikation)',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse B, ärztliche Untersuchung und Sehtest nach Anlage 5 und 6 FeV.',
		) ),
		'klasse-de' => fahrschule_klasse( 'lkw-bus', 'DE', 'truck', 'Bus mit Anhänger über 750 kg.', array(
			'alter'           => '24 Jahre (21 mit Berufskraftfahrer-Qualifikation)',
			'voraussetzungen' => 'Fahrerlaubnis der Klasse D.',
		) ),

		/* ---------------- Standorte ---------------- */
		'standorte' => array(
			'slug' => 'standorte', 'template' => 'page-uebersicht', 'parent' => '', 'nav' => 'main', 'group' => 'standorte',
			'title' => 'Unsere Standorte',
			'menu'  => 'Standorte',
			'pdf'   => 'f8',
			'desc'  => 'Die Fahrschule edi finden Sie in Duisburg, Essen und Gelsenkirchen. Adressen, Telefonnummern und Öffnungszeiten aller Standorte.',
			'blocks' => array(
				array( 'fahrschule/locations', array( 'head' => '0' ) ),
			),
		),
		'duisburg' => array(
			'slug' => 'duisburg', 'template' => 'page-standort', 'parent' => 'standorte', 'nav' => 'sub', 'group' => 'standorte',
			'title' => 'Fahrschule edi Duisburg',
			'menu'  => 'Duisburg',
			'desc'  => 'Fahrschule edi in Duisburg, ' . fahrschule_pdf_location( 'Duisburg' )['street'] . ': Führerschein für PKW, Motorrad, LKW und Bus. Sicherheitstraining für Hafen und Logistik.',
			'blocks' => array(
				array( 'fahrschule/location-hero', array(
					'city' => 'Duisburg', 'icon' => 'shield', 'badge' => 'Hafen & Logistik',
					'heading' => 'Sicher fahren in Duisburg. Sicher fahren im Hafen. Sicher fahren bei edi.',
					'lead' => 'Spezialisiert auf Sicherheitstraining und Fahrpraxis im dichten Stadtverkehr. Ideal für logistische Anforderungen im Duisburger Hafen.',
				) ),
				array( 'fahrschule/location-content', array(
					'city' => 'Duisburg',
				) ),
				array( 'fahrschule/location-calculator', array() ),
			),
		),
		'essen' => array(
			'slug' => 'essen', 'template' => 'page-standort', 'parent' => 'standorte', 'nav' => 'sub', 'group' => 'standorte',
			'title' => 'Fahrschule edi Essen',
			'menu'  => 'Essen',
			'desc'  => 'Fahrschule edi in Essen, ' . fahrschule_pdf_location( 'Essen' )['street'] . ': moderne Fahrausbildung für alle Führerscheinklassen mit erfahrenen Fahrlehrern.',
			'blocks' => array(
				array( 'fahrschule/location-hero', array(
					'city' => 'Essen', 'icon' => 'truck', 'badge' => 'Modernität & Vielfalt',
					'heading' => 'Fahrschule edi Essen – Modernität und Vielfalt im Herzen des Ruhrgebiets.',
					'lead' => 'Moderne Ausbildung für alle Klassen mitten in Essen. Vielfältige Angebote, erfahrene Fahrlehrer und eine entspannte Lernatmosphäre.',
					'contact' => 'Betül Akar',
				) ),
				array( 'fahrschule/location-content', array(
					'city' => 'Essen',
				) ),
				array( 'fahrschule/location-calculator', array() ),
			),
		),
		'gelsenkirchen' => array(
			'slug' => 'gelsenkirchen', 'template' => 'page-standort', 'parent' => 'standorte', 'nav' => 'sub', 'group' => 'standorte',
			'title' => 'Fahrschule edi Gelsenkirchen',
			'menu'  => 'Gelsenkirchen',
			'desc'  => 'Fahrschule edi in Gelsenkirchen, ' . fahrschule_pdf_location( 'Gelsenkirchen' )['street'] . ': Führerschein mit persönlicher Betreuung – Motorrad, PKW, LKW und Bus.',
			'blocks' => array(
				array( 'fahrschule/location-hero', array(
					'city' => 'Gelsenkirchen', 'icon' => 'award', 'badge' => 'Zentrum & Tradition',
					'heading' => 'Fahrschule edi Gelsenkirchen – Zentrum und Tradition für Ihren Führerschein.',
					'lead' => 'Traditionsreiche Fahrschule im Zentrum von Gelsenkirchen. Persönliche Betreuung und bewährte Ausbildung auf modernem Niveau.',
					'contact' => 'Aslan Seymen',
				) ),
				array( 'fahrschule/location-content', array(
					'city' => 'Gelsenkirchen',
				) ),
				array( 'fahrschule/location-calculator', array() ),
			),
		),

		/* ---------------- Über uns ---------------- */
		'ueber-uns' => array(
			'slug' => 'ueber-uns', 'template' => 'page-uebersicht', 'parent' => '', 'nav' => 'main', 'group' => 'ueberuns',
			'title' => 'Über uns',
			'menu'  => 'Über uns',
			'pdf'   => 'f2',
			'desc'  => 'Über die Fahrschule & Fahrlehrerfachschule edi: seit vielen Jahren im Ruhrgebiet zuhause, mit erfahrenen Fahrlehrern und persönlicher Betreuung.',
			'blocks' => array(
				array( 'fahrschule/about-intro', array( 'head' => '0' ) ),
				array( 'fahrschule/about-timeline', array() ),
				array( 'fahrschule/about-philosophy', array() ),
				// Impressionen aus der Mediathek (edi-galerie-*); ohne Bilder ausgeblendet.
				array( 'fahrschule/galerie', array() ),
			),
		),
		'team' => array(
			'slug' => 'team', 'template' => 'page-team', 'parent' => 'ueber-uns', 'nav' => 'sub', 'group' => 'ueberuns',
			'title' => 'Unser Team',
			'menu'  => 'Team',
			'pdf'   => 'f9',
			'desc'  => 'Das Team der Fahrschule edi: engagierte Fahrlehrer und Mitarbeiter mit langjähriger Erfahrung an drei Standorten im Ruhrgebiet.',
			'blocks' => array(
				array( 'fahrschule/about-team', array( 'head' => '0' ) ),
				array( 'fahrschule/fahrlehrer-finder', array() ),
				array( 'fahrschule/team-spirit', array() ),
			),
		),
		'erfahrungen' => array(
			'slug' => 'erfahrungen', 'template' => 'page', 'parent' => 'ueber-uns', 'nav' => 'sub', 'group' => 'ueberuns',
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
			'slug' => 'faq', 'template' => 'page-faq', 'parent' => '', 'nav' => 'main', 'group' => '',
			'title' => 'Häufige Fragen',
			'menu'  => 'FAQ',
			'lead'  => 'Antworten auf die Fragen, die uns am häufigsten erreichen.',
			'desc'  => 'Häufige Fragen zur Fahrausbildung bei edi: Dauer, Mindestalter, Kosten, Unterrichtsmaterialien und Sprachen.',
			'blocks' => array(
				array( 'fahrschule/faq', array() ),
			),
		),
		'kontakt' => array(
			'slug' => 'kontakt', 'template' => 'page-kontakt', 'parent' => '', 'nav' => 'main', 'group' => '',
			'title' => 'Kontakt',
			'menu'  => 'Kontakt',
			'lead'  => 'Rufen Sie uns an, schreiben Sie uns oder kommen Sie direkt in einer unserer Filialen vorbei.',
			'desc'  => 'Kontakt zur Fahrschule edi: Telefon, E-Mail und Adressen der Standorte Duisburg, Essen und Gelsenkirchen.',
			'blocks' => array(
				array( 'fahrschule/cta', array() ),
				array( 'fahrschule/contact-form', array() ),
			),
		),
		'anmeldung' => array(
			'slug' => 'anmeldung', 'template' => 'page-kontakt', 'parent' => '', 'nav' => 'none',
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
			),
		),

		/* ---------------- Rechtliches ---------------- */
		'impressum' => array(
			'slug' => 'impressum', 'template' => 'page-recht', 'parent' => '', 'nav' => 'none',
			'title' => 'Impressum', 'menu' => 'Impressum',
			'desc'  => 'Impressum der Fahrschule & Fahrlehrerfachschule edi GmbH, Duisburg.',
			'blocks' => array( array( 'fahrschule/legal', array( 'kind' => 'impressum' ) ) ),
		),
		'datenschutz' => array(
			'slug' => 'datenschutz', 'template' => 'page-recht', 'parent' => '', 'nav' => 'none',
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
	$post = is_singular( 'page' ) ? get_queried_object() : null;
	// Editor-Vorschau: Der Block-Renderer (REST) kennt keine Hauptabfrage,
	// setzt aber $post auf die bearbeitete Seite, wenn der Editor post_id
	// mitschickt (assets/js/editor.js). So zeigt die Vorschau denselben
	// Seitenkontext wie das Frontend.
	if ( ! $post && defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post'];
		if ( $post instanceof WP_Post && (int) get_option( 'page_on_front' ) === (int) $post->ID ) { return 'home'; }
	}
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) { return ''; }
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
	$items = fs_breadcrumb_items();
	if ( ! $items ) { return ''; }

	ob_start(); ?>
	<nav class="fs-crumbs" aria-label="Brotkrumen-Navigation">
		<div class="fs-container">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Startseite</a></li>
				<?php foreach ( $items as $i => $item ) :
					$is_last = ( $i === count( $items ) - 1 ); ?>
					<li<?php echo $is_last ? ' aria-current="page"' : ''; ?>>
						<?php if ( $is_last ) : ?>
							<span><?php echo esc_html( $item[0] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $item[1] ); ?>"><?php echo esc_html( $item[0] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Pfad unterhalb der Startseite als Liste von [Beschriftung, URL].
 *
 * Registry-Seiten nehmen Menübeschriftung und Eltern aus inc/pages.php.
 * Seiten, die die Redaktion selbst anlegt, und Beiträge bekommen ihren Pfad
 * aus der WordPress-Seitenhierarchie - sonst fehlten ihnen die Brotkrumen.
 */
function fs_breadcrumb_items() {
	$key = fahrschule_current_key();
	if ( 'home' === $key ) { return array(); }
	$map = fahrschule_page_map();

	if ( $key && isset( $map[ $key ] ) ) {
		$items = array();
		for ( $cur = $key; $cur && isset( $map[ $cur ] ); $cur = $map[ $cur ]['parent'] ) {
			array_unshift( $items, array( $map[ $cur ]['menu'], fs_url( $cur ) ) );
		}
		return $items;
	}

	$post = is_singular() ? get_queried_object() : null;
	if ( ! $post instanceof WP_Post ) { return array(); }
	$items = array();
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $id ) {
		$items[] = array( get_the_title( $id ), get_permalink( $id ) );
	}
	$items[] = array( get_the_title( $post ), get_permalink( $post ) );
	return $items;
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
				// Auslieferungszustand: der Block-Stapel der Registry als Markup.
				// Danach gehört der Inhalt dem Editor - nichts überschreibt ihn.
				'post_content' => fahrschule_page_content( $key ),
				'meta_input'   => fahrschule_page_template_meta( $key ),
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

	update_option( 'fahrschule_content_version', FAHRSCHULE_CONTENT_VERSION );
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'fahrschule_create_pages' );

/**
 * Stand des ausgelieferten Seiteninhalts. Bis Theme 2.x lag der Inhalt nicht
 * in der Seite, sondern wurde zur Laufzeit aus der Registry gerendert
 * (post_content leer). Ab 3.0 rendert die Block-Vorlage post_content - leere
 * Seiten wären leer. Die Migration füllt sie einmalig.
 */
define( 'FAHRSCHULE_CONTENT_VERSION', 3 );

/** Block-Markup des Registry-Stapels einer Seite (ohne Seitenkopf - den liefert die Vorlage). */
function fahrschule_page_content( $key ) {
	$map = fahrschule_page_map();
	if ( empty( $map[ $key ]['blocks'] ) ) { return ''; }
	$blocks = array();
	foreach ( $map[ $key ]['blocks'] as $b ) {
		$blocks[] = array(
			'blockName'    => $b[0],
			'attrs'        => isset( $b[1] ) ? $b[1] : array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}
	return serialize_blocks( $blocks );
}

/** _wp_page_template für eine Registry-Seite; leer für Standard und Startseite. */
function fahrschule_page_template_meta( $key ) {
	$map = fahrschule_page_map();
	$tpl = isset( $map[ $key ]['template'] ) ? $map[ $key ]['template'] : 'page';
	return in_array( $tpl, array( 'page', 'front-page' ), true ) ? array() : array( '_wp_page_template' => $tpl );
}

/**
 * Einmal-Migration auf Inhaltsstand 3: befüllt NUR Seiten, deren Inhalt leer
 * ist, und setzt dort die Vorlage. Seiten mit Inhalt bleiben unangetastet -
 * dort hat die Redaktion gearbeitet. Läuft im Admin, bis der Stand erreicht ist.
 */
function fahrschule_migrate_content() {
	if ( (int) get_option( 'fahrschule_content_version', 0 ) >= FAHRSCHULE_CONTENT_VERSION ) { return; }
	foreach ( fahrschule_page_map() as $key => $page ) {
		$path = fahrschule_page_path( $key );
		$post = get_page_by_path( '' !== $path ? $path : 'startseite' );
		if ( ! $post instanceof WP_Post || '' !== trim( (string) $post->post_content ) ) { continue; }
		$content = fahrschule_page_content( $key );
		if ( '' !== $content ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_content' => $content ) );
		}
		foreach ( fahrschule_page_template_meta( $key ) as $meta => $value ) {
			$cur = get_post_meta( $post->ID, $meta, true );
			if ( '' === $cur || 'default' === $cur ) { update_post_meta( $post->ID, $meta, $value ); }
		}
	}
	update_option( 'fahrschule_content_version', FAHRSCHULE_CONTENT_VERSION );
}
add_action( 'admin_init', 'fahrschule_migrate_content' );
