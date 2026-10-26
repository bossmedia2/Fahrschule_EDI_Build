<?php
/**
 * Fahrschule edi – Zentrale Block-/Pattern-Konfiguration.
 *
 * EIN großes, strukturiertes Array beschreibt ALLE Blöcke. Daraus werden in
 * functions.php per foreach-Schleife sowohl die Gutenberg-Blöcke (register_block_type)
 * als auch die Block-Patterns (register_block_pattern) erzeugt.
 *
 * Aufbau pro Eintrag:
 *   title       – Anzeigename im Editor
 *   description – Kurzbeschreibung
 *   icon        – Dashicon-Slug
 *   keywords    – Suchbegriffe im Inserter
 *   render      – Name der Render-Callback-Funktion (siehe inc/render-callbacks.php)
 *   pattern     – true => zusätzlich als Block-Pattern registrieren
 *   page        – Zuordnung (nur Doku/Gruppierung)
 *   fields      – editierbare Attribute:  attr => [ default, label, control(, options) ]
 *                 Daraus werden Block-Attribute UND die Editor-Steuerelemente abgeleitet.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function fahrschule_blocks_config() {

	/* Fahrlehrer-Team (Bild-Briefing Oktober 2026): Name, Foto-Slug,
	   Standort, Klassen, Rolle, PDF-Gruppe. Vorgabe des Fahrlehrer-Finders. */
	$fs_lehrer = array(
		array( 'Aslan', 'aslan', 'Gelsenkirchen', 'A, B', 'Leiter & Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Yalcin', 'yalcin', 'Duisburg', 'A, B, BE', 'Leiter & Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Adis', 'adis', 'Gelsenkirchen', 'A', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Ali Ihsan', 'ali-ihsan', 'Gelsenkirchen', 'B, BE', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Baris', 'baris', 'Gelsenkirchen', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Birol', 'birol', 'Gelsenkirchen', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Fergan', 'fergan', 'Gelsenkirchen', 'A, B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Nebahat', 'nebahat', 'Gelsenkirchen', 'B', 'Fahrlehrerin', 'Dozenten & Ausbilder' ),
		array( 'Ramazan', 'ramazan', 'Gelsenkirchen', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Sakit', 'sakit', 'Gelsenkirchen', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Ömer', 'oemer', 'Duisburg', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Rahel', 'rahel', 'Duisburg', 'B, BE', 'Fahrlehrerin', 'Dozenten & Ausbilder' ),
		array( 'Tammam', 'tammam', 'Duisburg', 'B, BE', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Devran', 'devran', 'Essen', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Mustafa', 'mustafa', 'Essen', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Abdullah', 'abdullah', '', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
		array( 'Mesut', 'mesut', '', 'B', 'Fahrlehrer', 'Dozenten & Ausbilder' ),
	);

	$t  = function ( $default, $label, $control = 'text', $options = array() ) {
		return array( 'default' => $default, 'label' => $label, 'control' => $control, 'options' => $options );
	};

	/*
	 * Wiederholfeld (Repeater): eine Liste gleichartiger Zeilen, die der
	 * Redakteur erweitern, umsortieren und löschen kann. Ersetzt die Arrays,
	 * die bisher fest in den Render-Callbacks standen und im Editor
	 * unerreichbar waren.
	 *
	 * $columns – Spaltenbeschriftungen, in der Reihenfolge der Zeilenwerte
	 * $rows    – Vorgabezeilen; jede Zeile ist eine Liste in Spaltenreihenfolge
	 */
	// $probe (nur für die Prüfkette): gültige Testzeile und der Text, der dann
	// in der Ausgabe stehen muss - für Spalten, die Schlüssel statt freien
	// Text enthalten (eine Fantasie-Seite würde zu Recht nicht angezeigt).
	$rep = function ( $rows, $label, $columns, $probe = null ) {
		return array(
			'default' => $rows,
			'label'   => $label,
			'control' => 'repeater',
			'columns' => $columns,
			'options' => array(),
			'probe'   => $probe,
		);
	};

	/* Kundentexte (PDF) – dieselbe Quelle wie die Render-Callbacks, siehe inc/pdf-texts.php. */
	$pdf  = 'fahrschule_pdf';
	$head = $t( '1', 'Abschnittskopf (Label + Überschrift) anzeigen', 'select', array( '1' => 'Ja', '0' => 'Nein – steht bereits im Seitenkopf' ) );

	$bloecke = array(

		/* ---- Hierarchie: Bereiche (Ebene 1) & Klassen (Ebene 2) ---- */
		'fahrschule/bereich-uebersicht' => array(
			'title' => 'Bereich: Klassen-Übersicht', 'description' => 'Karten aller Klassen, die diesem Bereich untergeordnet sind. Die Liste entsteht aus der Seitenstruktur.',
			'icon' => 'grid-view', 'keywords' => array( 'bereich', 'klassen', 'übersicht', 'kategorie' ),
			'render' => 'fs_render_bereich_uebersicht', 'pattern' => true, 'may_be_empty' => true, 'page' => 'Bereich',
			'fields' => array(
				'bereich'  => $t( '', 'Bereich (Seitenschlüssel, leer = aktuelle Seite)', 'text' ),
				'eyebrow'  => $t( 'Führerscheinklassen', 'Eyebrow', 'text' ),
				'heading'  => $t( 'Klassen in diesem Bereich', 'Überschrift', 'text' ),
				'subtitle' => $t( '', 'Untertitel', 'textarea' ),
				'icon'     => $t( 'award', 'Icon für Karten ohne eigenes Icon', 'text' ),
				'more'     => $t( 'Zur Klasse', 'Linktext auf der Karte', 'text' ),
			),
		),
		'fahrschule/kurs-details' => array(
			'title' => 'Kurs: Eckdaten & Inhalte', 'description' => 'Textkarten (Voraussetzungen, Ablauf, Förderung) mit Seitenleiste „Kurzdaten“: Mindestalter, Theorie, Pflichtfahrten, Fahrstunden, Prüfung, Richtpreis und Verweis auf die Preisseite. Jeder Slot ist einzeln leerbar.',
			'icon' => 'id-alt', 'keywords' => array( 'kurs', 'klasse', 'details', 'voraussetzungen' ),
			'render' => 'fs_render_kurs_details', 'pattern' => true, 'may_be_empty' => true, 'page' => 'Kurs',
			'fields' => array(
				'eyebrow'         => $t( '', 'Eyebrow', 'text' ),
				'heading'         => $t( 'Das Wichtigste auf einen Blick', 'Überschrift', 'text' ),
				'alter'           => $t( '', 'Mindestalter', 'text' ),
				'theorie'         => $t( '', 'Theorieunterricht', 'text' ),
				'pflichtfahrten'  => $t( '', 'Pflichtfahrten', 'text' ),
				'dauer'           => $t( '', 'Fahrstunden (Richtwert)', 'text' ),
				'pruefung'        => $t( '', 'Prüfung', 'text' ),
				'preis'           => $t( '', 'Richtpreis', 'text' ),
				'preis_link'      => $t( '@preise', 'Preisverweis: Link', 'text' ),
				'preis_label'     => $t( 'Alle Preise & Rechner', 'Preisverweis: Text', 'text' ),
				'voraussetzungen' => $t( '', 'Voraussetzungen', 'textarea' ),
				'ablauf'          => $t( '', 'Ablauf der Ausbildung', 'textarea' ),
				'foerderung'      => $t( '', 'Förderung & Finanzierung', 'textarea' ),
				'hinweis'         => $t( '', 'Abschließender Hinweis', 'textarea' ),
			),
		),

		/* ---- Team & Galerie ---- */
		'fahrschule/fahrlehrer-finder' => array(
			'title' => 'Fahrlehrer-Finder', 'description' => 'Team nach den drei PDF-Gruppen (Büro & Ansprechpartner, Geschäftsleitung, Dozenten & Ausbilder), filterbar nach Standort, Klasse und Fahrlehrerin. Fotos über edi-team-<slug> aus der Mediathek. Gruppen ohne Personen entfallen.',
			'icon' => 'groups', 'keywords' => array( 'team', 'fahrlehrer', 'filter', 'standort' ),
			'render' => 'fs_render_fahrlehrer_finder', 'pattern' => true, 'may_be_empty' => true, 'page' => 'Team',
			'fields' => array(
				'eyebrow'  => $t( 'Unser Team', 'Eyebrow', 'text' ),
				'heading'  => $t( 'Finden Sie Ihren Fahrlehrer', 'Überschrift', 'text' ),
				'subtitle' => $t( 'Filtern Sie nach Standort und Führerscheinklasse.', 'Untertitel', 'textarea' ),
				'empty'    => $t( 'Für diese Auswahl ist gerade niemand hinterlegt.', 'Text, wenn kein Treffer', 'text' ),
				'people'   => $rep( array_merge( fs_team_ansprechpartner( $fs_lehrer ), $fs_lehrer, array_map(
					// Geschäftsleitung aus den Stammdaten (Impressum), ohne Foto und Klassen.
					static function ( $n ) { return array( $n, '', '', '', 'Geschäftsführung', 'Geschäftsleitung' ); },
					fahrschule_business_data()['management']
				) ), 'Person', array( 'Name', 'Dateiname (edi-team-…)', 'Standort', 'Klassen (B, BE, A)', 'Rolle', 'Gruppe (Büro & Ansprechpartner / Geschäftsleitung / Dozenten & Ausbilder)' ) ),
			),
		),
		'fahrschule/galerie' => array(
			'title' => 'Foto-Galerie', 'description' => 'Zeigt live alle Mediathek-Bilder mit Namen edi-galerie-*. Optional nach Kategorie (edi-galerie-<kategorie>-<nr>) filterbar; Filter erscheinen nur für belegte Kategorien. Mit Lightbox.',
			'icon' => 'format-gallery', 'keywords' => array( 'galerie', 'fotos', 'bilder', 'lightbox' ),
			'render' => 'fs_render_galerie', 'pattern' => true, 'may_be_empty' => true, 'page' => 'Galerie',
			'fields' => array(
				'eyebrow'    => $t( 'Einblicke', 'Eyebrow', 'text' ),
				'heading'    => $t( 'Fahrschule edi in Bildern', 'Überschrift', 'text' ),
				'subtitle'   => $t( '', 'Untertitel', 'textarea' ),
				'batch'      => $t( '24', 'Bilder je Nachladeschritt', 'text' ),
				'kategorien' => $rep( array(
					array( 'Fahrstunde', 'fahrstunde' ),
					array( 'Fuhrpark', 'fuhrpark' ),
					array( 'Theorie', 'theorie' ),
					array( 'Team', 'team' ),
					array( 'Standort', 'standort' ),
				), 'Kategorie', array( 'Beschriftung', 'Präfix (edi-galerie-…)' ) ),
			),
		),

		/* ---- Global / Header / Footer ---- */
		'fahrschule/navigation' => array(
			'title' => 'Navigation', 'description' => 'Fixierte Kopfzeile mit Logo, Menü & Standorte-Dropdown.',
			'icon' => 'menu', 'keywords' => array( 'navigation', 'header', 'menu', 'kopfzeile' ),
			'render' => 'fs_render_navigation', 'pattern' => true, 'page' => 'Global',
			'fields' => array( 'logo' => $t( fs_media( 'logo' ), 'Logo-URL', 'url' ) ),
		),
		'fahrschule/footer' => array(
			'title' => 'Footer', 'description' => 'Fußzeile mit Navigation, Kontakt & Zertifizierung.',
			'icon' => 'editor-insertmore', 'keywords' => array( 'footer', 'fußzeile' ),
			'render' => 'fs_render_footer', 'pattern' => true, 'page' => 'Global',
			'fields' => array( 'logo' => $t( fs_media( 'logo' ), 'Logo-URL', 'url' ) ),
		),
		'fahrschule/tiktok-badge' => array(
			'title' => 'TikTok Badge', 'description' => 'Schwebender TikTok-Button (schließbar).',
			'icon' => 'format-video', 'keywords' => array( 'tiktok', 'social', 'badge' ),
			'render' => 'fs_render_tiktok_badge', 'pattern' => true, 'page' => 'Global',
			'fields' => array(
				'url'   => $t( 'https://www.tiktok.com/@edi_fahrschule', 'TikTok-URL', 'url' ),
				'line1' => $t( 'Besuchen Sie uns', 'Zeile 1', 'text' ),
				'line2' => $t( 'auf TikTok', 'Zeile 2', 'text' ),
			),
		),
		'fahrschule/cookie-banner' => array(
			'title' => 'Cookie-Banner', 'description' => 'DSGVO-Cookie-Hinweis (Notwendig / Alle akzeptieren).',
			'icon' => 'privacy', 'keywords' => array( 'cookie', 'dsgvo', 'consent', 'datenschutz' ),
			'render' => 'fs_render_cookie_banner', 'pattern' => true, 'page' => 'Global',
			'fields' => array(),
		),

		/* ---- Startseite ---- */
		'fahrschule/video-hero' => array(
			'title' => 'Video Hero', 'description' => 'Vollflächiger Hero mit Hintergrundvideo und Standbild (Startseite) – PDF-Abschnitt F1. Video und Standbild kommen aus der Mediathek (edi-video-hero.mp4, edi-video-hero-standbild).',
			'icon' => 'video-alt3', 'keywords' => array( 'hero', 'video', 'start' ),
			'render' => 'fs_render_video_hero', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'badge'      => $t( 'Seit über 30 Jahren für Sie da', 'Badge', 'text' ),
				'heading'    => $t( $pdf( 'f1' )['headline'], 'Überschrift', 'text' ),
				'highlight'  => $t( $pdf( 'f1' )['highlight'], 'Hervorgehobenes Wort', 'text' ),
				'text'       => $t( implode( "\n\n", $pdf( 'f1' )['paragraphs'] ), 'Text (Absätze durch Leerzeile trennen)', 'textarea' ),
				'btn1_label' => $t( 'Jetzt anmelden', 'Button 1 Text', 'text' ),
				'btn1_url'   => $t( '@anmeldung', 'Button 1 Link', 'text' ),
				'btn2_label' => $t( 'Mehr erfahren', 'Button 2 Text', 'text' ),
				'btn2_url'   => $t( '@klassen', 'Button 2 Link', 'text' ),
				'video_url'  => $t( fs_media( 'video_hero' ), 'Video-URL (Mediathek: edi-video-hero.mp4)', 'url' ),
				'standbild'  => $t( fs_media( 'video_hero_bild' ), 'Standbild-URL (Poster, Editor, reduzierte Bewegung; Mediathek: edi-video-hero-standbild)', 'url' ),
			),
		),
		'fahrschule/hero' => array(
			'title' => 'Hero (hell)', 'description' => 'Helle Hero-Variante mit pinkem Hintergrund.',
			'icon' => 'cover-image', 'keywords' => array( 'hero', 'header' ),
			'render' => 'fs_render_hero', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'badge'      => $t( 'Seit über 30 Jahren', 'Badge', 'text' ),
				'heading'    => $t( $pdf( 'f1' )['headline'], 'Überschrift', 'text' ),
				'highlight'  => $t( $pdf( 'f1' )['highlight'], 'Hervorgehobenes Wort', 'text' ),
				'text'       => $t( $pdf( 'f1' )['paragraphs'][0], 'Text', 'textarea' ),
				'btn1_label' => $t( 'Jetzt anmelden', 'Button 1 Text', 'text' ),
				'btn1_url'   => $t( '@anmeldung', 'Button 1 Link', 'text' ),
				'btn2_label' => $t( 'Kostenlose Beratung', 'Button 2 Text', 'text' ),
				'btn2_url'   => $t( '@klassen', 'Button 2 Link', 'text' ),
			),
		),
		'fahrschule/license-classes' => array(
			'title' => 'Führerscheinklassen (Karten)', 'description' => 'Karten-Raster mit 6 Führerscheinklassen inkl. Richtpreis.',
			'icon' => 'id-alt', 'keywords' => array( 'klassen', 'führerschein', 'preise' ),
			'render' => 'fs_render_license_classes', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'classes'  => $rep( array(
					array( 'Klasse B', 'PKW', 'Personenkraftwagen bis 3.500 kg', '32-40 Std.', '€1.650 - €2.100', '18 Jahre' ),
					array( 'Klasse BE', 'PKW + Anhänger', 'Motorwagen mit Anhänger', '20-30 Std.', '€900 - €1.500', '18 Jahre' ),
					array( 'Klasse A', 'Motorrad', 'Motorräder über 35 kW', '25-35 Std.', '€1.200 - €1.800', '24 Jahre' ),
					array( 'Klasse A1', 'Leichtkraftrad', 'Motorräder bis 125 ccm', '15-25 Std.', '€800 - €1.200', '16 Jahre' ),
					array( 'Klasse AM', 'Roller/Mofa', 'Mopeds und leichte Roller', '12-18 Std.', '€600 - €900', '15 Jahre' ),
					array( 'Klasse C', 'LKW', 'Lastkraftwagen', '30-40 Std.', '€1.800 - €2.500', '18 Jahre' ),
				), 'Führerscheinklasse', array( 'Klasse', 'Kategorie', 'Beschreibung', 'Dauer', 'Richtpreis', 'Mindestalter' ) ),
				'eyebrow'  => $t( 'Führerscheinklassen', 'Eyebrow', 'text' ),
				'heading'  => $t( 'Alle Führerscheinklassen', 'Überschrift', 'text' ),
				'subtitle' => $t( 'Von der Richtpreis-Berechnung bis zur professionellen Ausbildung', 'Untertitel', 'textarea' ),
			),
		),
		'fahrschule/calculator' => array(
			'title' => 'Führerschein-Rechner', 'description' => 'Interaktiver Preisrechner (Klasse / Typ / Getriebe).',
			'icon' => 'calculator', 'keywords' => array( 'rechner', 'preis', 'kalkulator' ),
			'render' => 'fs_render_calculator', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'eyebrow'  => $t( 'Transparente Preise', 'Eyebrow', 'text' ),
				'heading'  => $t( 'Führerschein-Rechner', 'Überschrift', 'text' ),
				'subtitle' => $t( 'Kalkulieren Sie schnell und einfach Ihren Gesamtpreis', 'Untertitel', 'textarea' ),
			),
		),
		'fahrschule/locations' => array(
			'title' => 'Standorte (Übersicht)', 'description' => 'Drei Standort-Karten mit Adressen, Telefon und E-Mail – PDF-Abschnitt F8.',
			'icon' => 'location-alt', 'keywords' => array( 'standorte', 'filialen', 'kontakt' ),
			'render' => 'fs_render_locations', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'eyebrow'  => $t( $pdf( 'f8' )['label'], 'Eyebrow', 'text' ),
				'heading'  => $t( $pdf( 'f8' )['headline'], 'Überschrift', 'text' ),
				'subtitle' => $t( $pdf( 'f8' )['paragraphs'][0], 'Untertitel', 'textarea' ),
				'head'     => $head,
				'kompakt'  => $t( '0', 'Darstellung', 'select', array( '0' => 'Voll (Fotos, Absatz, Kontakt-Knopf)', '1' => 'Kompakt (Link zur Standortseite)' ) ),
			),
		),
		'fahrschule/team-spirit' => array(
			'title' => 'Team Spirit', 'description' => 'Video + Werte-Raster + Zitat.',
			'icon' => 'groups', 'keywords' => array( 'team', 'video', 'werte' ),
			'render' => 'fs_render_team_spirit', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'values'   => $rep( array(
					array( 'heart', 'Mit Herz dabei', 'Wir lieben, was wir tun. Ihre Erfolgsgeschichte ist auch unsere.' ),
					array( 'zap', 'Energie & Motivation', 'Positive Energie steckt an. Unsere Begeisterung überträgt sich auf Sie.' ),
					array( 'users', 'Gemeinschaft', 'Wir sind eine Familie, die sich gegenseitig unterstützt.' ),
				), 'Wert', array( 'Icon (heart, zap, users …)', 'Titel', 'Text' ) ),
				'badge'     => $t( 'Unser Team Spirit', 'Badge', 'text' ),
				'heading'   => $t( 'Eine Familie voller Leidenschaft', 'Überschrift', 'text' ),
				'highlight' => $t( 'Familie', 'Hervorgehobenes Wort', 'text' ),
				'text'      => $t( 'Bei Fahrschule edi sind wir nicht nur Instruktoren – wir sind Mentoren, Motivatoren und Ihre zuverlässigen Partner auf dem Weg zum Führerschein. Unsere offene Atmosphäre, gepaart mit professioneller Kompetenz, macht die Fahrausbildung zu einem positiven Erlebnis.', 'Text', 'textarea' ),
				'quote'     => $t( 'Bei uns lernen Sie nicht nur fahren – Sie werden Teil einer Gemeinschaft, die Sie auf Ihrem Weg begleitet und Sie anfeuert.', 'Zitat', 'textarea' ),
				'quote_by'  => $t( '– Das edi Team', 'Zitat-Autor', 'text' ),
				'video_url' => $t( fs_media( 'video_team' ), 'Video-URL', 'url' ),
			),
		),
		'fahrschule/fleet-facilities' => array(
			'title' => 'Flotte & Einrichtungen', 'description' => 'Galerie Fuhrpark + Räumlichkeiten + CTA-Band.',
			'icon' => 'car', 'keywords' => array( 'flotte', 'fuhrpark', 'einrichtung' ),
			'render' => 'fs_render_fleet_facilities', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(),
		),
		'fahrschule/testimonials' => array(
			'title' => 'Testimonials', 'description' => 'Kundenbewertungen mit Sternen – aktuell leer (keine erfundenen Zitate), blendet sich ohne echte Einträge automatisch aus.',
			'icon' => 'star-filled', 'keywords' => array( 'bewertungen', 'kunden', 'testimonials' ),
			'render' => 'fs_render_testimonials', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'eyebrow'  => $t( 'Was unsere Schüler sagen', 'Eyebrow', 'text' ),
				'heading'  => $t( 'Kundenbewertungen', 'Überschrift', 'text' ),
				'subtitle' => $t( 'Lesen Sie, was unsere zufriedenen Fahrschüler über uns berichten', 'Untertitel', 'textarea' ),
			),
		),
		/* Slug bleibt instagram-slider: Umbenennen bräche jede Seite, auf der der
		   Block steht. Inhaltlich ist es der Social-Feed beider Plattformen. */
		'fahrschule/instagram-slider' => array(
			'title' => 'Social-Feed (Instagram/TikTok)', 'description' => 'Karussell aus Kacheln; jede Kachel trägt ihre Plattform (tiktok/instagram), ein Bild aus der Mediathek, einen Link und eine Bildbeschreibung. Kein Embed, kein Abruf bei TikTok oder Instagram. Ohne Kachel mit Bild und Beschreibung entfällt die Sektion.',
			'icon' => 'share', 'keywords' => array( 'instagram', 'tiktok', 'social', 'slider' ),
			'render' => 'fs_render_instagram_slider', 'pattern' => true, 'may_be_empty' => true, 'page' => 'Erfahrungen',
			'fields' => array(
				'heading'      => $t( 'Folgen Sie uns', 'Überschrift', 'text' ),
				'highlight'    => $t( '', 'Hervorgehobenes Wort', 'text' ),
				'subtitle'     => $t( 'Einblicke in den Alltag der Fahrschule edi – Tipps und Neuigkeiten direkt in Ihrem Feed.', 'Untertitel', 'textarea' ),
				// Profile aus den Stammdaten (inc/seo.php). Leerer @-Name = Knopf entfällt.
				'instaHandle'  => $t( fs_social_profil( 'instagram' )['handle'], 'Instagram: @-Name (leer = kein Knopf)', 'text' ),
				'instaUrl'     => $t( fs_social_profil( 'instagram' )['url'], 'Instagram: Profil-Link', 'url' ),
				'tiktokHandle' => $t( fs_social_profil( 'tiktok' )['handle'], 'TikTok: @-Name (leer = kein Knopf)', 'text' ),
				'tiktokUrl'    => $t( fs_social_profil( 'tiktok' )['url'], 'TikTok: Profil-Link', 'url' ),
				// Kacheln: bewusst ohne Bildbeschreibung vorbelegt - eine Kachel
				// erscheint erst, wenn Bild UND Beschreibung da sind.
				'items'        => $rep(
					array_map( static function ( $n ) { return array( 'instagram', sprintf( 'edi-social-%03d', $n ), '', '' ); }, range( 1, 6 ) ),
					'Kachel',
					array( 'Plattform (tiktok / instagram)', 'Bild: Medien-Slug, z. B. edi-social-001 (quadratisch, mind. 1080 px)', 'Link zum Beitrag (leer = Profil)', 'Bildbeschreibung (Pflicht – was ist auf dem Bild zu sehen?)' ),
					array( array( 'tiktok', 'edi-logo', '', 'ZZPROBEZZ' ), 'ZZPROBEZZ' )
				),
				// Bis 3.0: einzelnes Instagram-Profil. Bleibt registriert, damit
				// Gutenberg einen gespeicherten Wert beim Speichern nicht verwirft;
				// im Editor ausgeblendet, beim Rendern Rückfall für instaUrl.
				'url'          => $t( '', 'Instagram-URL (alt)', 'url' ) + array( 'legacy' => true ),
			),
		),
		'fahrschule/faq' => array(
			'title' => 'FAQ', 'description' => 'Akkordeon mit häufigen Fragen.',
			'icon' => 'editor-help', 'keywords' => array( 'faq', 'fragen', 'akkordeon' ),
			'render' => 'fs_render_faq', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'eyebrow' => $t( 'Häufig gestellte Fragen', 'Eyebrow', 'text' ),
				'heading' => $t( 'FAQ', 'Überschrift', 'text' ),
			),
		),
		'fahrschule/cta' => array(
			'title' => 'CTA / Kontakt', 'description' => 'Call-to-Action mit Kontaktdaten & Statistiken.',
			'icon' => 'megaphone', 'keywords' => array( 'cta', 'kontakt', 'anmeldung' ),
			'render' => 'fs_render_cta', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'stats'    => $rep( array(
					// Nur Belegtes (harte Regel 3): Gründungsjahr und Standorte aus inc/seo.php.
					// Hier standen zuvor „5000+ Bestandene Prüfungen“ und „5 Sprachen“ ohne Quelle.
					array( ( (int) gmdate( 'Y' ) - (int) fahrschule_business_data()['founded'] ) . '+', 'Jahre Erfahrung' ),
					array( (string) count( fahrschule_business_data()['locations'] ), 'Standorte' ),
				), 'Kennzahl', array( 'Wert', 'Beschriftung' ) ),
				'heading'   => $t( 'Bereit für Ihren Führerschein?', 'Überschrift', 'text' ),
				'highlight' => $t( 'Führerschein?', 'Hervorgehobenes Wort', 'text' ),
				'lead'      => $t( 'Starten Sie noch heute Ihre Fahrausbildung. Unsere Experten in Gelsenkirchen, Essen und Duisburg stehen Ihnen zur Verfügung.', 'Lead-Text', 'textarea' ),
				'phone'     => $t( '+49 (0) 203 57 85 703', 'Telefon', 'text' ),
				'email'     => $t( 'info@fahrschule-edi.de', 'E-Mail', 'text' ),
			),
		),

		/* ---- Über uns ---- */
		'fahrschule/about-intro' => array(
			'title' => 'Über uns – Intro', 'description' => 'Einleitung „Ihre Fahrschule im Ruhrgebiet“ – PDF-Abschnitt F2.',
			'icon' => 'info', 'keywords' => array( 'über uns', 'ruhrgebiet', 'intro' ),
			'render' => 'fs_render_about_intro', 'pattern' => true, 'page' => 'Über uns',
			'fields' => array(
				'eyebrow'   => $t( $pdf( 'f2' )['label'], 'Eyebrow', 'text' ),
				'heading'   => $t( $pdf( 'f2' )['headline'], 'Überschrift', 'text' ),
				'highlight' => $t( $pdf( 'f2' )['highlight'], 'Hervorgehobenes Wort', 'text' ),
				'p1'        => $t( $pdf( 'f2' )['paragraphs'][0], 'Absatz 1', 'textarea' ),
				'p2'        => $t( $pdf( 'f2' )['paragraphs'][1], 'Absatz 2', 'textarea' ),
				'p3'        => $t( $pdf( 'f2' )['paragraphs'][2], 'Absatz 3', 'textarea' ),
				'head'      => $head,
			),
		),
		'fahrschule/about-timeline' => array(
			'title' => 'Über uns – Timeline', 'description' => 'Vertikale Zeitleiste der Firmengeschichte.',
			'icon' => 'clock', 'keywords' => array( 'timeline', 'geschichte', 'über uns' ),
			'render' => 'fs_render_about_timeline', 'pattern' => true, 'page' => 'Über uns',
			'fields' => array(
				'items'    => $rep( array(
					// Nur Belegtes: Gründung (inc/seo.php), heutige Standorte (PDF F8).
					// Die Stationen 2000er/2010 und die Superlative zur Absolventenzahl
					// hatten keine Quelle und sind entfallen.
					array( fahrschule_business_data()['founded'], 'Gründung', 'Die Fahrschule edi wird gegründet.' ),
					array( 'Heute', 'Drei Standorte im Ruhrgebiet', fs_aufzaehlung( array_keys( fahrschule_pdf( 'f8' )['locations'] ) ) . ' – Ausbildung für Motorrad, PKW, LKW und Bus.' ),
				), 'Station', array( 'Jahr', 'Titel', 'Text' ) ),
				'heading'   => $t( 'Tradition trifft Moderne', 'Überschrift', 'text' ),
				'highlight' => $t( 'Moderne', 'Hervorgehobenes Wort', 'text' ),
				'subtitle'  => $t( 'Über 30 Jahre Erfahrung in der Fahrerausbildung im Ruhrgebiet', 'Untertitel', 'textarea' ),
			),
		),
		'fahrschule/about-philosophy' => array(
			'title' => 'Über uns – Philosophie', 'description' => 'Drei Werte-Karten (Philosophie).',
			'icon' => 'lightbulb', 'keywords' => array( 'philosophie', 'werte', 'über uns' ),
			'render' => 'fs_render_about_philosophy', 'pattern' => true, 'page' => 'Über uns',
			'fields' => array(
				'items'    => $rep( array(
					array( 'heart', 'Individuelle Betreuung', 'Jeder Fahrschüler erhält persönliche Aufmerksamkeit und wird individuell begleitet – auch in schwierigen Fällen.' ),
					array( 'zap', 'Moderne Methoden', 'Wir kombinieren bewährte Ausbildungsmethoden mit modernen digitalen Lerntools für optimale Erfolge.' ),
					array( 'users', 'Erfahrenes Team', 'Unsere Fahrlehrer sind kompetent, hoch motiviert und verfügen über umfangreiche Erfahrung.' ),
				), 'Grundsatz', array( 'Icon', 'Titel', 'Text' ) ),
				'heading'   => $t( 'Unsere Philosophie', 'Überschrift', 'text' ),
				'highlight' => $t( 'Philosophie', 'Hervorgehobenes Wort', 'text' ),
				'subtitle'  => $t( 'Mit Kompetenz und Betreuung bis zum Führerschein', 'Untertitel', 'textarea' ),
			),
		),
		'fahrschule/about-team' => array(
			'title' => 'Über uns – Team', 'description' => '„Persönlich. Geduldig. Erfahren.“ mit den drei Team-Gruppen – PDF-Abschnitt F9.',
			'icon' => 'groups', 'keywords' => array( 'team', 'mitarbeiter', 'über uns' ),
			'render' => 'fs_render_about_team', 'pattern' => true, 'page' => 'Über uns',
			'fields' => array(
				'eyebrow' => $t( $pdf( 'f9' )['label'], 'Eyebrow', 'text' ),
				'heading' => $t( $pdf( 'f9' )['headline'], 'Überschrift', 'text' ),
				'p1'      => $t( $pdf( 'f9' )['paragraphs'][0], 'Absatz 1', 'textarea' ),
				'p2'      => $t( $pdf( 'f9' )['paragraphs'][1], 'Absatz 2', 'textarea' ),
				'footer'  => $t( $pdf( 'f9' )['closing'], 'Abschlusstext', 'textarea' ),
				'head'    => $head,
			),
		),

		/* ---- Testimonials-Seite ---- */
		'fahrschule/testimonials-hero' => array(
			'title' => 'Testimonials – Hero', 'description' => 'Kopf der Erfahrungen-Seite: 30+ Jahre Erfahrung seit der Gründung 1993.',
			'icon' => 'awards', 'keywords' => array( 'testimonials', 'hero', 'zähler' ),
			'render' => 'fs_render_testimonials_hero', 'pattern' => true, 'page' => 'Testimonials',
			'fields' => array(
				// Bis 3.0 stand hier eine unbelegte Absolventenzahl (SYSTEM-REPORT 2.4);
				// korrigiert war nur die Render-Vorgabe, nicht diese Konfiguration.
				'heading'   => $t( 'Über 30 Jahre Erfahrung im Ruhrgebiet', 'Überschrift', 'text' ),
				'highlight' => $t( '30 Jahre Erfahrung', 'Hervorgehobenes Wort', 'text' ),
				'subtitle'  => $t( 'Seit 1993 begleiten wir Fahrschülerinnen und Fahrschüler in Duisburg, Essen und Gelsenkirchen zum Führerschein.', 'Untertitel', 'textarea' ),
			),
		),
		'fahrschule/graduates-gallery' => array(
			'title' => 'Absolventen-Galerie', 'description' => 'Drei Bildkacheln erfolgreicher Absolventen.',
			'icon' => 'format-gallery', 'keywords' => array( 'absolventen', 'galerie', 'testimonials' ),
			'render' => 'fs_render_graduates_gallery', 'pattern' => true, 'page' => 'Testimonials',
			'fields' => array(
				'heading'   => $t( 'Unsere Absolventen im Überblick', 'Überschrift', 'text' ),
				'highlight' => $t( 'Absolventen', 'Hervorgehobenes Wort', 'text' ),
			),
		),
		'fahrschule/testimonials-detailed' => array(
			'title' => 'Testimonials – Detailraster', 'description' => 'Sechs ausführliche Bewertungen inkl. Klasse.',
			'icon' => 'star-half', 'keywords' => array( 'bewertungen', 'raster', 'testimonials' ),
			'render' => 'fs_render_testimonials_detailed', 'pattern' => true, 'page' => 'Testimonials',
			'fields' => array(
				'heading'   => $t( 'Kundenstimmen & Bewertungen', 'Überschrift', 'text' ),
				'highlight' => $t( 'Bewertungen', 'Hervorgehobenes Wort', 'text' ),
			),
		),

		/* ---- Standort-Detailseiten ---- */
		'fahrschule/location-hero' => array(
			'title' => 'Standort – Hero', 'description' => 'Stadt-Hero mit Badge, Adresse & Schnellanmeldung. Pro Stadt konfigurierbar.',
			'icon' => 'location', 'keywords' => array( 'standort', 'hero', 'stadt' ),
			'render' => 'fs_render_location_hero', 'pattern' => true, 'page' => 'Standorte',
			'fields' => array(
				'city'    => $t( 'Duisburg', 'Stadt', 'text' ),
				'icon'    => $t( 'shield', 'Icon', 'select', array( 'shield' => 'Schild', 'truck' => 'LKW', 'award' => 'Auszeichnung', 'map-pin' => 'Pin' ) ),
				'badge'   => $t( 'Hafen & Logistik', 'Badge', 'text' ),
				'heading' => $t( 'Sicher fahren in Duisburg. Sicher fahren im Hafen. Sicher fahren bei edi.', 'Überschrift', 'textarea' ),
				'lead'    => $t( 'Spezialisiert auf Sicherheitstraining und Fahrpraxis im dichten Stadtverkehr. Ideal für logistische Anforderungen im Duisburger Hafen.', 'Lead-Text', 'textarea' ),
				'address' => $t( '', 'Adresse (leer = aus den Stammdaten der Stadt)', 'text' ),
				'zip'     => $t( '', 'PLZ & Ort (leer = Stammdaten)', 'text' ),
				'phone'   => $t( '', 'Telefon (leer = Stammdaten)', 'text' ),
				'email'   => $t( '', 'E-Mail (leer = Stammdaten)', 'text' ),
				'hours'   => $t( '', 'Öffnungszeiten (leer = zentral hinterlegte Zeiten)', 'text' ),
				'contact' => $t( '', 'Ansprechpartner vor Ort (optional)', 'text' ),
			),
		),
		'fahrschule/location-content' => array(
			'title' => 'Standort – Inhalt', 'description' => 'Standortbilder, Distanzrechner & lokale Bewertungen.',
			'icon' => 'admin-multisite', 'keywords' => array( 'standort', 'bilder', 'distanz' ),
			'render' => 'fs_render_location_content', 'pattern' => true, 'page' => 'Standorte',
			'fields' => array(
				'city'   => $t( 'Duisburg', 'Stadt', 'text' ),
				'img1'   => $t( '', 'Bild 1 URL (leer = edi-standort-<stadt> aus der Mediathek)', 'url' ),
				'img1_t' => $t( 'Moderne Fahrschule', 'Bild 1 Titel', 'text' ),
				'img1_s' => $t( 'Standort mit Schulungsfahrzeugen', 'Bild 1 Untertitel', 'text' ),
				'img2'   => $t( fs_media( 'fac_empfang' ), 'Bild 2 URL', 'url' ),
				'img2_t' => $t( 'Willkommensbereich', 'Bild 2 Titel', 'text' ),
				'img2_s' => $t( 'Moderner Empfangsbereich & Schulungsräume', 'Bild 2 Untertitel', 'text' ),
			),
		),
		'fahrschule/location-calculator' => array(
			'title' => 'Standort – Rechner', 'description' => 'Slider-Rechner (Stunden × Stundenpreis + Theorie).',
			'icon' => 'calculator', 'keywords' => array( 'standort', 'rechner', 'slider' ),
			'render' => 'fs_render_location_calculator', 'pattern' => true, 'page' => 'Standorte',
			'fields' => array(),
		),

		/* ---- PDF-Custom-Blöcke ---- */
		'fahrschule/license-overview' => array(
			'title' => 'Führerscheinklassen (Übersicht)', 'description' => 'Vollständige Klassenliste Motorrad/PKW/LKW/Bus – PDF-Abschnitt F3.',
			'icon' => 'list-view', 'keywords' => array( 'klassen', 'übersicht', 'pdf' ),
			'render' => 'fs_render_license_overview', 'pattern' => true, 'page' => 'PDF / Custom',
			'fields' => array(
				'eyebrow'  => $t( $pdf( 'f3' )['label'], 'Eyebrow', 'text' ),
				'heading'  => $t( $pdf( 'f3' )['headline'], 'Überschrift', 'text' ),
				'subtitle' => $t( $pdf( 'f3' )['paragraphs'][0], 'Untertitel', 'textarea' ),
				'head'     => $head,
			),
		),
		'fahrschule/services' => array(
			'title' => 'Unsere Leistungen', 'description' => 'Leistungsliste „Alles aus einer Hand“ – PDF-Abschnitt F4.',
			'icon' => 'yes-alt', 'keywords' => array( 'leistungen', 'service', 'pdf' ),
			'render' => 'fs_render_services', 'pattern' => true, 'page' => 'PDF / Custom',
			'fields' => array(
				'eyebrow'    => $t( $pdf( 'f4' )['label'], 'Eyebrow', 'text' ),
				'heading'    => $t( $pdf( 'f4' )['headline'], 'Überschrift', 'text' ),
				'subtitle'   => $t( $pdf( 'f4' )['paragraphs'][0], 'Untertitel', 'textarea' ),
				'list_intro' => $t( $pdf( 'f4' )['list_intro'], 'Listen-Einleitung', 'text' ),
				'footer'     => $t( $pdf( 'f4' )['closing'], 'Abschlusstext', 'textarea' ),
				'head'       => $head,
			),
		),
		'fahrschule/theory-schedule' => array(
			'title' => 'Theorieunterricht – Termine', 'description' => 'Wöchentliche Theorie-Termine je Standort-Gruppe (aus der alten Website übernommen).',
			'icon' => 'clock', 'keywords' => array( 'theorie', 'unterricht', 'termine', 'zeiten' ),
			'render' => 'fs_render_theory_schedule', 'pattern' => true, 'page' => 'Theorieunterricht',
			'fields' => array(
				'eyebrow' => $t( 'Theorieunterricht', 'Eyebrow', 'text' ),
				'heading' => $t( 'Unsere Theorie-Termine', 'Überschrift', 'text' ),
				'lead'    => $t( 'Der Theorieunterricht findet wöchentlich an festen Terminen statt – wählen Sie den Standort, der für Sie am besten passt.', 'Lead-Text', 'textarea' ),
			),
		),
		'fahrschule/professional-drivers' => array(
			'title' => 'Berufskraftfahrer', 'description' => 'Aus- & Weiterbildung Berufskraftfahrer – PDF-Abschnitt F5.',
			'icon' => 'car', 'keywords' => array( 'berufskraftfahrer', 'lkw', 'logistik', 'pdf' ),
			'render' => 'fs_render_professional_drivers', 'pattern' => true, 'page' => 'PDF / Custom',
			'fields' => array(
				'eyebrow'    => $t( $pdf( 'f5' )['label'], 'Eyebrow', 'text' ),
				'heading'    => $t( $pdf( 'f5' )['headline'], 'Überschrift', 'text' ),
				'p1'         => $t( $pdf( 'f5' )['paragraphs'][0], 'Absatz 1', 'textarea' ),
				'p2'         => $t( $pdf( 'f5' )['paragraphs'][1], 'Absatz 2', 'textarea' ),
				'list_intro' => $t( $pdf( 'f5' )['list_intro'], 'Listen-Einleitung', 'text' ),
				'footer'     => $t( $pdf( 'f5' )['closing'], 'Abschlusstext', 'textarea' ),
				'head'       => $head,
			),
		),
		'fahrschule/seminars' => array(
			'title' => 'ASF & FES', 'description' => 'Aufbauseminare & Punkteabbau – PDF-Abschnitt F6.',
			'icon' => 'welcome-learn-more', 'keywords' => array( 'asf', 'fes', 'seminar', 'punkte', 'pdf' ),
			'render' => 'fs_render_seminars', 'pattern' => true, 'page' => 'PDF / Custom',
			'fields' => array(
				'eyebrow'    => $t( $pdf( 'f6' )['label'], 'Eyebrow', 'text' ),
				'heading'    => $t( $pdf( 'f6' )['headline'], 'Überschrift', 'text' ),
				'subtitle'   => $t( $pdf( 'f6' )['paragraphs'][0], 'Untertitel', 'textarea' ),
				'list_intro' => $t( $pdf( 'f6' )['list_intro'], 'Listen-Einleitung', 'text' ),
				'footer'     => $t( $pdf( 'f6' )['closing'], 'Abschlusstext', 'textarea' ),
				'head'       => $head,
			),
		),
		'fahrschule/modern-training' => array(
			'title' => 'Moderne Ausbildung', 'description' => 'Simulatoren, Fuhrpark & Lernsoftware – PDF-Abschnitt F7.',
			'icon' => 'desktop', 'keywords' => array( 'moderne ausbildung', 'simulator', 'technik', 'pdf' ),
			'render' => 'fs_render_modern_training', 'pattern' => true, 'page' => 'PDF / Custom',
			'fields' => array(
				'eyebrow'   => $t( $pdf( 'f7' )['label'], 'Eyebrow', 'text' ),
				'heading'   => $t( $pdf( 'f7' )['headline'], 'Überschrift', 'text' ),
				'highlight' => $t( $pdf( 'f7' )['highlight'], 'Hervorgehobenes Wort', 'text' ),
				'p1'        => $t( $pdf( 'f7' )['paragraphs'][0], 'Absatz 1', 'textarea' ),
				'p2'        => $t( $pdf( 'f7' )['paragraphs'][1], 'Absatz 2', 'textarea' ),
				'p3'        => $t( $pdf( 'f7' )['paragraphs'][2], 'Absatz 3', 'textarea' ),
				'footer'    => $t( $pdf( 'f7' )['paragraphs'][3], 'Abschlusstext', 'textarea' ),
				'head'      => $head,
			),
		),
		'fahrschule/anmeldung' => array(
			'title' => 'Anmeldung (CTA)', 'description' => 'Abschluss „Jetzt bei der Fahrschule edi anmelden“ – PDF-Abschnitt F10.',
			'icon' => 'welcome-write-blog', 'keywords' => array( 'anmeldung', 'cta', 'pdf' ),
			'render' => 'fs_render_anmeldung', 'pattern' => true, 'page' => 'PDF / Custom',
			'fields' => array(
				'eyebrow'    => $t( $pdf( 'f10' )['label'], 'Eyebrow', 'text' ),
				'heading'    => $t( $pdf( 'f10' )['headline'], 'Überschrift', 'text' ),
				'p1'         => $t( $pdf( 'f10' )['paragraphs'][0], 'Absatz 1', 'textarea' ),
				'p2'         => $t( $pdf( 'f10' )['paragraphs'][1], 'Absatz 2', 'textarea' ),
				'p3'         => $t( $pdf( 'f10' )['paragraphs'][2], 'Absatz 3', 'textarea' ),
				'link_intro' => $t( $pdf( 'f10' )['link_intro'], 'Text vor dem Button', 'text' ),
				'btn_label'  => $t( $pdf( 'f10' )['link_label'], 'Button-Text', 'text' ),
				'btn_url'    => $t( '@anmeldung', 'Button-Link', 'text' ),
				'head'       => $head,
			),
		),
		'fahrschule/contact-form' => array(
			'title' => 'Formular (Kontakt, Anmeldung, Bewerbung)', 'description' => 'Contact-Form-7-Formular der gewählten Art (legt das Plugin edi-integrationen an; Empfänger aus den Stammdaten, Eingänge in Flamingo). Eigener Shortcode möglich. Ohne CF7 greift ein eingebautes Formular.',
			'icon' => 'email-alt', 'keywords' => array( 'kontakt', 'formular', 'anmeldung', 'lead' ),
			'render' => 'fs_render_contact_form', 'pattern' => true, 'page' => 'Kontakt, Anmeldung',
			'fields' => array(
				'heading'       => $t( 'Schreiben Sie uns', 'Überschrift', 'text' ),
				'lead'          => $t( 'Fragen zur Ausbildung, den Kosten oder einem Standort? Wir melden uns kurzfristig bei Ihnen zurück.', 'Lead-Text', 'textarea' ),
				'art'           => $t( 'kontakt', 'Formular', 'select', array( 'kontakt' => 'Kontakt', 'anmeldung' => 'Anmeldung', 'bewerbung' => 'Bewerbung' ) ),
				'formShortcode' => $t( '', 'Eigener Shortcode (optional, z. B. [contact-form-7 id="…"])', 'text' ),
			),
		),

		/* ---- Unterseiten-Architektur ---- */
		'fahrschule/page-hero' => array(
			'title' => 'Seitenkopf (Unterseite)', 'description' => 'Kompakter Kopfbereich für Unterseiten. Titel/Einleitung kommen aus der Seiten-Registry.',
			'icon' => 'heading', 'keywords' => array( 'seitenkopf', 'titel', 'unterseite' ),
			'render' => 'fs_render_page_hero', 'pattern' => true, 'page' => 'Unterseiten',
			'fields' => array(
				'eyebrow'   => $t( '', 'Eyebrow', 'text' ),
				'heading'   => $t( '', 'Überschrift (leer = PDF-Abschnitt bzw. Seitentitel)', 'text' ),
				'highlight' => $t( '', 'Hervorgehobenes Wort', 'text' ),
				'lead'      => $t( '', 'Einleitung (leer = aus Registry)', 'textarea' ),
			),
		),
		'fahrschule/breadcrumbs' => array(
			'title' => 'Brotkrumen', 'description' => 'Pfad zur aktuellen Seite (Startseite › Bereich › Seite). Steht in den Seitenvorlagen; ohne Seite (Website-Editor) nur ein Hinweis.',
			'icon' => 'arrow-right-alt', 'keywords' => array( 'breadcrumbs', 'brotkrumen', 'pfad' ),
			'render' => 'fs_render_breadcrumbs', 'pattern' => false, 'may_be_empty' => true, 'page' => 'Vorlagen',
			'fields' => array(),
		),
		'fahrschule/karte' => array(
			'title' => 'Karte (nach Einwilligung)', 'description' => 'OpenStreetMap-Karte des Standorts, Zwei-Klick-Lösung: Bis zum Klick (oder „Alle akzeptieren") wird nichts von Dritten geladen. Standort aus den Stammdaten; leer = Standort der geöffneten Seite.',
			'icon' => 'location-alt', 'keywords' => array( 'karte', 'map', 'anfahrt', 'route' ),
			'render' => 'fs_render_karte', 'pattern' => false, 'may_be_empty' => true, 'page' => 'Standorte',
			'fields' => array(
				'city'    => $t( '', 'Standort (leer = Seite; sonst duisburg, essen, gelsenkirchen)', 'text' ),
				'heading' => $t( 'So finden Sie uns', 'Überschrift', 'text' ),
				'hinweis' => $t( 'Die Karte wird von OpenStreetMap geladen. Dabei wird Ihre IP-Adresse an die OpenStreetMap Foundation übertragen.', 'Hinweis vor dem Laden', 'textarea' ),
				'button'  => $t( 'Karte laden', 'Button-Text', 'text' ),
				'route'   => $t( 'Route bei OpenStreetMap planen', 'Routen-Link (öffnet extern)', 'text' ),
			),
		),
		'fahrschule/jobs' => array(
			'title' => 'Stellenangebote', 'description' => 'Offene Stellen aus WP Job Manager (besetzte ausgenommen), optional nach Kategorie. Ohne Stelle: Leer-Text und Knopf zur Initiativbewerbung.',
			'icon' => 'businessperson', 'keywords' => array( 'jobs', 'stellen', 'karriere', 'bewerbung' ),
			'render' => 'fs_render_jobs', 'pattern' => true, 'page' => 'Karriere',
			'fields' => array(
				'heading'   => $t( 'Offene Stellen', 'Überschrift', 'text' ),
				'intro'     => $t( '', 'Einleitung', 'textarea' ),
				'anzahl'    => $t( '10', 'Anzahl (höchstens)', 'select', array( '3' => '3', '5' => '5', '10' => '10', '20' => '20', '50' => '50' ) ),
				'kategorie' => $t( '', 'Kategorie-Filter (leer = alle; fahrlehrer, buero-ansprechpartner, quereinstieg-ausbildung)', 'text' ),
				'btn_label' => $t( 'Zur Stelle', 'Button-Text je Stelle', 'text' ),
				// 'zustand' (nur Prüfkette): Feld wirkt nur, wenn keine Stelle offen ist.
				'leer'      => $t( 'Aktuell ist keine Stelle ausgeschrieben. Initiativbewerbungen sind willkommen.', 'Leer-Text (keine offene Stelle)', 'textarea' ) + array( 'zustand' => 'ohne_daten' ),
				'initiativ' => $t( 'Initiativ bewerben', 'Text Initiativ-Knopf', 'text' ),
				'ziel'      => $t( '#kontaktformular', 'Ziel-Link Initiativ-Knopf', 'text' ),
			),
		),
		'fahrschule/stelle' => array(
			'title' => 'Stelle (Einzelansicht)', 'description' => 'Eckdaten, Beschreibung und Bewerbungsformular einer Stellenanzeige. Steht in der Vorlage single-job_listing.',
			'icon' => 'id', 'keywords' => array( 'stelle', 'job', 'bewerbung' ),
			'render' => 'fs_render_stelle', 'pattern' => false, 'may_be_empty' => true, 'page' => 'Vorlagen',
			'fields' => array(
				'form_heading' => $t( 'Jetzt bewerben', 'Überschrift Bewerbung', 'text' ),
			),
		),
		'fahrschule/fehlerseite' => array(
			'title' => 'Fehlerseite (404)', 'description' => 'Hinweis „Seite nicht gefunden“ mit Wegen zurück und Suchfeld. Steht in der Vorlage 404.',
			'icon' => 'warning', 'keywords' => array( '404', 'fehler', 'nicht gefunden' ),
			'render' => 'fs_render_fehlerseite', 'pattern' => false, 'page' => 'Vorlagen',
			'fields' => array(
				'eyebrow'   => $t( 'Fehler 404', 'Eyebrow', 'text' ),
				'heading'   => $t( 'Diese Seite gibt es nicht mehr', 'Überschrift', 'text' ),
				'highlight' => $t( 'nicht mehr', 'Hervorgehobenes Wort', 'text' ),
				'text'      => $t( 'Die aufgerufene Adresse führt ins Leere. Vielleicht hilft einer dieser Wege weiter.', 'Text', 'textarea' ),
				'links'     => $rep( array(
					array( 'Zur Startseite', '/', 'arrow-right' ),
					array( 'Führerscheinklassen ansehen', '@klassen', 'arrow-right' ),
					array( 'Unsere Standorte', '@standorte', 'map-pin' ),
					array( 'Kontakt aufnehmen', '@kontakt', 'phone' ),
				), 'Weg', array( 'Text', 'Link', 'Icon' ) ),
				'suche'     => $t( '1', 'Suchfeld zeigen (1/0)', 'text' ),
			),
		),
		'fahrschule/vertrauensleiste' => array(
			'title' => 'Vertrauensleiste', 'description' => 'Schmale Leiste mit belegten Fakten unter dem Hero: Zertifizierung, Gründungsjahr, Standorte, Klassen. Jede Zeile: Icon, Text, optional Logo aus der Medien-Registry.',
			'icon' => 'yes-alt', 'keywords' => array( 'vertrauen', 'fakten', 'zertifikat', 'leiste' ),
			'render' => 'fs_render_vertrauensleiste', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				// Nur Belegtes: CERTQUA-Logo (alte Website), Gründungsjahr und
				// Standorte aus inc/seo.php, Klassengruppen aus PDF F3.
				'items' => $rep( array(
					array( 'award', 'CERTQUA-zertifiziert', 'logo_certqua' ),
					array( 'clock', 'Seit ' . fahrschule_business_data()['founded'] . ' im Ruhrgebiet', '' ),
					array( 'map-pin', count( fahrschule_business_data()['locations'] ) . ' Standorte: ' . implode( ', ', array_column( fahrschule_business_data()['locations'], 'city' ) ), '' ),
					array( 'check-circle', fs_aufzaehlung( array_keys( $pdf( 'f3' )['groups'] ) ), '' ),
				), 'Eintrag', array( 'Icon', 'Text', 'Logo (Medien-Schlüssel, optional)' ) ),
			),
		),
		'fahrschule/klassen-kacheln' => array(
			'title' => 'Klassen-Kacheln', 'description' => 'Vier Kacheln nach PDF F3 (Motorrad, PKW, LKW, Bus) mit je zwei Knöpfen: Klassen ansehen und Beratung.',
			'icon' => 'screenoptions', 'keywords' => array( 'klassen', 'kacheln', 'führerschein', 'startseite' ),
			'render' => 'fs_render_klassen_kacheln', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'eyebrow'    => $t( $pdf( 'f3' )['label'], 'Eyebrow', 'text' ),
				'heading'    => $t( $pdf( 'f3' )['headline'], 'Überschrift', 'text' ),
				'kacheln'    => $rep( fs_klassen_kacheln_vorgabe(), 'Kachel', array( 'Titel', 'Klassen', 'Link', 'Icon' ) ),
				'btn1_label' => $t( 'Klassen ansehen', 'Button 1 Text (Link der Kachel)', 'text' ),
				'btn2_label' => $t( 'Beratung', 'Button 2 Text', 'text' ),
				'btn2_url'   => $t( '@kontakt', 'Button 2 Link', 'text' ),
			),
		),
		'fahrschule/seiten-anriss' => array(
			'title' => 'Seiten-Anriss', 'description' => 'Anriss einer Unterseite: Label, Überschrift und erster Absatz ihres PDF-Abschnitts (wortgleich) bzw. Titel und Einleitung, dazu ein Link. Leere Textfelder = aus PDF/Registry.',
			'icon' => 'excerpt-view', 'keywords' => array( 'anriss', 'teaser', 'startseite', 'abschnitt' ),
			'render' => 'fs_render_seiten_anriss', 'pattern' => true, 'may_be_empty' => true, 'page' => 'Startseite',
			'fields' => array(
				'seite'     => $t( 'ueber-uns', 'Seite (Schlüssel, z. B. ueber-uns, team)', 'text' ),
				'eyebrow'   => $t( '', 'Eyebrow (leer = PDF-Label)', 'text' ),
				'heading'   => $t( '', 'Überschrift (leer = PDF/Seitentitel)', 'text' ),
				'text'      => $t( '', 'Text (leer = erster PDF-Absatz/Einleitung)', 'textarea' ),
				'btn_label' => $t( 'Mehr erfahren', 'Button-Text', 'text' ),
			),
		),
		'fahrschule/page-teasers' => array(
			'title' => 'Themen-Teaser', 'description' => 'Teaser der Startseite auf die Unterseiten – je Label, Überschrift und erster Satz des PDF-Abschnitts.',
			'icon' => 'grid-view', 'keywords' => array( 'teaser', 'übersicht', 'startseite' ),
			'render' => 'fs_render_page_teasers', 'pattern' => true, 'page' => 'Unterseiten',
			'fields' => array(
				'eyebrow'  => $t( '', 'Eyebrow (optional)', 'text' ),
				'heading'  => $t( '', 'Überschrift (optional)', 'text' ),
				'subtitle' => $t( '', 'Untertitel (optional)', 'textarea' ),
				'seiten'   => $rep( array(
					array( 'klassen', 'award' ), array( 'leistungen', 'check-circle' ), array( 'berufskraftfahrer', 'truck' ),
					array( 'asf-fes', 'shield' ), array( 'moderne-ausbildung', 'zap' ), array( 'team', 'users' ),
				), 'Teaser', array( 'Seite (Schlüssel, z. B. asf-fes)', 'Icon' ), array( array( 'ueber-uns', 'heart' ), '/ueber-uns/' ) ),
			),
		),
		'fahrschule/instructor-training' => array(
			'title' => 'Fahrlehrerausbildung', 'description' => 'Fahrlehrerfachschule: Ausbildung zum Fahrlehrer nach FahrlG.',
			'icon' => 'welcome-learn-more', 'keywords' => array( 'fahrlehrer', 'fachschule', 'ausbildung' ),
			'render' => 'fs_render_instructor_training', 'pattern' => true, 'page' => 'Unterseiten',
			'fields' => array(
				'eyebrow'   => $t( 'Fahrlehrerfachschule', 'Eyebrow', 'text' ),
				'heading'   => $t( 'Machen Sie Ihr Können zum Beruf', 'Überschrift', 'text' ),
				'highlight' => $t( 'Beruf', 'Hervorgehobenes Wort', 'text' ),
				'p1'        => $t( 'Als Fahrschule & Fahrlehrerfachschule edi bilden wir nicht nur Fahrschüler aus, sondern auch die Fahrlehrerinnen und Fahrlehrer von morgen.', 'Absatz 1', 'textarea' ),
				'p2'        => $t( 'Die Ausbildung erfolgt nach dem Fahrlehrergesetz und verbindet Fachtheorie, Verkehrspädagogik und Praxis. Sie lernen bei Ausbildern, die selbst täglich im Fahrschulalltag stehen.', 'Absatz 2', 'textarea' ),
				'footer'    => $t( 'Sie interessieren sich für die Fahrlehrerlaubnis? Wir beraten Sie persönlich zu Voraussetzungen, Ablauf und Fördermöglichkeiten.', 'Abschlusstext', 'textarea' ),
				'btn_label' => $t( 'Beratung anfragen', 'Button-Text', 'text' ),
				'btn_url'   => $t( '@kontakt', 'Button-Link (@seitenschlüssel)', 'text' ),
			),
		),

		/* ---- Rechtstexte ---- */
		'fahrschule/legal' => array(
			'title' => 'Rechtstext (Impressum/Datenschutz)', 'description' => 'Rechtsseite – per Auswahl Impressum oder Datenschutz.',
			'icon' => 'shield-alt', 'keywords' => array( 'impressum', 'datenschutz', 'legal' ),
			'render' => 'fs_render_legal', 'pattern' => true, 'page' => 'Rechtliches',
			'fields' => array(
				'kind' => $t( 'impressum', 'Inhalt', 'select', array( 'impressum' => 'Impressum', 'datenschutz' => 'Datenschutz' ) ),
			),
		),
	);

	return fahrschule_flaeche_felder( $bloecke, $t );
}

/**
 * Feld „Fläche“ an jedem Inhaltsblock - an EINER Stelle statt in 40 Einträgen.
 *
 * Ausgenommen sind Blöcke ohne eigenen Abschnitt oder mit fester Fläche
 * (Navigation, Fußzeile, Brotkrumen, Badges, Heroes mit Foto/Video). Sie
 * tragen 'flaeche_fest': die Fläche, die sie in der Abschnittsfolge
 * zählen ('' = kein Abschnitt). Der Render-Aufruf wird umhüllt: erst der
 * Block, dann fs_flaeche_anwenden() - so gilt die Fläche im Frontend, in der
 * Editor-Vorschau und in den Prüfungen gleichermaßen.
 */
function fahrschule_flaeche_felder( $bloecke, $t ) {
	$fest = array(
		'fahrschule/navigation'    => '',
		'fahrschule/footer'        => '',
		'fahrschule/tiktok-badge'  => '',
		'fahrschule/cookie-banner' => '',
		'fahrschule/breadcrumbs'   => '',
		'fahrschule/stelle'        => '',
		'fahrschule/video-hero'    => 'foto',
		'fahrschule/hero'          => 'vollpink',
		'fahrschule/location-hero' => 'vollpink',
	);
	// Schmale Bänder sind von Haus aus Leiste; alles andere Vollpink.
	$vorgabe = array( 'fahrschule/vertrauensleiste' => 'leiste' );
	$wahl    = array( 'vollpink' => 'Vollpink (Signatur)', 'hellpink' => 'Hellpink (Rosé)', 'leiste' => 'Leiste (kräftiges Pink, nur schmale Bänder)', 'foto' => 'Foto (Bild mit Abdunkler)' );

	foreach ( $bloecke as $name => &$cfg ) {
		if ( array_key_exists( $name, $fest ) ) {
			$cfg['flaeche_fest'] = $fest[ $name ];
			continue;
		}
		$cfg['fields']['flaeche']      = $t( isset( $vorgabe[ $name ] ) ? $vorgabe[ $name ] : 'vollpink', 'Fläche', 'select', $wahl );
		$cfg['fields']['flaeche_bild'] = $t( '', 'Bild für Fläche „Foto“ (Medien-Slug, z. B. edi-galerie-001)', 'text' );
		$cfg['fields']['flaeche_bild']['zustand'] = 'nur_foto';
		$render        = $cfg['render'];
		$cfg['render'] = static function ( $a, $content = '', $block = null ) use ( $render ) {
			return fs_flaeche_anwenden( call_user_func( $render, $a, $content, $block ), $a );
		};
	}
	unset( $cfg );
	return $bloecke;
}
