<?php
/**
 * edi-integrationen – Matomo-Tracking (datenschutzbewusst).
 *
 * Kein SaaS-Analytics-Skript (Google Analytics & Co.), sondern eine
 * Einbindung für eine selbst gehostete bzw. eigene Matomo-Instanz – URL und
 * Site-ID kommen ausschließlich aus den Plugin-Einstellungen, es ist keine
 * fremde Instanz fest hinterlegt. Fehlt eines von beidem: no-op, es wird
 * kein Skript eingebunden.
 *
 * DSGVO/TTDSG: Standardmäßig "cookielos" (disableCookies() + Do-Not-Track
 * respektieren) – Matomo setzt dann keinerlei Tracking-Cookie und verzichtet
 * auf Cross-Session-Fingerprinting. Nach gängiger Einschätzung (siehe u. a.
 * die Matomo-eigene Dokumentation "Do I need a cookie consent banner")
 * fällt das nicht unter das Einwilligungserfordernis des § 25 TTDSG, weil
 * keine Information auf dem Endgerät gespeichert/ausgelesen wird. Das
 * ersetzt keine Rechtsberatung im Einzelfall – siehe SYSTEM-REPORT.md.
 * Wer stattdessen klassisches Consent-Tracking mit Cookie-Banner möchte,
 * kann "Cookielos tracken" in den Einstellungen abschalten; die IP-
 * Anonymisierung ist serverseitig in der Matomo-Instanz selbst zu aktivieren
 * (dort, nicht hier im JavaScript, sitzt die maßgebliche Einstellung).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Matomo-Basis-URL ohne abschließenden Slash, oder ''. */
function fahrschule_matomo_url(): string {
	return untrailingslashit( (string) get_option( 'fahrschule_matomo_url', '' ) );
}

function fahrschule_matomo_enabled(): bool {
	return '' !== fahrschule_matomo_url() && (int) get_option( 'fahrschule_matomo_site_id', 0 ) > 0;
}

/**
 * Trackingcode – nur wenn URL + Site-ID hinterlegt sind.
 *
 * Der cookielose Modus (Standard, siehe Docblock oben) bleibt bewusst
 * ungegatet - er setzt per Definition keine Cookies und braucht deshalb
 * keine Einwilligung. Wird er in den Einstellungen abgeschaltet, greift das
 * Cookie-Banner des Themes (fs_render_cookie_banner() in
 * inc/render-callbacks.php): erst nach "Alle akzeptieren" (Cookie
 * fs_consent=all) wird überhaupt geladen. Ohne das Theme (Cookie also nie
 * gesetzt) bleibt der nicht-cookielose Modus dann schlicht endgültig aus -
 * sicherer Fallback statt eines Fehlers.
 */
function fahrschule_matomo_head() {
	if ( ! fahrschule_matomo_enabled() ) { return; }

	$cookieless = (bool) get_option( 'fahrschule_matomo_cookieless', true );
	if ( ! $cookieless ) {
		$consent = isset( $_COOKIE['fs_consent'] ) ? sanitize_key( wp_unslash( $_COOKIE['fs_consent'] ) ) : '';
		if ( 'all' !== $consent ) { return; }
	}

	$url     = trailingslashit( fahrschule_matomo_url() );
	$site_id = (string) (int) get_option( 'fahrschule_matomo_site_id', 0 );
	?>
	<script>
	var _paq = window._paq = window._paq || [];
	<?php if ( $cookieless ) : ?>
	_paq.push(['disableCookies']);
	_paq.push(['setDoNotTrack', true]);
	<?php endif; ?>
	_paq.push(['trackPageView']);
	_paq.push(['enableLinkTracking']);
	(function () {
		var u = <?php echo wp_json_encode( $url ); ?>;
		_paq.push(['setTrackerUrl', u + 'matomo.php']);
		_paq.push(['setSiteId', <?php echo wp_json_encode( $site_id ); ?>]);
		var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
		g.async = true; g.src = u + 'matomo.js';
		s.parentNode.insertBefore(g, s);
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'fahrschule_matomo_head', 30 );

/**
 * Opt-out-Baustein für die Datenschutzerklärung: [edi_matomo_optout]
 * Matomo liefert unter der eigenen Instanz ein einbettbares Opt-out-Iframe.
 * Ohne Konfiguration gibt der Shortcode nichts aus (kein totes Iframe).
 */
function fahrschule_matomo_optout_shortcode() {
	if ( ! fahrschule_matomo_enabled() ) { return ''; }
	$src = trailingslashit( fahrschule_matomo_url() ) . 'index.php?module=CoreAdminHome&action=optOut&language=de';
	return sprintf(
		'<iframe style="border:0;width:100%%;max-width:600px;height:200px" src="%s" title="Matomo Opt-out"></iframe>',
		esc_url( $src )
	);
}
add_shortcode( 'edi_matomo_optout', 'fahrschule_matomo_optout_shortcode' );

add_action( 'admin_init', function () {
	register_setting( 'edi_integrationen', 'fahrschule_matomo_url', array( 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
	register_setting( 'edi_integrationen', 'fahrschule_matomo_site_id', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
	register_setting( 'edi_integrationen', 'fahrschule_matomo_cookieless', array( 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
} );
