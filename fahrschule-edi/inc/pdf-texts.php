<?php
/**
 * Fahrschule edi – Kundentexte (PDF) als einzige Quelle
 *
 * Die zehn Abschnitte aus „Texte_Webseite_Fahrschule" (Kundenvorgabe) im
 * Wortlaut. Daraus speisen sich die Render-Callbacks (inc/render-callbacks.php)
 * UND die Editor-Standardwerte der Blöcke (inc/blocks-config.php) – vorher
 * standen die Texte an beiden Stellen getrennt und liefen auseinander.
 *
 * Jeder Abschnitt ist auf der Website eine eigene Sektion (Attribut
 * data-pdf-section="F1" … "F10"): F1/F2/F8/F10 vollständig auf der
 * Startseite, F3–F7 und F9 dort als Teaser und vollständig auf ihrer
 * Unterseite (Zuordnung: Schlüssel 'pdf' in inc/pages.php).
 *
 * Wortlaut nicht umformulieren. Einzige Abweichungen vom PDF-Satz:
 * Zeilenumbrüche zusammengeführt, leere Aufzählungspunkte weggelassen,
 * Ligaturen (ﬁ) als normale Buchstaben.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Alle PDF-Abschnitte. Schlüssel = interner Name, 'id' = Nummer im PDF.
 *
 * @return array<string, array<string, mixed>>
 */
function fahrschule_pdf_sections() {
	return array(
		'f1'  => array(
			'id'         => 'F1',
			'label'      => 'Startseite / Hero',
			'headline'   => 'Fahrschule edi – Mit Kompetenz zum Führerschein',
			'highlight'  => 'Kompetenz',
			'paragraphs' => array(
				'Sie möchten Ihren Führerschein machen und suchen eine Fahrschule, die Sie zuverlässig begleitet? Dann sind Sie bei uns genau richtig.',
				'Die Fahrschule edi begleitet Fahrschüler seit über 30 Jahren auf dem Weg zum Führerschein. Mit erfahrenen Fahrlehrern, moderner Ausbildung und persönlicher Betreuung sorgen wir dafür, dass Sie sicher und gut vorbereitet in die Prüfung gehen.',
				'Ob Auto, Motorrad, LKW oder Bus: bei uns lernen Sie Schritt für Schritt alles, was Sie für Ihren Führerschein brauchen. Der Unterricht ist praxisnah, verständlich aufgebaut und auch in mehreren Sprachen möglich.',
				'Unser Ziel ist nicht nur die bestandene Prüfung, sondern sicheres und verantwortungsvolles Fahren im Alltag.',
			),
		),
		'f2'  => array(
			'id'         => 'F2',
			'label'      => 'Über uns',
			'headline'   => 'Ihre Fahrschule im Ruhrgebiet',
			'highlight'  => 'Ruhrgebiet',
			'paragraphs' => array(
				'Die Fahrschule edi ist seit vielen Jahren im Ruhrgebiet zuhause und begleitet Fahrschüler erfolgreich auf dem Weg zum Führerschein.',
				'Mit unseren Standorten in Duisburg, Essen und Gelsenkirchen sind wir für viele Fahrschüler im Pott die perfekte Anlaufstelle rund um den Führerschein. Ob Auto, Motorrad, LKW oder Bus – bei uns finden Sie die passende Ausbildung für Ihr Ziel.',
				'Freundlicher Umgang, klare Erklärungen und eine entspannte Lernatmosphäre gehören für uns genauso dazu wie eine gute Vorbereitung auf Theorie und Praxis.',
			),
		),
		'f3'  => array(
			'id'         => 'F3',
			'label'      => 'Führerscheinklassen',
			'headline'   => 'Unsere Führerscheinklassen',
			'paragraphs' => array(
				'Bei der Fahrschule edi können Sie verschiedene Führerscheine erwerben: vom Motorrad bis zum LKW oder Bus.',
			),
			'groups'     => array(
				'Motorrad' => array( 'AM', 'A1', 'A2', 'A' ),
				'PKW'      => array( 'BF17', 'B', 'B197', 'BE' ),
				'LKW'      => array( 'C1', 'C1E', 'C', 'CE' ),
				'Bus'      => array( 'D', 'DE' ),
			),
		),
		'f4'  => array(
			'id'         => 'F4',
			'label'      => 'Unsere Leistungen',
			'headline'   => 'Alles rund um den Führerschein aus einer Hand',
			'paragraphs' => array(
				'Von der Anmeldung bis zur praktischen Prüfung begleiten wir Sie während der gesamten Ausbildung. Dabei unterstützen wir Sie nicht nur im Unterricht, sondern auch bei organisatorischen Fragen.',
			),
			'list_intro' => 'Unsere Leistungen:',
			'items'      => array(
				'Unterstützung bei der Antragstellung',
				'Hilfe bei der Führerscheinfinanzierung',
				'Erste-Hilfe-Kurse',
				'Passbilder für den Führerschein',
				'Vermittlung medizinischer Untersuchungen',
				'Moderne Lernsoftware',
				'Persönliche Betreuung',
				'Praxisorientierter Theorieunterricht',
			),
			'closing'    => 'Unser Ziel ist es, Ihnen den Weg zum Führerschein so einfach und angenehm wie möglich zu machen.',
		),
		'f5'  => array(
			'id'         => 'F5',
			'label'      => 'Berufskraftfahrer',
			'headline'   => 'Ausbildung & Weiterbildung für Berufskraftfahrer',
			'paragraphs' => array(
				'Neben den klassischen Führerscheinen bieten wir auch Aus- und Weiterbildungen für Berufskraftfahrer an.',
				'Der Bereich Transport und Logistik bietet gute Zukunftschancen und sichere Arbeitsplätze. Unsere Schulungen bereiten Sie auf den professionellen Einsatz im Straßenverkehr vor.',
			),
			'list_intro' => 'Inhalte:',
			'items'      => array(
				'LKW-Führerscheine',
				'Berufskraftfahrer-Weiterbildungen',
				'Sicheres und wirtschaftliches Fahren',
				'Ladungssicherung',
				'Praxisnahe Übungen',
			),
			'closing'    => 'Wichtig für diesen Bereich sind Verantwortungsbewusstsein, Aufmerksamkeit und Zuverlässigkeit.',
		),
		'f6'  => array(
			'id'         => 'F6',
			'label'      => 'ASF & FES',
			'headline'   => 'Aufbauseminare und Punkteabbau',
			'paragraphs' => array(
				'Wir bieten verschiedene Seminare für Fahranfänger und Fahrer mit Punkten in Flensburg an.',
			),
			'list_intro' => 'Unsere Angebote:',
			'items'      => array(
				'ASF-Aufbauseminare',
				'FES-Seminare zum Punkteabbau',
				'Individuelle Beratung',
				'Unterstützung bei Fragen rund um die Fahrerlaubnis',
			),
			'closing'    => 'Die Seminare helfen Ihnen dabei, sicherer und bewusster im Straßenverkehr unterwegs zu sein.',
		),
		'f7'  => array(
			'id'         => 'F7',
			'label'      => 'Moderne Ausbildung',
			'headline'   => 'Lernen mit moderner Technik',
			'highlight'  => 'moderner Technik',
			'paragraphs' => array(
				'Für eine erfolgreiche Ausbildung setzen wir auf moderne Fahrzeuge, innovative Fahrsimulatoren und aktuelle Lernmethoden.',
				'Unsere Fahrsimulatoren ermöglichen ein sicheres erstes Üben typischer Fahrsituationen, bevor es auf die Straße geht. So können wichtige Abläufe stressfrei trainiert und Schritt für Schritt erlernt werden.',
				'Unser Fuhrpark umfasst Autos, Motorräder, LKWs und Busse mit moderner Ausstattung. Zusätzlich nutzen wir zeitgemäße Lernsoftware, damit Theorie und Praxis verständlich und anschaulich vermittelt werden können.',
				'So entsteht eine Ausbildung, die sicher, modern und alltagsnah auf den Straßenverkehr vorbereitet.',
			),
		),
		'f8'  => array(
			'id'         => 'F8',
			'label'      => 'Standorte',
			'headline'   => 'Unsere Standorte',
			'paragraphs' => array(
				'Die Fahrschule edi ist an mehreren Standorten im Ruhrgebiet für Sie da.',
			),
			/* Reihenfolge wie im PDF. Fotos je Standort: fs_media( 'loc_…' ). */
			'locations'  => array(
				'Duisburg'      => array( 'street' => 'Friedrich Ebert Str. 346', 'zip' => '47139 Duisburg', 'phone' => '0203 – 5785703', 'email' => 'duisburg@fahrschule-edi.de' ),
				'Gelsenkirchen' => array( 'street' => 'Luitpoldstr. 4', 'zip' => '45879 Gelsenkirchen', 'phone' => '0209 – 1487030', 'email' => 'gelsenkirchen@fahrschule-edi.de' ),
				'Essen'         => array( 'street' => 'Wilhelm Nieswandt Allee 153', 'zip' => '45326 Essen', 'phone' => '0201 – 2899482', 'email' => 'essen@fahrschule-edi.de' ),
			),
		),
		'f9'  => array(
			'id'         => 'F9',
			'label'      => 'Unser Team',
			'headline'   => 'Persönlich. Geduldig. Erfahren.',
			'paragraphs' => array(
				'Hinter der Fahrschule edi steht ein engagiertes Team aus Fahrlehrern und Mitarbeitern mit langjähriger Erfahrung.',
				'Uns ist wichtig, dass sich jeder Fahrschüler wohlfühlt und verständlich lernen kann. Darum nehmen wir uns Zeit für Fragen und begleiten Sie Schritt für Schritt durch die Ausbildung.',
			),
			/* Im PDF je Gruppe mit „(Fotos)" markiert – Fotos liegen noch nicht vor. */
			'groups'     => array(
				'Büro & Ansprechpartner' => '(Essen, Gelsenkirchen, Duisburg)',
				'Geschäftsleitung'       => '(Essen, Gelsenkirchen, Duisburg)',
				'Dozenten & Ausbilder'   => '(Essen, Gelsenkirchen, Duisburg)',
			),
			'closing'    => 'Mit Geduld, Erfahrung und persönlicher Betreuung helfen wir Ihnen dabei, sicher und selbstbewusst am Straßenverkehr teilzunehmen.',
		),
		'f10' => array(
			'id'         => 'F10',
			'label'      => 'Anmeldung',
			'headline'   => 'Jetzt bei der Fahrschule edi anmelden',
			'paragraphs' => array(
				'Der erste Schritt zum Führerschein beginnt mit der richtigen Fahrschule.',
				'Wir beraten Sie persönlich und helfen Ihnen dabei, die passende Führerscheinklasse zu finden.',
				'Jetzt informieren und gemeinsam den Weg zum Führerschein starten.',
			),
			'link_intro' => 'Online-Anmeldung:',
			'link_label' => 'Fahrschule edi Anmeldung',
		),
	);
}

/**
 * Ein PDF-Abschnitt.
 *
 * @param string $key Schlüssel aus fahrschule_pdf_sections() (f1 … f10).
 * @return array<string, mixed>
 */
function fahrschule_pdf( $key ) {
	$all = fahrschule_pdf_sections();
	return isset( $all[ $key ] ) ? $all[ $key ] : array();
}

/**
 * Ein einzelner Text eines Abschnitts, z. B. fahrschule_pdf_text( 'f4', 'closing' )
 * oder der n-te Absatz über fahrschule_pdf_text( 'f2', 'p', 1 ).
 *
 * @param string $key   Abschnitt.
 * @param string $field Feldname bzw. 'p' für einen Absatz.
 * @param int    $index Absatz-Index (nur bei 'p').
 * @return string
 */
function fahrschule_pdf_text( $key, $field, $index = 0 ) {
	$s = fahrschule_pdf( $key );
	if ( 'p' === $field ) {
		return isset( $s['paragraphs'][ $index ] ) ? $s['paragraphs'][ $index ] : '';
	}
	return isset( $s[ $field ] ) && is_string( $s[ $field ] ) ? $s[ $field ] : '';
}

/**
 * Erster Satz eines Textes – für die Teaser der Startseite (wortgleich, nur
 * gekürzt; kein eigener Teasertext).
 *
 * @param string $text Absatz.
 * @return string
 */
function fahrschule_pdf_first_sentence( $text ) {
	if ( preg_match( '/^(.+?[.!?])(\s|$)/u', (string) $text, $m ) ) {
		return $m[1];
	}
	return (string) $text;
}

/**
 * Standortdaten laut PDF-Abschnitt F8.
 *
 * @param string $city Duisburg, Gelsenkirchen oder Essen.
 * @return array{street:string,zip:string,phone:string,email:string}
 */
function fahrschule_pdf_location( $city ) {
	$locations = fahrschule_pdf( 'f8' )['locations'];
	// Unbekannter Ort: nichts - nie still die Adresse eines anderen Standorts.
	return isset( $locations[ $city ] ) ? $locations[ $city ] : array();
}

/**
 * Gibt an, ob ein Block seinen Abschnittskopf (Label + Überschrift) zeigen
 * soll. Auf der Unterseite eines Abschnitts steht der Kopf bereits im
 * Seitenkopf – dort setzt inc/pages.php 'head' => '0'.
 *
 * @param array<string, mixed> $d Block-Attribute.
 * @return bool
 */
function fahrschule_pdf_show_head( $d ) {
	return ! isset( $d['head'] ) || '0' !== (string) $d['head'];
}
