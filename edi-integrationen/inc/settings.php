<?php
/**
 * edi-integrationen – Einstellungsseite (Einstellungen → edi-integrationen).
 *
 * Das Fahrschule-edi-Theme hat noch
 * keine eigene Einstellungsseite mit einem Erweiterungspunkt für Plugins -
 * eine eigene Seite ist hier deshalb einfacher als ein Hook, den es im
 * Theme noch gar nicht gibt. Das Theme bleibt trotzdem unabhängig: siehe
 * Docblock in edi-integrationen.php.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
	add_options_page(
		'edi-integrationen',
		'edi-integrationen',
		'manage_options',
		'edi-integrationen',
		'fahrschule_integrationen_settings_page'
	);
} );

function fahrschule_integrationen_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	?>
	<div class="wrap">
		<h1>edi-integrationen</h1>
		<p>Formulare, Stellenanzeigen und Statistik für die Website der Fahrschule edi. Leere Felder bedeuten: keine Anbindung – die Website funktioniert unverändert auch ohne sie.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'edi_integrationen' ); ?>
			<h2>Formulare</h2>
			<p>Die Formulare legt das Plugin in Contact Form 7 an (Kontakt → Formulare). Empfänger kommen aus den Stammdaten des Themes, Einsendungen speichert Flamingo.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( array( 'kontakt' => 'Kontakt', 'anmeldung' => 'Anmeldung', 'schnellanmeldung' => 'Schnellanmeldung', 'bewerbung' => 'Bewerbung' ) as $art => $label ) : $fid = (int) get_option( 'edi_cf7_' . $art, 0 ); ?>
				<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><?php echo $fid ? '<a href="' . esc_url( admin_url( 'admin.php?page=wpcf7&post=' . $fid . '&action=edit' ) ) . '">Formular #' . (int) $fid . ' bearbeiten</a>' : 'noch nicht angelegt – Contact Form 7 aktivieren'; ?></td></tr>
				<?php endforeach; ?>
			</table>

			<h2>Matomo (Statistik)</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fahrschule_matomo_url">Matomo-URL</label></th>
					<td>
						<input type="url" id="fahrschule_matomo_url" name="fahrschule_matomo_url" class="regular-text"
							placeholder="https://analytics.beispiel.de/"
							value="<?php echo esc_attr( get_option( 'fahrschule_matomo_url', '' ) ); ?>">
						<p class="description">Optional. Adresse der eigenen/selbst gehosteten Matomo-Instanz. Leer = keine Statistik-Erfassung.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fahrschule_matomo_site_id">Matomo Site-ID</label></th>
					<td>
						<input type="number" min="1" step="1" id="fahrschule_matomo_site_id" name="fahrschule_matomo_site_id" class="small-text"
							value="<?php echo esc_attr( get_option( 'fahrschule_matomo_site_id', '' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">Cookielos tracken</th>
					<td>
						<input type="hidden" name="fahrschule_matomo_cookieless" value="0">
						<label>
							<input type="checkbox" name="fahrschule_matomo_cookieless" value="1" <?php checked( get_option( 'fahrschule_matomo_cookieless', true ) ); ?>>
							Keine Tracking-Cookies setzen und Do-Not-Track respektieren (empfohlen – ohne dies i. d. R. kein Cookie-Banner nötig)
						</label>
						<p class="description">
							Nur abschalten, wenn ein eigenes Cookie-Consent-Tool die Einwilligung
							regelt. Opt-out-Baustein für die Datenschutzseite:
							<code>[edi_matomo_optout]</code>.
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
