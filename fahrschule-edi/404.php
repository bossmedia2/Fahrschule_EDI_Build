<?php
/**
 * 404 – Seite nicht gefunden.
 *
 * Liefert korrekt einen 404-Status (WordPress setzt ihn automatisch) und wird
 * über den wp_robots-Filter auf noindex gesetzt. Statt einer Sackgasse bietet
 * die Seite Wege zurück in relevante Bereiche.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<div class="fs-pagetop"></div>
<section class="fs-section fs-surface-brand">
	<div class="fs-container fs-z">
		<div class="fs-head">
			<span class="fs-eyebrow">Fehler 404</span>
			<h1 style="font-size:clamp(2.5rem,7vw,4rem);font-weight:800;margin:.75rem 0 1rem;">
				Diese Seite gibt es <span class="fs-accent">nicht mehr</span>
			</h1>
			<p>Die aufgerufene Adresse führt ins Leere. Vielleicht hilft einer dieser Wege weiter.</p>
		</div>

		<div class="fs-features">
			<a class="fs-feature" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php echo fs_icon( 'arrow-right', 22 ); ?><span>Zur Startseite</span>
			</a>
			<a class="fs-feature" href="<?php echo esc_url( home_url( '/fuehrerscheinklassen/' ) ); ?>">
				<?php echo fs_icon( 'arrow-right', 22 ); ?><span>Führerscheinklassen ansehen</span>
			</a>
			<a class="fs-feature" href="<?php echo esc_url( home_url( '/standorte/' ) ); ?>">
				<?php echo fs_icon( 'map-pin', 22 ); ?><span>Unsere Standorte</span>
			</a>
			<a class="fs-feature" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">
				<?php echo fs_icon( 'phone', 22 ); ?><span>Kontakt aufnehmen</span>
			</a>
		</div>

		<div class="fs-center fs-mt-8">
			<?php get_search_form(); ?>
		</div>
	</div>
</section>
<?php
get_footer();
