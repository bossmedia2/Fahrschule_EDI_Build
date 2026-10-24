<?php
/**
 * Plugin Name: edi-integrationen
 * Description: Begleit-Plugin zum Fahrschule-edi-Theme: legt die Formulare in Contact Form 7 an (Empfänger aus den Stammdaten, Spamschutz ohne Drittanbieter, Eingänge in Flamingo), richtet WP Job Manager ein (Kategorien, Karriereseite) und bindet Matomo ein. Getrennt vom Theme, damit Funktionen einen Theme-Wechsel überleben.
 * Version: 2.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Text Domain: edi-integrationen
 * License: GPL-2.0-or-later
 *
 * WARUM EIN EIGENES PLUGIN
 * WordPress trennt Zuständigkeiten entlang einer einfachen Linie: Aussehen
 * gehört ins Theme, Anbindungen und Daten ins Plugin. Formulare, Stellen-
 * anzeigen und Statistik fallen klar auf die zweite Seite. Lägen sie im
 * Theme, würde ein späterer Theme-Wechsel sie stillschweigend abschalten.
 *
 * Das Theme funktioniert vollständig ohne dieses Plugin:
 *   • Formulare: ohne CF7-Formulare greift der eingebaute Rückfall
 *     (fahrschule-edi/inc/forms.php), Versand per wp_mail().
 *   • Stellen: ohne WP Job Manager zeigt der Stellen-Block seinen Leer-Text.
 *   • Matomo: bindet ausschließlich dieses Plugin ein - ohne Plugin wird
 *     schlicht nichts erfasst.
 *
 * @package Edi_Integrationen
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'EDI_INTEGRATIONEN_VERSION', '2.0.0' );

require_once __DIR__ . '/inc/matomo.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/formulare.php';
require_once __DIR__ . '/inc/stellen.php';

// Formulare gleich bei Aktivierung anlegen, wenn Contact Form 7 schon da ist.
register_activation_hook( __FILE__, function () {
	edi_formulare_anlegen();
	edi_stellen_einrichten();
} );
