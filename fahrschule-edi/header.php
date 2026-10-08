<?php
/**
 * Kopfbereich – wird von allen Templates eingebunden.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
	<noscript>
		<!-- Ohne JavaScript werden Scroll-Animationen übersprungen,
		     damit alle Inhalte sichtbar und crawlbar bleiben.
		     Cookie-Banner und TikTok-Badge werden ausgeblendet: beide
		     lassen sich nur per JavaScript schließen und würden sonst
		     dauerhaft den unteren Bildrand belegen (mobil 216px) und
		     einander überdecken. Ohne JS lädt das Theme ohnehin keine
		     einwilligungspflichtigen Ressourcen. -->
		<style>
			.fs-fade-up{opacity:1 !important;animation:none !important}
			.fs-cookiebanner,.fs-tiktok{display:none !important}
		</style>
	</noscript>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="fs-skip" href="#content">Zum Inhalt springen</a>
<?php
// Globale Navigation (zentral registrierter Block).
echo fs_block( 'fahrschule/navigation' );
?>
<main id="content">
