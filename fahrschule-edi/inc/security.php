<?php
/**
 * Fahrschule edi – Sicherheits-Hardening (theme-seitig, ohne Live-Zugriff)
 *
 * WPScan-Befunde, die sich auf Theme-Ebene beheben lassen (kein Zugriff auf
 * die Live-Installation in dieser Session, siehe docs/SETUP.md für die
 * verbleibenden, serverseitigen Prüfpunkte):
 *  - XML-RPC (xmlrpc.php) deaktivieren und den zugehörigen Pingback-Header entfernen
 *  - REST-API-User-Enumeration (/wp-json/wp/v2/users) für nicht angemeldete Besucher blocken
 *  - ?author=N-Enumeration blocken (Autoren-Archiv auf die Startseite umleiten)
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( is_user_logged_in() ) { return $endpoints; }
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( preg_match( '#^/wp/v2/users#', $route ) ) { unset( $endpoints[ $route ] ); }
	}
	return $endpoints;
} );

add_action( 'template_redirect', function () {
	if ( is_author() && ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );
