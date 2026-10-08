<?php
/**
 * Startseite.
 *
 * Aufbau nach „Texte_Webseite_Fahrschule" (Kundenvorgabe, inc/pdf-texts.php):
 * Die Abschnitte der Startseite stehen hier vollständig, die übrigen
 * PDF-Abschnitte als Teaser (Label, Überschrift, erster Satz) mit Link auf
 * ihre Unterseite – die Website bleibt bewusst mehrseitig (keine Onepage).
 *
 *  F1  Startseite / Hero          → video-hero
 *  F2  Über uns                   → about-intro
 *  F3–F7, F9 (Teaser)             → page-teasers
 *  F8  Standorte                  → locations
 *      Team Spirit, Bewertungen   → kein PDF-Abschnitt
 *  F10 Anmeldung (Abschluss)      → anmeldung
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

echo fs_block( 'fahrschule/video-hero' );
echo fs_block( 'fahrschule/about-intro' );
echo fs_block( 'fahrschule/page-teasers' );
echo fs_block( 'fahrschule/locations' );
echo fs_block( 'fahrschule/team-spirit' );
echo fs_block( 'fahrschule/testimonials' );
echo fs_block( 'fahrschule/anmeldung' );

get_footer();
