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
	$rep = function ( $rows, $label, $columns ) {
		return array(
			'default' => $rows,
			'label'   => $label,
			'control' => 'repeater',
			'columns' => $columns,
			'options' => array(),
		);
	};

	/* Kundentexte (PDF) – dieselbe Quelle wie die Render-Callbacks, siehe inc/pdf-texts.php. */
	$pdf  = 'fahrschule_pdf';
	$head = $t( '1', 'Abschnittskopf (Label + Überschrift) anzeigen', 'select', array( '1' => 'Ja', '0' => 'Nein – steht bereits im Seitenkopf' ) );

	return array(

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
			'title' => 'Video Hero', 'description' => 'Vollflächiger Hero mit Hintergrundvideo (Startseite) – PDF-Abschnitt F1.',
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
				'video_url'  => $t( fs_media( 'video_hero' ), 'Video-URL', 'url' ),
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
		'fahrschule/instagram-slider' => array(
			'title' => 'Instagram Slider', 'description' => 'Slider mit Instagram-Beiträgen (3 sichtbar).',
			'icon' => 'instagram', 'keywords' => array( 'instagram', 'social', 'slider' ),
			'render' => 'fs_render_instagram_slider', 'pattern' => true, 'page' => 'Startseite',
			'fields' => array(
				'heading'   => $t( 'Folgen Sie uns auf Instagram', 'Überschrift', 'text' ),
				'highlight' => $t( 'Instagram', 'Hervorgehobenes Wort', 'text' ),
				'subtitle'  => $t( 'Erleben Sie die beste Fahrschule im Ruhrgebiet direkt in Ihrem Feed. Tipps, Erfolgsgeschichten und Behind-the-Scenes.', 'Untertitel', 'textarea' ),
				'url'       => $t( 'https://www.instagram.com/fahrschuleedi/', 'Instagram-URL', 'url' ),
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
					array( '30+', 'Jahre Erfahrung' ),
					array( '5000+', 'Bestandene Prüfungen' ),
					array( '3', 'Standorte' ),
					array( '5', 'Sprachen' ),
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
					array( '1993', 'Gründung', 'Fahrschule edi wird mit Leidenschaft gegründet' ),
					array( '2000er', 'Expansion', 'Eröffnung weiterer Standorte in Essen und Duisburg' ),
					array( '2010', 'Modernisierung', 'Einführung digitaler Lernmethoden und moderner Fahrzeuge' ),
					array( 'Heute', 'Größte im Ruhrgebiet', 'Eine der erfolgreichsten Fahrschulen mit über 10.000+ glücklichen Absolventen' ),
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
			'title' => 'Testimonials – Hero', 'description' => 'Zähler-Hero „Über 10.000+ glückliche Fahrschüler“.',
			'icon' => 'awards', 'keywords' => array( 'testimonials', 'hero', 'zähler' ),
			'render' => 'fs_render_testimonials_hero', 'pattern' => true, 'page' => 'Testimonials',
			'fields' => array(
				'heading'   => $t( 'Über 10.000+ glückliche Fahrschüler', 'Überschrift', 'text' ),
				'highlight' => $t( '10.000+ glückliche', 'Hervorgehobenes Wort', 'text' ),
				'subtitle'  => $t( 'Vertrauen Sie auf unsere Erfolgsbilanz und die positiven Erfahrungen unserer Absolventen', 'Untertitel', 'textarea' ),
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
				'address' => $t( fahrschule_pdf_location( 'Duisburg' )['street'], 'Adresse', 'text' ),
				'zip'     => $t( fahrschule_pdf_location( 'Duisburg' )['zip'], 'PLZ & Ort', 'text' ),
				'phone'   => $t( fahrschule_pdf_location( 'Duisburg' )['phone'], 'Telefon', 'text' ),
				'email'   => $t( fahrschule_pdf_location( 'Duisburg' )['email'], 'E-Mail', 'text' ),
				'hours'   => $t( str_replace( "\n", ', ', fahrschule_location_hours_text( 'Duisburg' ) ), 'Öffnungszeiten', 'text' ),
				'contact' => $t( '', 'Ansprechpartner vor Ort (optional)', 'text' ),
			),
		),
		'fahrschule/location-content' => array(
			'title' => 'Standort – Inhalt', 'description' => 'Standortbilder, Distanzrechner & lokale Bewertungen.',
			'icon' => 'admin-multisite', 'keywords' => array( 'standort', 'bilder', 'distanz' ),
			'render' => 'fs_render_location_content', 'pattern' => true, 'page' => 'Standorte',
			'fields' => array(
				'city'   => $t( 'Duisburg', 'Stadt', 'text' ),
				'img1'   => $t( fs_media( 'loc_duisburg' ), 'Bild 1 URL', 'url' ),
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
			'title' => 'Kontakt-/Anmeldeformular', 'description' => 'Echtes Formular (Name, E-Mail, Telefon, Standort, Nachricht) – Versand per E-Mail, optional zusätzlich an Odoo.',
			'icon' => 'email-alt', 'keywords' => array( 'kontakt', 'formular', 'anmeldung', 'lead' ),
			'render' => 'fs_render_contact_form', 'pattern' => true, 'page' => 'Kontakt, Anmeldung',
			'fields' => array(
				'heading' => $t( 'Schreiben Sie uns', 'Überschrift', 'text' ),
				'lead'    => $t( 'Fragen zur Ausbildung, den Kosten oder einem Standort? Wir melden uns kurzfristig bei Ihnen zurück.', 'Lead-Text', 'textarea' ),
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
		'fahrschule/page-teasers' => array(
			'title' => 'Themen-Teaser', 'description' => 'Teaser der Startseite auf die Unterseiten – je Label, Überschrift und erster Satz des PDF-Abschnitts.',
			'icon' => 'grid-view', 'keywords' => array( 'teaser', 'übersicht', 'startseite' ),
			'render' => 'fs_render_page_teasers', 'pattern' => true, 'page' => 'Unterseiten',
			'fields' => array(
				'eyebrow'  => $t( '', 'Eyebrow (optional)', 'text' ),
				'heading'  => $t( '', 'Überschrift (optional)', 'text' ),
				'subtitle' => $t( '', 'Untertitel (optional)', 'textarea' ),
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
}
