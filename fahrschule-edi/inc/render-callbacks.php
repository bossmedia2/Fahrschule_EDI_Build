<?php
/**
 * Fahrschule edi – Render-Callbacks (Teil 1: Startseite & globale Bausteine)
 *
 * Jede Funktion rendert serverseitig das HTML eines dynamischen Gutenberg-Blocks.
 * Inhalte aus dem PDF "Texte_Webseite_Fahrschule" sind als Default-Werte eingepflegt.
 *
 * @package Fahrschule_Edi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* -------------------------------------------------------------------------
 * NAVIGATION / HEADER
 * ---------------------------------------------------------------------- */
function fs_render_navigation( $a ) {
	$logo = ! empty( $a['logo'] ) ? $a['logo'] : fs_media( 'logo' );

	// Navigationsstruktur wird zentral aus der Seiten-Registry erzeugt.
	$map     = fahrschule_page_map();
	$groups  = fahrschule_nav_groups();
	$current = fahrschule_current_key();

	// Seiten den Gruppen zuordnen.
	$grouped = array();
	$singles = array();
	foreach ( $map as $key => $page ) {
		if ( 'none' === $page['nav'] ) { continue; }
		$g = isset( $page['group'] ) ? $page['group'] : '';
		if ( $g && isset( $groups[ $g ] ) ) {
			$grouped[ $g ][ $key ] = $page;
		} else {
			$singles[ $key ] = $page;
		}
	}

	ob_start(); ?>
	<nav class="fs-nav" data-fs-nav aria-label="Hauptnavigation">
		<div class="fs-container">
			<div class="fs-nav__inner">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fs-nav__logo" aria-label="Fahrschule edi – zur Startseite">
					<img src="<?php echo esc_url( $logo ); ?>" alt="Fahrschule &amp; Fahrlehrerfachschule edi" width="87" height="40" decoding="async" fetchpriority="high">
				</a>

				<div class="fs-nav__links">
					<?php foreach ( $groups as $gkey => $group ) :
						if ( empty( $grouped[ $gkey ] ) ) { continue; }
						$parent_key = $group['parent'];
						$is_active  = ( $parent_key && $parent_key === $current );
						foreach ( $grouped[ $gkey ] as $k => $pg ) { if ( $k === $current ) { $is_active = true; } }
						?>
						<div class="fs-nav__drop">
							<?php if ( $parent_key ) : ?>
								<a class="fs-nav__toplink<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( fs_url( $parent_key ) ); ?>"<?php echo $parent_key === $current ? ' aria-current="page"' : ''; ?>>
									<?php echo esc_html( $group['label'] ); ?> <?php echo fs_icon( 'chevron-down', 16 ); ?>
								</a>
							<?php else : ?>
								<button type="button" class="fs-nav__toplink<?php echo $is_active ? ' is-active' : ''; ?>" aria-expanded="false">
									<?php echo esc_html( $group['label'] ); ?> <?php echo fs_icon( 'chevron-down', 16 ); ?>
								</button>
							<?php endif; ?>
							<div class="fs-nav__menu">
								<?php foreach ( $grouped[ $gkey ] as $k => $pg ) : ?>
									<a href="<?php echo esc_url( fs_url( $k ) ); ?>"<?php echo $k === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $pg['menu'] ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>

					<?php foreach ( $singles as $k => $pg ) : ?>
						<a class="fs-nav__toplink<?php echo $k === $current ? ' is-active' : ''; ?>" href="<?php echo esc_url( fs_url( $k ) ); ?>"<?php echo $k === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $pg['menu'] ); ?></a>
					<?php endforeach; ?>
				</div>

				<div class="fs-nav__actions">
					<a class="fs-btn fs-btn--outline fs-nav__cta" href="<?php echo esc_url( fahrschule_phone( 'link' ) ); ?>"><?php echo fs_icon( 'phone', 16 ); ?> <span><?php echo esc_html( fahrschule_phone() ); ?></span></a>
					<a class="fs-btn fs-btn--primary fs-nav__cta" href="<?php echo esc_url( fs_url( 'anmeldung' ) ); ?>">Jetzt anmelden</a>
					<button type="button" class="fs-nav__burger" data-fs-burger aria-label="Menü öffnen" aria-expanded="false" aria-controls="fs-drawer">
						<?php echo fs_icon( 'menu', 24 ); ?>
					</button>
				</div>
			</div>
		</div>
	</nav>

	<div class="fs-drawer-overlay" data-fs-overlay></div>
	<div class="fs-drawer" id="fs-drawer" data-fs-drawer>
		<?php $gi = 0; foreach ( $groups as $gkey => $group ) :
			if ( empty( $grouped[ $gkey ] ) ) { continue; }
			$gi++;
			// Gruppe der geöffneten Seite steht im Schubfach gleich offen.
			$sub_open = ( $group['parent'] && $group['parent'] === $current ) || isset( $grouped[ $gkey ][ $current ] ); ?>
			<div class="fs-drawer__group">
				<button type="button" class="fs-drawer__toggle" data-fs-subtoggle aria-expanded="<?php echo $sub_open ? 'true' : 'false'; ?>" aria-controls="fs-sub-<?php echo (int) $gi; ?>">
					<?php echo esc_html( $group['label'] ); ?> <?php echo fs_icon( 'chevron-down', 16 ); ?>
				</button>
				<div class="fs-drawer__sub<?php echo $sub_open ? ' is-open' : ''; ?>" id="fs-sub-<?php echo (int) $gi; ?>" data-fs-sub>
					<?php // "Übersicht" nur, wenn die Elternseite nicht ohnehin als Eintrag der Gruppe erscheint.
					if ( $group['parent'] && ! isset( $grouped[ $gkey ][ $group['parent'] ] ) ) : ?>
						<a href="<?php echo esc_url( fs_url( $group['parent'] ) ); ?>"<?php echo $group['parent'] === $current ? ' aria-current="page"' : ''; ?>>Übersicht</a>
					<?php endif; ?>
					<?php foreach ( $grouped[ $gkey ] as $k => $pg ) : ?>
						<a href="<?php echo esc_url( fs_url( $k ) ); ?>"<?php echo $k === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $pg['menu'] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<?php foreach ( $singles as $k => $pg ) : ?>
			<a class="fs-drawer__link" href="<?php echo esc_url( fs_url( $k ) ); ?>"<?php echo $k === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $pg['menu'] ); ?></a>
		<?php endforeach; ?>

		<div class="fs-drawer__actions">
			<a class="fs-btn fs-btn--outline fs-btn--block" href="<?php echo esc_url( fahrschule_phone( 'link' ) ); ?>"><?php echo fs_icon( 'phone', 16 ); ?> Anrufen</a>
			<a class="fs-btn fs-btn--primary fs-btn--block" href="<?php echo esc_url( fs_url( 'anmeldung' ) ); ?>">Jetzt anmelden</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * VIDEO HERO (Startseite) – PDF: Hero-Text
 * ---------------------------------------------------------------------- */
function fs_render_video_hero( $a ) {
	$f1 = fahrschule_pdf( 'f1' );
	$d  = fs_attrs( $a, array(
		'badge'      => 'Seit über 30 Jahren für Sie da',
		'heading'    => $f1['headline'],
		'highlight'  => $f1['highlight'],
		'text'       => implode( "\n\n", $f1['paragraphs'] ),
		'btn1_label' => 'Jetzt anmelden',
		'btn1_url'   => '@anmeldung',
		'btn2_label' => 'Mehr erfahren',
		'btn2_url'   => '@klassen',
		'video_url'  => fs_media( 'video_hero' ),
		'standbild'  => fs_media( 'video_hero_bild' ),
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$video   = trim( (string) $d['video_url'] );
	$bild    = trim( (string) $d['standbild'] );
	// Editor-Vorschau ohne Video: das Standbild genügt, und ein laufendes
	// Video im Editor hängt an keinem Frontend-Skript (harte Regel 2).
	$mit_video = '' !== $video && ! fs_is_editor_preview();
	$webm      = $mit_video ? fs_media( 'video_hero_webm' ) : '';
	$hint      = ( '' === $video && '' === $bild )
		? fs_editor_hint( 'Hero:', 'Weder Video (edi-video-hero.mp4) noch Standbild (edi-video-hero-standbild) in der Mediathek – bis dahin Vollpink.' )
		: '';
	/* Absätze im Textfeld durch Leerzeilen getrennt (PDF-Abschnitt F1: vier Absätze). */
	$paragraphs = array_filter( array_map( 'trim', preg_split( '/\R\s*\R/u', (string) $d['text'] ) ) );
	ob_start(); ?>
	<?php echo $hint; ?>
	<section class="fs-vhero<?php echo ( '' === $video && '' === $bild ) ? ' fs-vhero--ohne-medien' : ''; ?>" data-pdf-section="F1"<?php echo '' !== $bild ? ' style="background-image:url(' . esc_url( $bild ) . ')"' : ''; ?>>
		<?php if ( $mit_video ) : ?>
		<?php /* Kein autoplay: frontend.js startet das Video nur ohne
		         „Bewegung reduzieren“. Bis dahin (und ohne JS) steht das Poster. */ ?>
		<video class="fs-vhero__media" muted playsinline loop preload="metadata" aria-hidden="true" data-fs-hero-video<?php echo '' !== $bild ? ' poster="' . esc_url( $bild ) . '"' : ''; ?>>
			<?php if ( $webm ) : ?><source src="<?php echo esc_url( $webm ); ?>" type="video/webm"><?php endif; ?>
			<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
		</video>
		<?php endif; ?>
		<?php if ( '' !== $video || '' !== $bild ) : ?><div class="fs-vhero__overlay"></div><?php endif; ?>
		<div class="fs-container fs-vhero__inner">
			<?php echo fs_el( 'span', $d['badge'], 'class="fs-badge fs-fade-up"' ); ?>
			<?php echo fs_el( 'h1', '', 'class="fs-fade-up"', $heading ); ?>
			<?php echo fs_wrap( 'div', fs_paragraphs( $d['text'] ), 'class="fs-vhero__text fs-fade-up"' ); ?>
			<div class="fs-hero-actions fs-fade-up">
				<a class="fs-btn fs-btn--lg fs-btn--primary-white" href="<?php echo esc_url( fs_link( $d['btn1_url'] ) ); ?>"><?php echo esc_html( $d['btn1_label'] ); ?> <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
				<a class="fs-btn fs-btn--lg fs-btn--ghost-white" href="<?php echo esc_url( fs_link( $d['btn2_url'] ) ); ?>"><?php echo fs_icon( 'play', 20 ); ?> <?php echo esc_html( $d['btn2_label'] ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * HERO (helle Variante)
 * ---------------------------------------------------------------------- */
function fs_render_hero( $a ) {
	$d = fs_attrs( $a, array(
		'badge'     => 'Seit über 30 Jahren',
		'heading'   => fahrschule_pdf_text( 'f1', 'headline' ),
		'highlight' => fahrschule_pdf_text( 'f1', 'highlight' ),
		'text'      => fahrschule_pdf_text( 'f1', 'p', 0 ),
		'btn1_label'=> 'Jetzt anmelden', 'btn1_url' => '@anmeldung',
		'btn2_label'=> 'Kostenlose Beratung', 'btn2_url' => '@klassen',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-hero fs-surface-brand">
		<div class="fs-hero__bg"></div>
		<div class="fs-container fs-hero__inner fs-z">
			<?php echo fs_el( 'span', $d['badge'], 'class="fs-badge fs-fade-up"' ); ?>
			<?php echo fs_el( 'h1', '', 'class="fs-fade-up"', $heading ); ?>
			<?php echo fs_el( 'p', $d['text'], 'class="fs-fade-up"' ); ?>
			<div class="fs-hero-actions fs-fade-up">
				<?php echo fs_button( $d['btn1_label'], $d['btn1_url'], 'fs-btn fs-btn--lg fs-btn--primary-white' ); ?>
				<?php echo fs_button( $d['btn2_label'], $d['btn2_url'], 'fs-btn fs-btn--lg fs-btn--outline' ); ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FÜHRERSCHEINKLASSEN (Karten-Grid)
 * ---------------------------------------------------------------------- */
function fs_render_license_classes( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'  => 'Führerscheinklassen',
		'heading'  => 'Alle Führerscheinklassen',
		'subtitle' => 'Von der Richtpreis-Berechnung bis zur professionellen Ausbildung',
	) );
	// Zeilen kommen aus dem Wiederholfeld; leere Zeilen hat fs_rows() entfernt.
	$classes = fs_rows( $d['classes'], 6 );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>
			<?php if ( $classes ) : ?>
			<div class="fs-grid fs-grid--3 fs-wide" style="margin-inline:auto;">
				<?php foreach ( $classes as $c ) : ?>
				<article class="fs-card fs-fade-up">
					<div class="fs-card__body">
						<div class="fs-mb-8" style="margin-bottom:1rem;">
							<h3 class="fs-license__name"><?php echo esc_html( $c[0] ); ?></h3>
							<p class="fs-license__sub"><?php echo esc_html( $c[1] ); ?></p>
						</div>
						<p class="fs-license__desc"><?php echo esc_html( $c[2] ); ?></p>
						<div style="margin-bottom:1rem;">
							<div class="fs-license__row"><span>Fahrstunden:</span><strong><?php echo esc_html( $c[3] ); ?></strong></div>
							<div class="fs-license__row"><span>Geschätzter Preis:</span><strong class="price"><?php echo esc_html( $c[4] ); ?></strong></div>
							<div class="fs-license__row"><span>Mindestalter:</span><strong><?php echo esc_html( $c[5] ); ?></strong></div>
						</div>
						<a class="fs-btn fs-btn--primary fs-btn--block" href="<?php echo esc_url( fs_url( 'preise' ) ); ?>">Details <?php echo fs_icon( 'arrow-right', 16 ); ?></a>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FÜHRERSCHEIN-RECHNER
 * ---------------------------------------------------------------------- */
function fs_render_calculator( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'  => 'Transparente Preise',
		'heading'  => 'Führerschein-Rechner',
		'subtitle' => 'Kalkulieren Sie schnell und einfach Ihren Gesamtpreis',
	) );
	// Preisdaten als data-Attribut für die Frontend-Berechnung.
	$prices = wp_json_encode( array(
		'B'  => array( 1650, 2100, 32 ), 'BE' => array( 900, 1500, 20 ),
		'A'  => array( 1200, 1800, 25 ), 'A1' => array( 800, 1200, 15 ),
		'AM' => array( 600, 900, 12 ),   'C'  => array( 1800, 2500, 30 ),
	) );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>
			<div class="fs-calc" data-fs-calc data-prices='<?php echo esc_attr( $prices ); ?>'>
				<div class="fs-calc__body">
					<div>
						<label>Führerscheinklasse</label>
						<div class="fs-pillgrid fs-pillgrid--3" data-fs-group="class">
							<?php foreach ( array( 'B', 'BE', 'A', 'A1', 'AM', 'C' ) as $i => $k ) : ?>
							<button type="button" class="fs-pill<?php echo 0 === $i ? ' is-active' : ''; ?>" data-val="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $k ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>
					<div>
						<label>Ausbildungstyp</label>
						<div class="fs-pillgrid" data-fs-group="type">
							<button type="button" class="fs-pill is-active" data-val="neuerwerb">Neuerwerb</button>
							<button type="button" class="fs-pill" data-val="umstieg">Umstieg</button>
						</div>
					</div>
					<div>
						<label>Getriebe</label>
						<div class="fs-pillgrid" data-fs-group="transmission">
							<button type="button" class="fs-pill" data-val="auto">Automatik</button>
							<button type="button" class="fs-pill is-active" data-val="both">Automatik &amp; Schalter</button>
						</div>
					</div>
					<div class="fs-calc__result">
						<div class="fs-mb-8" style="margin-bottom:1rem;">
							<p class="label">Fahrstunden (Schätzung):</p>
							<p class="fs-calc__hours" data-fs-hours>32 Stunden</p>
						</div>
						<div style="margin-bottom:1.5rem;">
							<p class="label">Geschätzter Gesamtpreis:</p>
							<p class="fs-calc__price" data-fs-price>€1.650 - €2.100</p>
							<p class="fs-calc__hint">* Richtpreis ohne Gewähr. Individuelle Beratung empfohlen.</p>
						</div>
						<a class="fs-btn fs-btn--primary fs-btn--block" href="<?php echo esc_url( fs_url( 'kontakt' ) ); ?>">Jetzt anmelden <?php echo fs_icon( 'arrow-right', 18 ); ?></a>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * STANDORTE (Übersicht) – PDF: echte Adressen/Telefon/E-Mail
 * ---------------------------------------------------------------------- */
function fs_render_locations( $a ) {
	$f8 = fahrschule_pdf( 'f8' );
	$d  = fs_attrs( $a, array(
		'eyebrow'  => $f8['label'],
		'heading'  => $f8['headline'],
		'subtitle' => $f8['paragraphs'][0],
		'head'     => '1',
		'kompakt'  => '0',
	) );
	// Kompakt (Startseite, Kontakt): ohne Fotos und Absatz, je Karte ein Link
	// auf die Standortseite. Voll steht F8 nur auf /standorte/.
	$kompakt = '1' === (string) $d['kompakt'];
	if ( $kompakt ) { $d['subtitle'] = ''; }
	$photos = array( 'Duisburg' => 'loc_duisburg', 'Gelsenkirchen' => 'loc_gelsenkirchen', 'Essen' => 'loc_essen' );
	$hx     = fahrschule_pdf_show_head( $d ) ? 'h3' : 'h2'; // Gliederung ohne Sprung h1 -> h3
	ob_start(); ?>
	<section class="fs-section <?php echo $kompakt ? 'fs-surface-brand' : 'fs-surface-light'; ?>" <?php echo $kompakt ? 'data-pdf-teaser' : 'data-pdf-section'; ?>="F8">
		<div class="fs-container fs-z">
			<div class="fs-head">
				<?php if ( fahrschule_pdf_show_head( $d ) ) : ?>
				<?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?>
				<?php echo fs_el( 'h2', $d['heading'] ); ?>
				<?php endif; ?>
				<?php echo fs_el( 'p', $d['subtitle'] ); ?>
			</div>
			<div class="fs-grid fs-grid--3 fs-wide" style="margin-inline:auto;">
				<?php /* Reihenfolge, Adressen, Telefon und E-Mail wie in PDF-Abschnitt F8. */ ?>
				<?php foreach ( $f8['locations'] as $city => $loc ) : ?>
				<article class="fs-card fs-card--glass fs-fade-up">
					<?php if ( ! $kompakt && fs_has_media( $photos[ $city ] ) ) : ?>
					<div class="fs-loc__img"><img src="<?php echo esc_url( fs_media( $photos[ $city ] ) ); ?>" alt="Fahrschule edi Standort <?php echo esc_attr( $city ); ?> – Außenansicht" width="600" height="400" loading="lazy" decoding="async"></div>
					<?php endif; ?>
					<div class="fs-card__body">
						<<?php echo $hx; ?> class="fs-loc__title"><?php echo esc_html( $city ); ?></<?php echo $hx; ?>>
						<div class="fs-loc__line"><?php echo fs_icon( 'map-pin', 20 ); ?><span><?php echo esc_html( $loc['street'] ); ?><br><?php echo esc_html( $loc['zip'] ); ?></span></div>
						<div class="fs-loc__line"><?php echo fs_icon( 'phone', 20 ); ?><span class="fs-visually-hidden">Telefon:</span> <a href="tel:<?php echo esc_attr( fs_tel( $loc['phone'] ) ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a></div>
						<div class="fs-loc__line"><?php echo fs_icon( 'mail', 20 ); ?><span class="fs-visually-hidden">E-Mail:</span> <a href="mailto:<?php echo esc_attr( $loc['email'] ); ?>"><?php echo esc_html( $loc['email'] ); ?></a></div>
						<div class="fs-loc__line"><?php echo fs_icon( 'clock', 20 ); ?><span><?php echo fs_nl2br( fahrschule_location_hours_text( $city ) ); ?></span></div>
						<?php if ( $kompakt ) : ?>
						<a class="fs-btn fs-btn--primary fs-btn--block fs-mt-6" href="<?php echo esc_url( fs_url( sanitize_key( $city ) ) ); ?>">Zum Standort <?php echo esc_html( $city ); ?></a>
						<?php else : ?>
						<a class="fs-btn fs-btn--primary fs-btn--block fs-mt-6" href="<?php echo esc_url( fs_url( 'kontakt' ) ); ?>">Kontakt</a>
						<?php endif; ?>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * TEAM SPIRIT
 * ---------------------------------------------------------------------- */
function fs_render_team_spirit( $a ) {
	$d = fs_attrs( $a, array(
		'badge'    => 'Unser Team Spirit',
		'heading'  => 'Eine Familie voller Leidenschaft',
		'highlight'=> 'Familie',
		'text'     => 'Bei Fahrschule edi sind wir nicht nur Instruktoren – wir sind Mentoren, Motivatoren und Ihre zuverlässigen Partner auf dem Weg zum Führerschein. Unsere offene Atmosphäre, gepaart mit professioneller Kompetenz, macht die Fahrausbildung zu einem positiven Erlebnis.',
		'quote'    => 'Bei uns lernen Sie nicht nur fahren – Sie werden Teil einer Gemeinschaft, die Sie auf Ihrem Weg begleitet und Sie anfeuert.',
		'quote_by' => '– Das edi Team',
		'video_url'=> fs_media( 'video_team' ),
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$values = fs_rows( $d['values'], 3 );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-z">
			<div class="fs-split">
				<div class="fs-video-frame">
					<video autoplay muted loop playsinline preload="none" aria-label="Video: Das Team der Fahrschule edi">
						<source src="<?php echo esc_url( $d['video_url'] ); ?>" type="video/mp4">
					</video>
				</div>
				<div class="fs-prose fs-stack">
					<div>
						<span class="fs-badge fs-mb-8" style="margin-bottom:1.5rem;"><?php echo fs_icon( 'users', 16 ); ?> <?php echo esc_html( $d['badge'] ); ?></span>
						<?php echo fs_el( 'h2', '', '', $heading ); ?>
						<?php echo fs_el( 'p', $d['text'] ); ?>
					</div>
					<?php if ( $values ) : ?>
					<div class="fs-values">
						<?php foreach ( $values as $v ) : ?>
						<div class="fs-value">
							<div class="fs-value__ico"><?php echo fs_icon( $v[0], 24 ); ?></div>
							<h3><?php echo esc_html( $v[1] ); ?></h3>
							<p><?php echo esc_html( $v[2] ); ?></p>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					<div class="fs-quote">
						<p>"<?php echo esc_html( $d['quote'] ); ?>"</p>
						<?php echo fs_el( 'p', $d['quote_by'], 'class="by"' ); ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FLOTTE & EINRICHTUNGEN
 * ---------------------------------------------------------------------- */
function fs_render_fleet_facilities( $a ) {
	$fleet = array(
		array( 'Fahrzeugflotte', 'PKW & LKW', fs_media( 'fleet_pkw' ) ),
		array( 'Lkw-Ausbildung', 'C, C1, CE Klassen', fs_media( 'fleet_lkw' ) ),
		array( 'Motorradschule', 'A, A1, AM Klassen', fs_media( 'fleet_motorrad' ) ),
		array( 'Schulungsbus', 'Theorieunterricht & Events', fs_media( 'fleet_bus' ) ),
	);
	$fac = array(
		array( 'Modernstes Klassenzimmer', 'Technisch ausgestattet', fs_media( 'fac_klassenraum' ) ),
		array( 'Willkommensbereich', 'LED Begrüßungsbildschirm', fs_media( 'fac_empfang' ) ),
		array( 'Modernes Büro', 'Mit Mitarbeiterzonen', fs_media( 'fac_buero' ) ),
	);
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<div style="margin-bottom:6rem;">
				<div class="fs-head"><h2>Unsere <span class="fs-accent">Fahrzeugflotte</span></h2><p>Moderne und gut gepflegte Fahrzeuge für alle Führerscheinklassen. Alle Autos sind regelmäßig gewartet und vollständig versichert.</p></div>
				<div class="fs-grid fs-grid--4">
					<?php foreach ( $fleet as $f ) : ?>
					<div class="fs-tile"><div class="fs-tile__img"><img src="<?php echo esc_url( $f[2] ); ?>" alt="<?php echo esc_attr( $f[0] ); ?> der Fahrschule edi – <?php echo esc_attr( $f[1] ); ?>" width="600" height="400" loading="lazy" decoding="async"><div class="fs-tile__shade"></div></div><div class="fs-tile__cap"><h3><?php echo esc_html( $f[0] ); ?></h3><p><?php echo esc_html( $f[1] ); ?></p></div></div>
					<?php endforeach; ?>
				</div>
			</div>
			<div>
				<div class="fs-head"><h2>Unsere <span class="fs-accent">Einrichtungen</span></h2><p>Professionelle Schulungsräume mit moderner Technik. Alles, was Sie für eine erfolgreiche Ausbildung brauchen.</p></div>
				<div class="fs-grid fs-grid--3">
					<?php foreach ( $fac as $f ) : ?>
					<div class="fs-tile fs-tile--lg"><div class="fs-tile__img"><img src="<?php echo esc_url( $f[2] ); ?>" alt="<?php echo esc_attr( $f[0] ); ?> der Fahrschule edi – <?php echo esc_attr( $f[1] ); ?>" width="600" height="400" loading="lazy" decoding="async"><div class="fs-tile__shade"></div></div><div class="fs-tile__cap"><h3><?php echo esc_html( $f[0] ); ?></h3><p><?php echo esc_html( $f[1] ); ?></p></div></div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="fs-cta-band">
				<h3>Besuchen Sie uns persönlich!</h3>
				<p>Schauen Sie sich unsere Einrichtungen an und lernen Sie unser Team kennen.</p>
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( fs_url( 'kontakt' ) ); ?>">Vereinbaren Sie einen Termin</a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * TESTIMONIALS (Startseite)
 * ---------------------------------------------------------------------- */
function fs_render_testimonials( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'  => 'Was unsere Schüler sagen',
		'heading'  => 'Kundenbewertungen',
		'subtitle' => 'Lesen Sie, was unsere zufriedenen Fahrschüler über uns berichten',
		'reviews_url' => 'https://www.google.com/search?q=Fahrschule+edi+Duisburg+Bewertungen',
	) );
	/*
	 * Bewusst leer: Hier standen zuvor vier weitere erfundene Namen und
	 * Zitate ("Sarah M." usw. - keine dieser Personen existiert), zusätzlich
	 * zu den bereits in fs_render_testimonials_detailed() und
	 * fs_render_location_content() gefundenen und geleerten Stellen. Diese
	 * Startseiten-Sektion blendet sich automatisch aus, solange die Liste
	 * leer ist - sobald echte, mit Einwilligung veröffentlichte Zitate
	 * vorliegen, hier eintragen: array( 'Vorname N.', 'Stadt', 'Zitat' ).
	 */
	$t = array();

	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>
			<?php if ( ! $t ) : ?>
			<?php /* Solange keine freigegebenen Zitate vorliegen, verweist der
			         Abschnitt auf die echten Google-Bewertungen, statt die
			         Startseite ohne Social Proof zu lassen. */ ?>
			<div class="fs-cta-band">
				<h3>Was unsere Fahrschüler sagen</h3>
				<p>Über 30 Jahre Fahrschule edi – lesen Sie die Bewertungen unserer Fahrschülerinnen und Fahrschüler direkt bei Google.</p>
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( $d['reviews_url'] ); ?>" target="_blank" rel="noopener noreferrer">Bewertungen auf Google ansehen <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
			</div>
			<?php else : ?>
			<div class="fs-grid fs-grid--2" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $t as $item ) : ?>
				<article class="fs-card fs-card--glass fs-fade-up"><div class="fs-card__body">
					<div class="fs-stars"><?php for ( $i = 0; $i < 5; $i++ ) { echo fs_icon( 'star', 18 ); } ?></div>
					<p class="fs-testi">"<?php echo esc_html( $item[2] ); ?>"</p>
					<p class="fs-testi-name"><?php echo esc_html( $item[0] ); ?></p>
					<p class="fs-testi-meta"><?php echo esc_html( $item[1] ); ?></p>
				</div></article>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * INSTAGRAM SLIDER
 * ---------------------------------------------------------------------- */
function fs_render_instagram_slider( $a ) {
	$d = fs_attrs( $a, array(
		'heading'      => 'Folgen Sie uns',
		'highlight'    => '',
		'subtitle'     => '',
		'instaHandle'  => '',
		'instaUrl'     => '',
		'tiktokHandle' => '',
		'tiktokUrl'    => '',
		'items'        => array(),
		'url'          => '',
	) );
	if ( '' === trim( $d['instaUrl'] ) && '' !== trim( $d['url'] ) ) { $d['instaUrl'] = $d['url']; } // Altfeld bis 3.0
	$profil = array( 'instagram' => $d['instaUrl'], 'tiktok' => $d['tiktokUrl'] );
	$name   = array( 'instagram' => 'Instagram', 'tiktok' => 'TikTok' );

	/*
	 * Eine Kachel braucht Bild UND Beschreibung: ohne Bild gibt es nichts zu
	 * zeigen, ohne Beschreibung fehlt der Alt-Text (WCAG). Keine Kachel lädt
	 * etwas von TikTok oder Instagram - das Bild kommt aus der Mediathek, der
	 * Klick öffnet den Beitrag in einem neuen Tab.
	 */
	$kacheln = array();
	$fehlt   = array();
	foreach ( fs_rows( $d['items'], 4 ) as $i => $row ) {
		list( $plattform, $slug, $link, $text ) = array_map( 'trim', $row );
		$plattform = 'instagram' === strtolower( $plattform ) ? 'instagram' : 'tiktok';
		$bild      = '' !== $slug ? fs_media_by_slug( $slug ) : '';
		if ( '' === $bild ) { continue; }
		if ( '' === $text ) { $fehlt[] = $i + 1; continue; }
		$kacheln[] = array(
			'plattform' => $plattform,
			'bild'      => $bild,
			'link'      => '' !== $link ? $link : $profil[ $plattform ],
			'text'      => $text,
		);
	}
	$hinweis = $fehlt ? fs_editor_hint( 'Social-Feed', 'Kachel ' . implode( ', ', $fehlt ) . ': Bildbeschreibung fehlt – ohne sie erscheint die Kachel nicht.' ) : '';
	if ( ! $kacheln ) {
		return $hinweis ? $hinweis : fs_editor_hint( 'Social-Feed', 'Erscheint, sobald eine Kachel ein Bild (Medien-Slug) und eine Bildbeschreibung hat.' );
	}

	$knoepfe = array();
	foreach ( array( 'instagram' => 'instaHandle', 'tiktok' => 'tiktokHandle' ) as $p => $feld ) {
		if ( fs_has( $d, $feld ) && '' !== trim( $profil[ $p ] ) ) {
			$knoepfe[ $p ] = '@' . ltrim( trim( $d[ $feld ] ), '@' );
		}
	}
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-fs-ig>
		<div class="fs-container fs-wide" style="margin-inline:auto;">
			<?php echo $hinweis; ?>
			<?php if ( fs_has( $d, 'heading' ) || fs_has( $d, 'subtitle' ) ) : ?>
			<div class="fs-head">
				<?php if ( fs_has( $d, 'heading' ) ) : ?><div class="fs-ig__head"><?php echo fs_el( 'h2', '', '', $heading ); ?></div><?php endif; ?>
				<?php echo fs_el( 'p', $d['subtitle'] ); ?>
			</div>
			<?php endif; ?>
			<?php /* Alle Kacheln stehen im HTML - Editor-Vorschau und Seite ohne
			         JavaScript zeigen sie vollständig; frontend.js blendet im
			         Karussell ein Fenster von drei ein. */ ?>
			<div class="fs-ig__track" id="fs-ig-track" aria-live="polite" data-fs-ig-track>
				<?php foreach ( $kacheln as $k ) : ?>
				<a class="fs-ig__post" href="<?php echo esc_url( $k['link'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $k['text'] ); ?>" data-plattform="<?php echo esc_attr( $k['plattform'] ); ?>">
					<figure>
						<span class="fs-ig__badge"><?php echo fs_icon( $k['plattform'], 14 ); ?> <?php echo esc_html( $name[ $k['plattform'] ] ); ?></span>
						<img src="<?php echo esc_url( $k['bild'] ); ?>" alt="<?php echo esc_attr( $k['text'] ); ?>" width="500" height="500" loading="lazy" decoding="async">
						<figcaption class="fs-ig__cap"><p><?php echo esc_html( $k['text'] ); ?></p></figcaption>
					</figure>
				</a>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $kacheln ) > 3 ) : ?>
			<div class="fs-ig__nav">
				<button type="button" class="fs-ig__arrow" data-fs-ig-prev aria-label="Vorherige Beiträge" aria-controls="fs-ig-track"><?php echo fs_icon( 'chevron-left', 24 ); ?></button>
				<div class="fs-ig__dots" data-fs-ig-dots></div>
				<button type="button" class="fs-ig__arrow" data-fs-ig-next aria-label="Nächste Beiträge" aria-controls="fs-ig-track"><?php echo fs_icon( 'chevron-right', 24 ); ?></button>
			</div>
			<?php endif; ?>
			<?php if ( $knoepfe ) : ?>
			<div class="fs-center fs-mt-8 fs-ig__profile">
				<?php foreach ( $knoepfe as $p => $handle ) : ?>
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( $profil[ $p ] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo fs_icon( $p, 20 ); ?> <?php echo esc_html( $handle ); ?><span class="fs-visually-hidden"> auf <?php echo esc_html( $name[ $p ] ); ?></span></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Social-Profil aus den Stammdaten (inc/seo.php, 'social'): Link und @-Name.
 *
 * @param string $plattform instagram | tiktok
 * @return array{url:string,handle:string}
 */
function fs_social_profil( $plattform ) {
	$host = 'instagram' === $plattform ? 'instagram.com' : 'tiktok.com';
	foreach ( (array) fahrschule_business_data()['social'] as $url ) {
		if ( false !== strpos( (string) $url, $host ) ) {
			$teil = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
			return array( 'url' => $url, 'handle' => '@' . ltrim( strtok( $teil, '/' ), '@' ) );
		}
	}
	return array( 'url' => '', 'handle' => '' );
}

/* -------------------------------------------------------------------------
 * FAQ
 * ---------------------------------------------------------------------- */
function fs_render_faq( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow' => 'Häufig gestellte Fragen',
		'heading' => 'FAQ',
	) );
	// Gemeinsame Datenquelle mit dem FAQPage-Schema (inc/seo.php),
	// damit sichtbarer Text und strukturierte Daten identisch bleiben.
	$faqs = function_exists( 'fahrschule_faq_data' ) ? fahrschule_faq_data() : array();
	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] ), 'class="fs-head"' ); ?>
			<div class="fs-faq" data-fs-faq>
				<?php foreach ( $faqs as $f ) : ?>
				<div class="fs-faq__item">
					<h3 style="margin:0;"><button type="button" class="fs-faq__q" aria-expanded="false"><span><?php echo esc_html( $f[0] ); ?></span> <?php echo fs_icon( 'chevron-down', 24 ); ?></button></h3>
					<div class="fs-faq__a"><p><?php echo esc_html( $f[1] ); ?></p></div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * CTA / KONTAKT
 * ---------------------------------------------------------------------- */
function fs_render_cta( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Bereit für Ihren Führerschein?',
		'highlight' => 'Führerschein?',
		'lead'      => 'Starten Sie noch heute Ihre Fahrausbildung. Unsere Experten in Gelsenkirchen, Essen und Duisburg stehen Ihnen zur Verfügung.',
		'phone'     => '+49 (0) 203 57 85 703',
		'email'     => 'info@fahrschule-edi.de',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$stats = fs_rows( $d['stats'], 2 );
	ob_start(); ?>
	<section class="fs-cta fs-surface-brand">
		<div class="fs-cta__bg"></div>
		<div class="fs-container fs-z">
			<div class="fs-split">
				<div>
					<?php echo fs_el( 'h2', '', '', $heading ); ?>
					<?php echo fs_el( 'p', $d['lead'], 'class="fs-cta__lead"' ); ?>
					<div class="fs-mb-8">
						<div class="fs-contact-line"><?php echo fs_icon( 'phone', 24, 'fs-icon-pink' ); ?><div><p class="sub">Telefonisch erreichbar</p><?php echo fs_el( 'p', $d['phone'], 'class="val"' ); ?></div></div>
						<div class="fs-contact-line"><?php echo fs_icon( 'mail', 24, 'fs-icon-pink' ); ?><div><p class="sub">E-Mail</p><?php echo fs_el( 'p', $d['email'], 'class="val"' ); ?></div></div>
						<div class="fs-contact-line"><?php echo fs_icon( 'map-pin', 24, 'fs-icon-pink' ); ?><div><p class="sub">Besuchen Sie uns</p><p class="val">3 Standorte im Ruhrgebiet</p></div></div>
					</div>
					<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( fs_url( 'preise' ) ); ?>">Jetzt anmelden <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
				</div>
				<?php if ( $stats ) : ?>
				<div class="fs-stats">
					<?php foreach ( $stats as $s ) : ?>
					<div class="fs-stat"><div class="num"><?php echo esc_html( $s[0] ); ?></div><p class="lbl"><?php echo esc_html( $s[1] ); ?></p></div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FOOTER
 * ---------------------------------------------------------------------- */
function fs_render_footer( $a ) {
	$logo = ! empty( $a['logo'] ) ? $a['logo'] : fs_media( 'logo' );
	ob_start(); ?>
	<footer class="fs-footer fs-surface-light">
		<div class="fs-container">
			<div class="fs-footer__grid">
				<div>
					<h3><img src="<?php echo esc_url( $logo ); ?>" alt="" width="48" height="48" loading="lazy" decoding="async" style="display:inline-block"> Fahrschule<span class="fs-accent">edi</span></h3>
					<p>Fahrschule &amp; Fahrlehrerfachschule edi GmbH – Ihre Fahrschule für alle Führerscheinklassen im Ruhrgebiet. Seit über 30 Jahren.</p>
				</div>
				<div>
					<h4>Ausbildung</h4>
					<ul>
						<li><a href="<?php echo esc_url( fs_url( 'klassen' ) ); ?>">Führerscheinklassen</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'preise' ) ); ?>">Preise &amp; Rechner</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'leistungen' ) ); ?>">Leistungen</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'moderne-ausbildung' ) ); ?>">Moderne Ausbildung</a></li>
					</ul>
				</div>
				<div>
					<h4>Beruf &amp; Service</h4>
					<ul>
						<li><a href="<?php echo esc_url( fs_url( 'berufskraftfahrer' ) ); ?>">Berufskraftfahrer</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'fahrlehrerausbildung' ) ); ?>">Fahrlehrerausbildung</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'asf-fes' ) ); ?>">ASF &amp; FES</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'faq' ) ); ?>">Häufige Fragen</a></li>
					</ul>
				</div>
				<div>
					<h4>Standorte &amp; Kontakt</h4>
					<ul>
						<li><a href="<?php echo esc_url( fs_url( 'duisburg' ) ); ?>">Duisburg</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'essen' ) ); ?>">Essen</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'gelsenkirchen' ) ); ?>">Gelsenkirchen</a></li>
						<li><a href="<?php echo esc_url( fs_url( 'kontakt' ) ); ?>">Kontakt</a></li>
						<li><a href="mailto:info@fahrschule-edi.de">info@fahrschule-edi.de</a></li>
					</ul>
				</div>
			</div>
			<div class="fs-footer__bottom">
				<div class="fs-footer__badges">
					<span style="display:inline-flex;align-items:center;gap:.5rem;"><img src="<?php echo esc_url( fs_media( 'logo_certqua' ) ); ?>" alt="CERTQUA-Zertifizierung" width="80" height="32" loading="lazy" decoding="async"><span style="font-size:.72rem;">Zertifiziert</span></span>
					<?php if ( fs_has_media( 'logo_schalke' ) ) : ?>
					<span class="fs-accent">•</span>
					<span style="display:inline-flex;align-items:center;"><img src="<?php echo esc_url( fs_media( 'logo_schalke' ) ); ?>" alt="" width="32" height="32" loading="lazy" decoding="async"></span>
					<?php endif; ?>
					<span class="fs-accent">•</span>
					<span style="font-size:.72rem;">30+ Jahre Erfahrung</span>
				</div>
				<p class="fs-footer__legal">
					<a href="<?php echo esc_url( fs_url( 'impressum' ) ); ?>">Impressum</a>
					<span aria-hidden="true">·</span>
					<a href="<?php echo esc_url( fs_url( 'datenschutz' ) ); ?>">Datenschutz</a>
					<span aria-hidden="true">·</span>
					<a href="<?php echo esc_url( fs_url( 'ueber-uns' ) ); ?>">Über uns</a>
					<span aria-hidden="true">·</span>
					<button type="button" class="fs-footer__consent" data-fs-consent-reopen>Cookie-Einstellungen</button>
				</p>
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Fahrschule &amp; Fahrlehrerfachschule edi GmbH. Alle Rechte vorbehalten.</p>
			</div>
		</div>
	</footer>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * COOKIE-BANNER (DSGVO/TTDSG)
 *
 * Absichtlich schlicht gehalten: nur „Notwendig" und „Alle akzeptieren" statt
 * einer Kategorie-Liste mit Reglern, die für nicht vorhandene Kategorien
 * nichts bewirken würden. Steuert konkret: den nicht-cookielosen Matomo-Modus
 * (siehe edi-integrationen/inc/matomo.php) und - sobald hinzugefügt - jeden
 * künftigen Cookie setzenden Fremd-Embed (z. B. eine echte TikTok-/Google-
 * Maps-Einbettung statt des reinen Links unten). Der cookielose Matomo-Modus
 * (Standard) bleibt bewusst ungegatet, siehe die rechtliche Begründung im
 * companion-plugin.
 *
 * Wird IMMER gerendert (auch nach einer bereits getroffenen Wahl), damit der
 * Footer-Link „Cookie-Einstellungen" etwas zum Wiederöffnen hat - ob es beim
 * ersten Aufruf sichtbar ist, entscheidet allein das serverseitig gelesene
 * fs_consent-Cookie über das hidden-Attribut.
 * ---------------------------------------------------------------------- */
function fs_render_cookie_banner( $a ) {
	$choice = isset( $_COOKIE['fs_consent'] ) ? sanitize_key( wp_unslash( $_COOKIE['fs_consent'] ) ) : '';
	$decided = in_array( $choice, array( 'all', 'essential' ), true );
	ob_start(); ?>
	<div class="fs-cookiebanner<?php echo $decided ? '' : ' is-open'; ?>" data-fs-cookiebanner role="dialog" aria-modal="false" aria-label="Cookie-Einstellungen"<?php echo $decided ? ' hidden' : ''; ?>>
		<div class="fs-cookiebanner__inner">
			<p class="fs-cookiebanner__text">
				Diese Website verwendet nur technisch notwendige Cookies. Mit „Alle akzeptieren" erlauben Sie zusätzlich freiwillige Cookies (z.&nbsp;B. für Statistik oder eingebettete Inhalte), sofern diese eingerichtet sind. Mehr dazu in unserer <a href="<?php echo esc_url( fs_url( 'datenschutz' ) ); ?>">Datenschutzerklärung</a>.
			</p>
			<div class="fs-cookiebanner__actions">
				<button type="button" class="fs-btn fs-btn--outline" data-fs-consent="essential">Nur notwendige</button>
				<button type="button" class="fs-btn fs-btn--primary" data-fs-consent="all">Alle akzeptieren</button>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * TIKTOK BADGE
 * ---------------------------------------------------------------------- */
function fs_render_tiktok_badge( $a ) {
	$d = fs_attrs( $a, array(
		'url'   => 'https://www.tiktok.com/@edi_fahrschule',
		'line1' => 'Besuchen Sie uns',
		'line2' => 'auf TikTok',
	) );
	ob_start(); ?>
	<div class="fs-tiktok" data-fs-tiktok>
		<a class="fs-tiktok__link" href="<?php echo esc_url( $d['url'] ); ?>" target="_blank" rel="noopener noreferrer">
			<?php echo fs_icon( 'tiktok', 24 ); ?>
			<span><?php echo fs_el( 'span', $d['line1'], 'class="t1"' ); ?><?php echo fs_el( 'span', $d['line2'], 'class="t2"' ); ?></span>
			<button type="button" class="fs-tiktok__close" data-fs-tiktok-close aria-label="Schließen"><?php echo fs_icon( 'x', 14 ); ?></button>
		</a>
	</div>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 * TEIL 2: Unterseiten (Über uns, Testimonials, Standorte) & PDF-Custom-Blöcke
 * ====================================================================== */

/* -------------------------------------------------------------------------
 * ÜBER UNS – Intro (PDF: "Ihre Fahrschule im Ruhrgebiet")
 * ---------------------------------------------------------------------- */
function fs_render_about_intro( $a ) {
	$f2 = fahrschule_pdf( 'f2' );
	$d  = fs_attrs( $a, array(
		'eyebrow'   => $f2['label'],
		'heading'   => $f2['headline'],
		'highlight' => $f2['highlight'],
		'p1'        => $f2['paragraphs'][0],
		'p2'        => $f2['paragraphs'][1],
		'p3'        => $f2['paragraphs'][2],
		'head'      => '1',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand" data-pdf-section="F2">
		<div class="fs-container fs-z">
			<?php if ( fahrschule_pdf_show_head( $d ) ) : ?>
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ) . fs_el( 'h2', '', '', $heading ), 'class="fs-head"' ); ?>
			<?php endif; ?>
			<div class="fs-rich">
				<?php echo fs_el( 'p', $d['p1'], 'class="lead"' ); ?>
				<?php echo fs_el( 'p', $d['p2'] ); ?>
				<?php echo fs_el( 'p', $d['p3'] ); ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * ÜBER UNS – Timeline
 * ---------------------------------------------------------------------- */
function fs_render_about_timeline( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Tradition trifft Moderne',
		'highlight' => 'Moderne',
		'subtitle'  => 'Über 30 Jahre Erfahrung in der Fahrerausbildung im Ruhrgebiet',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$items = fs_rows( $d['items'], 3 );
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-z">
			<div class="fs-head" style="margin-bottom:5rem;"><?php echo fs_el( 'h2', '', '', $heading ); ?><?php echo fs_el( 'p', $d['subtitle'], 'style="color:var(--c-text);font-size:1.25rem;max-width:42rem;margin:1rem auto 0;"' ); ?></div>
			<?php if ( $items ) : ?>
			<div class="fs-timeline">
				<?php foreach ( $items as $it ) : ?>
				<div class="fs-timeline__item">
					<div class="fs-timeline__dot"></div>
					<div>
						<div class="fs-timeline__year"><?php echo esc_html( $it[0] ); ?></div>
						<h3><?php echo esc_html( $it[1] ); ?></h3>
						<p><?php echo esc_html( $it[2] ); ?></p>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * ÜBER UNS – Philosophie
 * ---------------------------------------------------------------------- */
function fs_render_about_philosophy( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Unsere Philosophie',
		'highlight' => 'Philosophie',
		'subtitle'  => 'Mit Kompetenz und Betreuung bis zum Führerschein',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$items = fs_rows( $d['items'], 3 );
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'h2', '', '', $heading ) . fs_el( 'p', $d['subtitle'], 'style="color:var(--c-text);font-size:1.25rem;"' ), 'class="fs-head"' ); ?>
			<?php if ( $items ) : ?>
			<div class="fs-grid fs-grid--3" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $items as $it ) : ?>
				<div class="fs-philo"><?php echo fs_icon( $it[0], 40 ); ?><h3><?php echo esc_html( $it[1] ); ?></h3><p><?php echo esc_html( $it[2] ); ?></p></div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * ÜBER UNS / TEAM – PDF: "Persönlich. Geduldig. Erfahren."
 * ---------------------------------------------------------------------- */
function fs_render_about_team( $a ) {
	$f9 = fahrschule_pdf( 'f9' );
	$d  = fs_attrs( $a, array(
		'eyebrow' => $f9['label'],
		'heading' => $f9['headline'],
		'p1'      => $f9['paragraphs'][0],
		'p2'      => $f9['paragraphs'][1],
		'footer'  => $f9['closing'],
		'head'    => '1',
	) );
	/*
	 * Die drei Gruppen aus dem PDF (Büro & Ansprechpartner, Geschäftsleitung,
	 * Dozenten & Ausbilder) zeigt der Fahrlehrer-Finder mit den echten
	 * Personen - hier stand bis 2.x je Gruppe eine Platzhalterkachel.
	 */
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-pdf-section="F9">
		<div class="fs-container">
			<?php if ( fahrschule_pdf_show_head( $d ) ) : ?>
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ) . fs_el( 'h2', $d['heading'] ), 'class="fs-head"' ); ?>
			<?php endif; ?>
			<div class="fs-rich fs-center">
				<?php echo fs_el( 'p', $d['p1'], 'class="lead"' ); ?>
				<?php echo fs_el( 'p', $d['p2'] ); ?>
			</div>
			<?php echo fs_el( 'p', $d['footer'], 'class="fs-center fs-mt-8" style="color:var(--c-heading);font-weight:600;max-width:42rem;margin-inline:auto;"' ); ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * TESTIMONIALS – Hero-Counter
 * ---------------------------------------------------------------------- */
function fs_render_testimonials_hero( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Über 30 Jahre Erfahrung im Ruhrgebiet',
		'highlight' => '30 Jahre Erfahrung',
		'subtitle'  => 'Seit 1993 begleiten wir Fahrschülerinnen und Fahrschüler in Duisburg, Essen und Gelsenkirchen zum Führerschein.',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-center">
			<?php echo fs_el( 'h1', '', 'style="font-size:clamp(2.75rem,8vw,4.5rem);font-weight:800;color:var(--c-heading);"', $heading ); ?>
			<?php echo fs_el( 'p', $d['subtitle'], 'style="color:var(--c-text);font-size:1.25rem;max-width:42rem;margin:1rem auto 0;"' ); ?>
			<a class="fs-btn fs-btn--lg fs-btn--outline fs-mt-4" href="https://www.google.com/search?q=Fahrschule+edi+Duisburg+Bewertungen" target="_blank" rel="noopener noreferrer">Echte Bewertungen auf Google ansehen <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * TESTIMONIALS – Absolventen-Galerie
 * ---------------------------------------------------------------------- */
function fs_render_graduates_gallery( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Unsere Absolventen im Überblick',
		'highlight' => 'Absolventen',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$items = array(
		array( fs_media( 'grad_auto' ), 'Mit dem Führerschein zum Erfolg' ),
		array( fs_media( 'grad_jung' ), 'Gemeinsam zum Ziel' ),
		array( fs_media( 'grad_motorrad' ), 'Auch Motorrad erfolgreich bestanden' ),
	);
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'h2', '', '', $heading ), 'class="fs-head"' ); ?>
			<div class="fs-grid fs-grid--3" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $items as $it ) : ?>
				<div class="fs-locimg" style="aspect-ratio:1/1;height:auto;"><img src="<?php echo esc_url( $it[0] ); ?>" alt="<?php echo esc_attr( $it[1] ); ?> – Absolvent der Fahrschule edi" width="500" height="500" loading="lazy" decoding="async"><div class="fs-locimg__cap"><h3 style="font-size:1.25rem;"><?php echo esc_html( $it[1] ); ?></h3></div></div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * TESTIMONIALS – ausführliches Raster (mit Klasse)
 * ---------------------------------------------------------------------- */
function fs_render_testimonials_detailed( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Kundenstimmen & Bewertungen',
		'highlight' => 'Bewertungen',
		'reviews_url' => 'https://www.google.com/search?q=Fahrschule+edi+Duisburg+Bewertungen',
	) );
	/*
	 * Bewusst leer: Hier standen zuvor sechs erfundene Namen und Zitate
	 * ("Sarah M.", "Marco K." usw. – keine dieser Personen existiert).
	 * Erfundene Kundenstimmen wären schlimmer als keine (vgl. das
	 * einem Schwesterprojekt). Die Sektion blendet sich
	 * automatisch aus, solange diese Liste leer ist – sobald echte, mit
	 * Einwilligung veröffentlichte Zitate vorliegen, hier eintragen:
	 * array( 'Vorname N.', 'Klasse/Kontext', 'Zitat' ).
	 */
	$t = array();

	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'h2', '', '', $heading ), 'class="fs-head"' ); ?>
			<?php if ( ! $t ) : ?>
			<div class="fs-cta-band">
				<h3>Echte Bewertungen statt erfundener Zitate</h3>
				<p>Die Erfahrungsberichte unserer Fahrschülerinnen und Fahrschüler stehen öffentlich bei Google – ungefiltert und mit Namen.</p>
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( $d['reviews_url'] ); ?>" target="_blank" rel="noopener noreferrer">Alle Bewertungen auf Google lesen <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
			</div>
			<?php else : ?>
			<div class="fs-grid fs-grid--2" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $t as $item ) : ?>
				<div class="fs-card fs-card--glass"><div class="fs-card__body">
					<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
						<div><p class="fs-testi-name" style="font-size:1.125rem;"><?php echo esc_html( $item[0] ); ?></p><p style="color:var(--c-accent);font-size:.875rem;"><?php echo esc_html( $item[1] ); ?></p></div>
						<div class="fs-stars"><?php for ( $i = 0; $i < 5; $i++ ) { echo fs_icon( 'star', 16 ); } ?></div>
					</div>
					<p class="fs-testi"><?php echo esc_html( $item[2] ); ?></p>
				</div></div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * STANDORT-HERO (generisch, je Stadt konfigurierbar)
 * ---------------------------------------------------------------------- */
function fs_render_location_hero( $a ) {
	$d = fs_attrs( $a, array(
		'city'     => 'Duisburg',
		'icon'     => 'shield',
		'badge'    => 'Hafen & Logistik',
		'heading'  => 'Sicher fahren in Duisburg. Sicher fahren im Hafen. Sicher fahren bei edi.',
		'lead'     => 'Spezialisiert auf Sicherheitstraining und Fahrpraxis im dichten Stadtverkehr. Ideal für logistische Anforderungen im Duisburger Hafen.',
		'address'  => '',
		'zip'      => '',
		'phone'    => '',
		'email'    => '',
		'hours'    => '',
		'contact'  => '',
	) );
	// Leere Kontaktfelder = Stammdaten der Stadt (inc/pdf-texts.php,
	// Öffnungszeiten aus fahrschule_location_hours_text()). So steht keine
	// Kopie von Adresse, Telefon oder Zeiten im Seiteninhalt.
	$nap = fahrschule_pdf_location( $d['city'] );
	foreach ( array( 'address' => 'street', 'zip' => 'zip', 'phone' => 'phone', 'email' => 'email' ) as $k => $src ) {
		if ( '' === trim( (string) $d[ $k ] ) && ! empty( $nap[ $src ] ) ) { $d[ $k ] = $nap[ $src ]; }
	}
	if ( '' === trim( (string) $d['hours'] ) ) {
		$d['hours'] = str_replace( "\n", ', ', fahrschule_location_hours_text( $d['city'] ) );
	}
	$herocls = 'fs-lochero fs-surface-brand';
	ob_start(); ?>
	<section class="<?php echo esc_attr( $herocls ); ?>">
		<div class="fs-container fs-lochero__inner">
			<div class="fs-lochero__grid">
				<div class="fs-stack" style="gap:1.5rem;">
					<span class="fs-badge"><?php echo fs_icon( $d['icon'], 16 ); ?> <?php echo esc_html( $d['badge'] ); ?></span>
					<?php echo fs_el( 'h1', $d['heading'] ); ?>
					<?php echo fs_el( 'p', $d['lead'], 'class="lead"' ); ?>
					<?php if ( fs_has( $d, 'address' ) || fs_has( $d, 'zip' ) ) : ?>
					<div class="fs-loc__line"><?php echo fs_icon( 'map-pin', 20 ); ?><div><?php echo fs_el( 'p', $d['address'], 'style="font-weight:600;color:var(--c-heading);margin:0;"' ); ?><?php echo fs_el( 'p', $d['zip'], 'style="margin:0;"' ); ?></div></div>
					<?php endif; ?>
					<?php if ( fs_has( $d, 'phone' ) ) : ?>
					<div class="fs-loc__line"><?php echo fs_icon( 'phone', 20 ); ?><a href="tel:<?php echo esc_attr( fs_tel( $d['phone'] ) ); ?>" style="color:var(--c-heading);"><?php echo esc_html( $d['phone'] ); ?></a></div>
					<?php endif; ?>
					<?php if ( fs_has( $d, 'email' ) ) : ?>
					<div class="fs-loc__line"><?php echo fs_icon( 'mail', 20 ); ?><a href="mailto:<?php echo esc_attr( $d['email'] ); ?>" style="color:var(--c-heading);"><?php echo esc_html( $d['email'] ); ?></a></div>
					<?php endif; ?>
					<?php if ( $d['hours'] ) : ?>
					<div class="fs-loc__line"><?php echo fs_icon( 'clock', 20 ); ?><?php echo fs_el( 'p', $d['hours'], 'style="margin:0;"' ); ?></div>
					<?php endif; ?>
					<?php if ( $d['contact'] ) : ?>
					<div class="fs-loc__line"><?php echo fs_icon( 'users', 20 ); ?><p style="margin:0;">Ansprechpartner vor Ort: <strong style="color:var(--c-heading);"><?php echo esc_html( $d['contact'] ); ?></strong></p></div>
					<?php endif; ?>
					<div style="display:flex;gap:1rem;flex-wrap:wrap;padding-top:.5rem;">
						<a class="fs-btn fs-btn--lg fs-btn--primary-white" href="<?php echo esc_url( fs_url( 'anmeldung' ) ); ?>">Jetzt anmelden</a>
						<a class="fs-btn fs-btn--lg fs-btn--outline" href="<?php echo esc_url( fs_url( 'kontakt' ) ); ?>">Kontakt</a>
					</div>
				</div>
				<div class="fs-locform">
					<h2>Schnelle Anmeldung</h2>
					<?php echo fs_formular( 'schnellanmeldung', array( 'standort' => $d['city'] ) ); ?>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * STANDORT-INHALT (Bilder + Distanzrechner + lokale Bewertungen)
 * ---------------------------------------------------------------------- */
function fs_render_location_content( $a ) {
	$d = fs_attrs( $a, array(
		'city'    => 'Duisburg',
		'img1'    => '',
		'img1_t'  => 'Moderne Fahrschule',
		'img1_s'  => 'Standort mit Schulungsfahrzeugen',
		'img2'    => fs_media( 'fac_empfang' ),
		'img2_t'  => 'Willkommensbereich',
		'img2_s'  => 'Moderner Empfangsbereich & Schulungsräume',
	) );
	/*
	 * Bewusst leer: Hier standen zuvor vier erfundene Namen und Zitate,
	 * identisch für alle drei Standorte nur mit anderem Stadtteil-Zusatz
	 * ("Marcus K." usw. – keine dieser Personen existiert). Erfundene
	 * Kundenstimmen wären schlimmer als keine. Der Abschnitt „Bewertungen
	 * aus {$d['city']}" blendet sich unten automatisch aus, solange diese
	 * Liste leer ist – sobald echte, mit Einwilligung veröffentlichte
	 * Zitate je Standort vorliegen, hier eintragen:
	 * array( 'Vorname N.', 'Stadtteil', 'Zitat' ).
	 */
	$testi = array();
	// Leeres Bild 1 = Standortbild der Stadt aus der Mediathek (edi-standort-<stadt>).
	if ( '' === trim( (string) $d['img1'] ) ) { $d['img1'] = fs_media( 'loc_' . sanitize_key( $d['city'] ) ); }
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<div style="margin-bottom:5rem;">
				<div class="fs-head"><h2>Unser <span class="fs-accent">Standort in <?php echo esc_html( $d['city'] ); ?></span></h2></div>
				<div class="fs-grid fs-grid--2" style="max-width:64rem;margin-inline:auto;">
					<div class="fs-locimg"><img src="<?php echo esc_url( $d['img1'] ); ?>" alt="<?php echo esc_attr( $d['img1_t'] ); ?> – Fahrschule edi <?php echo esc_attr( $d['city'] ); ?>" width="800" height="640" loading="lazy" decoding="async"><div class="fs-locimg__cap"><?php echo fs_el( 'h3', $d['img1_t'] ); ?><?php echo fs_el( 'p', $d['img1_s'] ); ?></div></div>
					<div class="fs-locimg"><img src="<?php echo esc_url( $d['img2'] ); ?>" alt="<?php echo esc_attr( $d['img2_t'] ); ?> – Fahrschule edi <?php echo esc_attr( $d['city'] ); ?>" width="800" height="640" loading="lazy" decoding="async"><div class="fs-locimg__cap"><?php echo fs_el( 'h3', $d['img2_t'] ); ?><?php echo fs_el( 'p', $d['img2_s'] ); ?></div></div>
				</div>
			</div>
			<div style="margin-bottom:5rem;">
				<div class="fs-head"><h2><?php echo fs_icon( 'navigation', 32, 'fs-icon-pink' ); ?> Wie nah wohnen Sie zu uns?</h2></div>
				<div class="fs-distance" data-fs-distance>
					<label style="display:flex;justify-content:space-between;color:var(--c-heading);font-weight:600;margin-bottom:1rem;">Entfernung: <span class="fs-accent" data-fs-dist-val>5 km</span></label>
					<input type="range" min="0" max="50" value="5" class="fs-range" data-fs-dist-range>
					<div class="fs-distance__out"><p>Fahrtzeit mit dem Auto:</p><div class="big" data-fs-dist-out>15 Minuten</div><p>Durchschnittlich im Stadtverkehr</p></div>
				</div>
			</div>
			<?php if ( $testi ) : ?>
			<div>
				<div class="fs-head"><h2>Bewertungen aus <?php echo fs_el( 'span', $d['city'], 'class="fs-accent"' ); ?></h2></div>
				<div class="fs-grid fs-grid--2" style="max-width:56rem;margin-inline:auto;">
					<?php foreach ( $testi as $t ) : ?>
					<div class="fs-darktesti">
						<div class="fs-stars"><?php for ( $i = 0; $i < 5; $i++ ) { echo fs_icon( 'star', 16 ); } ?></div>
						<p style="font-style:italic;margin-bottom:1rem;"><?php echo esc_html( $t[2] ); ?></p>
						<p class="name"><?php echo esc_html( $t[0] ); ?></p>
						<p style="color:var(--c-text);font-size:.875rem;"><?php echo esc_html( $t[1] ); ?></p>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * STANDORT-RECHNER (Slider-basiert)
 * ---------------------------------------------------------------------- */
function fs_render_location_calculator( $a ) {
	$prices = wp_json_encode( array(
		'b' => array( 'basic' => 65, 'intensive' => 75 ),
		'a' => array( 'basic' => 70, 'intensive' => 80 ),
		'c' => array( 'basic' => 85, 'intensive' => 100 ),
		'd' => array( 'basic' => 90, 'intensive' => 110 ),
	) );
	ob_start(); ?>
	<section class="fs-loccalc fs-surface-brand" data-fs-loccalc data-prices='<?php echo esc_attr( $prices ); ?>'>
		<div class="fs-container">
			<h2>Führerschein-<span class="fs-accent">Rechner</span></h2>
			<p class="fs-loccalc__lead">Berechnen Sie Ihre individuellen Kosten basierend auf Ihren Bedürfnissen</p>
			<div class="fs-loccalc__grid">
				<div class="fs-stack" style="gap:2rem;">
					<div>
						<label>Führerscheinklasse</label>
						<div class="fs-pillgrid" data-fs-group="class">
							<?php foreach ( array( 'b', 'a', 'c', 'd' ) as $i => $k ) : ?>
							<button type="button" class="fs-pill<?php echo 0 === $i ? ' is-active' : ''; ?>" data-val="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( strtoupper( $k ) ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>
					<div>
						<label>Ausbildungstyp</label>
						<div class="fs-pillgrid" data-fs-group="type">
							<button type="button" class="fs-pill is-active" data-val="basic">Basis</button>
							<button type="button" class="fs-pill" data-val="intensive">Intensiv</button>
						</div>
					</div>
					<div>
						<label>Fahrstunden: <span class="fs-accent" data-fs-driving-val>40</span></label>
						<input type="range" min="10" max="60" value="40" class="fs-range" data-fs-driving>
					</div>
					<div>
						<label>Theoriestunden: <span class="fs-accent" data-fs-theory-val>14</span></label>
						<input type="range" min="0" max="30" value="14" class="fs-range" data-fs-theory>
					</div>
				</div>
				<div style="display:flex;align-items:center;">
					<div class="fs-loccalc__total" style="width:100%;">
						<p style="color:var(--c-text);margin-bottom:.5rem;">Geschätzte Gesamtkosten</p>
						<div class="big" data-fs-loctotal>€2.810</div>
						<p style="color:var(--c-text);margin-top:.5rem;font-size:.85rem;">inkl. Theorie (15&nbsp;€/Std.) – Richtpreis ohne Gewähr</p>
						<a class="fs-btn fs-btn--lg fs-btn--primary fs-btn--block fs-mt-6" href="<?php echo esc_url( fs_url( 'anmeldung' ) ); ?>">Jetzt anmelden</a>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 * PDF-CUSTOM-BLÖCKE
 * ====================================================================== */

/* Vollständige Führerscheinklassen-Übersicht (PDF S.2) */
function fs_render_license_overview( $a ) {
	$f3 = fahrschule_pdf( 'f3' );
	$d  = fs_attrs( $a, array(
		'eyebrow'  => $f3['label'],
		'heading'  => $f3['headline'],
		'subtitle' => $f3['paragraphs'][0],
		'head'     => '1',
	) );
	$hx = fahrschule_pdf_show_head( $d ) ? 'h3' : 'h2'; // Gliederung ohne Sprung h1 -> h3
	ob_start(); ?>
	<section class="fs-section fs-surface-brand" data-pdf-section="F3">
		<div class="fs-container fs-z">
			<div class="fs-head">
				<?php if ( fahrschule_pdf_show_head( $d ) ) : ?><?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?><?php echo fs_el( 'h2', $d['heading'] ); ?><?php endif; ?>
				<?php echo fs_el( 'p', $d['subtitle'] ); ?>
			</div>
			<div class="fs-classlist">
				<?php foreach ( $f3['groups'] as $cat => $classes ) : ?>
				<div class="fs-classlist__cat">
					<<?php echo $hx; ?>><?php echo esc_html( $cat ); ?></<?php echo $hx; ?>>
					<ul><?php foreach ( $classes as $cls ) : ?><li><?php echo esc_html( $cls ); ?></li><?php endforeach; ?></ul>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* Unsere Leistungen (PDF S.3) */
function fs_render_services( $a ) {
	$f4 = fahrschule_pdf( 'f4' );
	$d  = fs_attrs( $a, array(
		'eyebrow'    => $f4['label'],
		'heading'    => $f4['headline'],
		'subtitle'   => $f4['paragraphs'][0],
		'list_intro' => $f4['list_intro'],
		'footer'     => $f4['closing'],
		'head'       => '1',
	) );
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-pdf-section="F4">
		<div class="fs-container fs-z">
			<div class="fs-head">
				<?php if ( fahrschule_pdf_show_head( $d ) ) : ?><?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?><?php echo fs_el( 'h2', $d['heading'] ); ?><?php endif; ?>
				<?php echo fs_el( 'p', $d['subtitle'] ); ?>
			</div>
			<?php if ( $d['list_intro'] ) : ?><?php echo fs_el( 'p', $d['list_intro'], 'class="fs-listintro"' ); ?><?php endif; ?>
			<div class="fs-features">
				<?php foreach ( $f4['items'] as $it ) : ?>
				<div class="fs-feature"><?php echo fs_icon( 'check-circle', 22 ); ?><span><?php echo esc_html( $it ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<?php echo fs_el( 'p', $d['footer'], 'class="fs-center fs-mt-8" style="color:var(--c-text);max-width:42rem;margin-inline:auto;"' ); ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * THEORIEUNTERRICHT – Wochentermine je Standort-Gruppe
 * Auf der alten Website ein eigener Hauptnavigationspunkt (eigene Zeiten je
 * Standort, inkl. türkischsprachigem Termin in Gelsenkirchen) - in der neuen
 * Struktur als Unterseite unter „Ausbildung" eingeordnet, URL-Segment
 * unverändert übernommen (/theorieunterricht/).
 * ---------------------------------------------------------------------- */
function fs_render_theory_schedule( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow' => 'Theorieunterricht',
		'heading' => 'Unsere Theorie-Termine',
		'lead'    => 'Der Theorieunterricht findet wöchentlich an festen Terminen statt – wählen Sie den Standort, der für Sie am besten passt.',
	) );
	$groups = array(
		array(
			'title' => 'Gelsenkirchen',
			'rows'  => array(
				array( 'Montag', '16:00 – 17:30 und 18:30 – 20:00 Uhr' ),
				array( 'Dienstag', '16:00 – 17:30 und 18:30 – 20:00 Uhr' ),
				array( 'Mittwoch', '16:00 – 17:30 und 18:30 – 20:00 Uhr' ),
				array( 'Donnerstag', '16:00 – 17:30 und 18:30 – 20:00 Uhr' ),
			),
			'note'  => 'Dienstags und donnerstags um 18:30 Uhr findet der Unterricht auch in türkischer Sprache statt.',
		),
		array(
			'title' => 'Essen und Duisburg',
			'rows'  => array(
				array( 'Montag', '18:30 – 20:00 Uhr' ),
				array( 'Dienstag', '18:30 – 20:00 Uhr' ),
				array( 'Mittwoch', '18:30 – 20:00 Uhr' ),
				array( 'Donnerstag', '18:30 – 20:00 Uhr' ),
			),
			'note'  => '',
		),
	);
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['lead'] ), 'class="fs-head"' ); ?>
			<div class="fs-grid fs-grid--2" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $groups as $g ) : ?>
				<div class="fs-card"><div class="fs-card__body">
					<h3 style="margin-bottom:1rem;color:var(--c-card-heading);"><?php echo esc_html( $g['title'] ); ?></h3>
					<div class="fs-stack" style="gap:.5rem;">
						<?php foreach ( $g['rows'] as $row ) : ?>
						<div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--c-card-border);padding-bottom:.5rem;">
							<span style="font-weight:600;"><?php echo esc_html( $row[0] ); ?></span>
							<span><?php echo esc_html( $row[1] ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<?php if ( $g['note'] ) : ?>
					<p style="margin-top:1rem;font-size:.875rem;color:var(--c-card-text);"><?php echo esc_html( $g['note'] ); ?></p>
					<?php endif; ?>
				</div></div>
				<?php endforeach; ?>
			</div>
			<div class="fs-center fs-mt-8">
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( fs_url( 'anmeldung' ) ); ?>">Jetzt anmelden <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* Berufskraftfahrer (PDF S.3) – CUSTOM */
function fs_render_professional_drivers( $a ) {
	$f5 = fahrschule_pdf( 'f5' );
	$d  = fs_attrs( $a, array(
		'eyebrow'    => $f5['label'],
		'heading'    => $f5['headline'],
		'p1'         => $f5['paragraphs'][0],
		'p2'         => $f5['paragraphs'][1],
		'list_intro' => $f5['list_intro'],
		'footer'     => $f5['closing'],
		'head'       => '1',
	) );
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-pdf-section="F5">
		<div class="fs-container">
			<?php if ( fahrschule_pdf_show_head( $d ) ) : ?>
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ) . fs_el( 'h2', $d['heading'], 'style="color:var(--c-heading);"' ), 'class="fs-head"' ); ?>
			<?php endif; ?>
			<div class="fs-rich fs-center">
				<?php echo fs_el( 'p', $d['p1'] ); ?>
				<?php echo fs_el( 'p', $d['p2'] ); ?>
			</div>
			<?php if ( $d['list_intro'] ) : ?><?php echo fs_el( 'p', $d['list_intro'], 'class="fs-listintro fs-mt-8"' ); ?><?php endif; ?>
			<div class="fs-features">
				<?php foreach ( $f5['items'] as $it ) : ?>
				<div class="fs-feature"><?php echo fs_icon( 'truck', 22 ); ?><span style="color:var(--c-heading);"><?php echo esc_html( $it ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<?php echo fs_el( 'p', $d['footer'], 'class="fs-center fs-mt-8" style="color:var(--c-text);max-width:42rem;margin-inline:auto;"' ); ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* ASF & FES (PDF S.4) – CUSTOM */
function fs_render_seminars( $a ) {
	$f6 = fahrschule_pdf( 'f6' );
	$d  = fs_attrs( $a, array(
		'eyebrow'    => $f6['label'],
		'heading'    => $f6['headline'],
		'subtitle'   => $f6['paragraphs'][0],
		'list_intro' => $f6['list_intro'],
		'footer'     => $f6['closing'],
		'head'       => '1',
	) );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand" data-pdf-section="F6">
		<div class="fs-container fs-z">
			<div class="fs-head">
				<?php if ( fahrschule_pdf_show_head( $d ) ) : ?><?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?><?php echo fs_el( 'h2', $d['heading'] ); ?><?php endif; ?>
				<?php echo fs_el( 'p', $d['subtitle'] ); ?>
			</div>
			<?php if ( $d['list_intro'] ) : ?><?php echo fs_el( 'p', $d['list_intro'], 'class="fs-listintro"' ); ?><?php endif; ?>
			<div class="fs-features">
				<?php foreach ( $f6['items'] as $it ) : ?>
				<div class="fs-feature"><?php echo fs_icon( 'check-circle', 22 ); ?><span><?php echo esc_html( $it ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<?php echo fs_el( 'p', $d['footer'], 'class="fs-center fs-mt-8" style="color:var(--c-heading);font-weight:600;max-width:42rem;margin-inline:auto;"' ); ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* Moderne Ausbildung (PDF S.4) – CUSTOM */
function fs_render_modern_training( $a ) {
	$f7 = fahrschule_pdf( 'f7' );
	$d  = fs_attrs( $a, array(
		'eyebrow'   => $f7['label'],
		'heading'   => $f7['headline'],
		'highlight' => $f7['highlight'],
		'p1'        => $f7['paragraphs'][0],
		'p2'        => $f7['paragraphs'][1],
		'p3'        => $f7['paragraphs'][2],
		'footer'    => $f7['paragraphs'][3],
		'head'      => '1',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-pdf-section="F7">
		<div class="fs-container">
			<?php if ( fahrschule_pdf_show_head( $d ) ) : ?>
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ) . fs_el( 'h2', '', '', $heading ), 'class="fs-head"' ); ?>
			<?php endif; ?>
			<div class="fs-rich fs-center">
				<?php echo fs_el( 'p', $d['p1'], 'style="color:var(--c-heading);font-weight:600;font-size:1.2rem;"' ); ?>
				<?php echo fs_el( 'p', $d['p2'] ); ?>
				<?php echo fs_el( 'p', $d['p3'] ); ?>
			</div>
			<?php echo fs_el( 'p', $d['footer'], 'class="fs-center fs-mt-8" style="color:var(--c-heading);font-weight:600;max-width:42rem;margin-inline:auto;"' ); ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* Anmeldung (PDF S.6) – CUSTOM CTA */
function fs_render_anmeldung( $a ) {
	$f10 = fahrschule_pdf( 'f10' );
	$d   = fs_attrs( $a, array(
		'eyebrow'    => $f10['label'],
		'heading'    => $f10['headline'],
		'p1'         => $f10['paragraphs'][0],
		'p2'         => $f10['paragraphs'][1],
		'p3'         => $f10['paragraphs'][2],
		'link_intro' => $f10['link_intro'],
		'btn_label'  => $f10['link_label'],
		'btn_url'    => '@anmeldung',
		'head'       => '1',
	) );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand" data-pdf-section="F10">
		<div class="fs-container fs-z">
			<div class="fs-anmeldung">
				<?php echo fs_icon( 'graduation', 48, 'fs-icon-pink' ); ?>
				<?php if ( fahrschule_pdf_show_head( $d ) ) : ?>
				<?php echo fs_el( 'p', $d['eyebrow'], 'class="fs-eyebrow fs-mt-4" style="margin-bottom:0;"' ); ?>
				<?php echo fs_el( 'h2', $d['heading'], 'class="fs-mt-4"' ); ?>
				<?php endif; ?>
				<div class="fs-anmeldung__text">
					<?php echo fs_el( 'p', $d['p1'] ); ?>
					<?php echo fs_el( 'p', $d['p2'] ); ?>
					<?php echo fs_el( 'p', $d['p3'], 'style="color:var(--c-heading);font-weight:600;"' ); ?>
				</div>
				<?php if ( $d['link_intro'] ) : ?><?php echo fs_el( 'p', $d['link_intro'], 'class="fs-anmeldung__label"' ); ?><?php endif; ?>
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( fs_link( $d['btn_url'] ) ); ?>"><?php echo esc_html( $d['btn_label'] ); ?> <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Allgemeines Kontakt-/Anmeldeformular – echter Versand über
 * fahrschule_handle_kontakt() (inc/forms.php). Eingebunden auf /kontakt/ und
 * /anmeldung/ (dieselbe Anfrageart mit optionalem Standort- und
 * Klasseninteresse, siehe Docblock dort) - jeweils mit eigener
 * Rücksprung-URL über das versteckte Feld fs_back.
 */
function fs_render_contact_form( $a ) {
	$d = fs_attrs( $a, array(
		'heading'       => 'Schreiben Sie uns',
		'lead'          => '',
		'art'           => 'kontakt',
		'formShortcode' => '',
	) );
	ob_start(); ?>
	<section class="fs-section fs-surface-light" id="kontaktformular">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['lead'] ), 'class="fs-head"' ); ?>
			<div class="fs-contactform">
				<?php echo fs_formular( $d['art'], array(), $d['formShortcode'] ); ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FORMULARE
 *
 * Ein Weg für alle Formulare der Website (Kontakt, Anmeldung,
 * Schnellanmeldung, Bewerbung):
 *   1. Editor-Vorschau  -> statische, deaktivierte Felder (die Vorschau darf
 *                          nicht von Plugin-JavaScript abhängen)
 *   2. eigener Shortcode im Block-Feld
 *   3. Contact-Form-7-Formular der Art aus dem Begleit-Plugin
 *      edi-integrationen (legt es an, setzt den Empfänger aus den
 *      Stammdaten, Flamingo speichert die Einsendung)
 *   4. ohne CF7: der eigene Handler in inc/forms.php (Rückfall)
 * Im Markup steht nie eine Empfängeradresse.
 * ---------------------------------------------------------------------- */
function fs_formular( $art, $extra = array(), $shortcode = '' ) {
	$art = sanitize_key( $art );
	if ( fs_is_editor_preview() ) {
		return fs_formular_vorschau( $art );
	}
	if ( '' !== trim( (string) $shortcode ) ) {
		return do_shortcode( $shortcode );
	}
	$id = function_exists( 'edi_formular_id' ) ? edi_formular_id( $art ) : 0;
	if ( $id ) {
		$cf  = wpcf7_contact_form( $id );
		$sc  = '[contact-form-7 id="' . esc_attr( method_exists( $cf, 'hash' ) ? $cf->hash() : $id ) . '"';
		foreach ( $extra as $k => $v ) {
			$sc .= ' ' . sanitize_key( $k ) . '="' . esc_attr( str_replace( array( '"', ']' ), '', (string) $v ) ) . '"';
		}
		return do_shortcode( $sc . ']' );
	}
	if ( 'schnellanmeldung' === $art ) {
		return fs_formular_fallback_schnell( isset( $extra['standort'] ) ? $extra['standort'] : '' );
	}
	if ( 'bewerbung' === $art ) {
		// Datei-Uploads gibt es nur über Contact Form 7.
		$mail = function_exists( 'fahrschule_business_data' ) ? fahrschule_business_data()['email'] : '';
		return '<p class="fs-formular-hinweis">Bewerbungen nehmen wir gern per E-Mail entgegen'
			. ( $mail ? ': <a href="mailto:' . esc_attr( $mail ) . '">' . esc_html( $mail ) . '</a>' : '' ) . '.</p>';
	}
	return fs_formular_fallback_kontakt();
}

/** Beide Pflicht-Einwilligungen - wortgleich zu den CF7-Formularen. */
function fs_formular_einwilligungen( $disabled = false ) {
	$dis = $disabled ? ' disabled' : ' required';
	ob_start(); ?>
	<div class="fs-field fs-einwilligung">
		<label><input type="checkbox" name="fs_privacy" value="1"<?php echo $dis; ?>> <span>Ich habe die <a href="<?php echo esc_url( fs_url( 'datenschutz' ) ); ?>">Datenschutzerklärung</a> gelesen und bin mit der Verarbeitung meiner Angaben zur Bearbeitung der Anfrage einverstanden. *</span></label>
	</div>
	<div class="fs-field fs-einwilligung">
		<label><input type="checkbox" name="fs_alter" value="1"<?php echo $dis; ?>> <span>Ich bin mindestens 16 Jahre alt. Bin ich jünger, ist eine erziehungsberechtigte Person mit der Übermittlung einverstanden (Art. 8 DSGVO). *</span></label>
	</div>
	<?php
	return ob_get_clean();
}

/** Editor-Vorschau: zeigt Aufbau und Einwilligungen, sendet nichts. */
function fs_formular_vorschau( $art ) {
	$felder = array( 'Name *', 'E-Mail *', 'Telefon' );
	if ( 'schnellanmeldung' !== $art ) { $felder[] = 'Standort'; }
	if ( in_array( $art, array( 'anmeldung', 'schnellanmeldung' ), true ) ) { $felder[] = 'Führerscheinklasse'; }
	if ( 'bewerbung' === $art ) { $felder[] = 'Lebenslauf (PDF, höchstens 10 MB)'; }
	if ( 'schnellanmeldung' !== $art ) { $felder[] = 'kontakt' === $art ? 'Nachricht *' : 'Nachricht'; }
	ob_start(); ?>
	<p class="fs-formular-hinweis">Vorschau des Formulars „<?php echo esc_html( $art ); ?>“ – das echte Formular (Contact Form 7) erscheint im Frontend.</p>
	<?php foreach ( $felder as $i => $f ) : ?>
	<div class="fs-field"><label for="fs-vorschau-<?php echo (int) $i; ?>"><?php echo esc_html( $f ); ?></label><input id="fs-vorschau-<?php echo (int) $i; ?>" type="text" disabled></div>
	<?php endforeach; ?>
	<?php echo fs_formular_einwilligungen( true ); ?>
	<button type="button" class="fs-btn fs-btn--lg fs-btn--primary fs-btn--block" disabled>Absenden</button>
	<?php
	return ob_get_clean();
}

/** Rückfall ohne CF7: Kontakt/Anmeldung über inc/forms.php. */
function fs_formular_fallback_kontakt() {
	$back  = function_exists( 'fahrschule_current_url' ) ? fahrschule_current_url() : home_url( '/' );
	$state = isset( $_GET['fs_kontakt'] ) ? sanitize_key( wp_unslash( $_GET['fs_kontakt'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur Anzeige
	if ( 'ok' === $state ) {
		return '<p role="status" class="fs-formular-hinweis">Danke für Ihre Nachricht! Wir melden uns kurzfristig bei Ihnen.</p>';
	}
	$orte = function_exists( 'fahrschule_business_data' ) ? array_column( fahrschule_business_data()['locations'], 'city' ) : array();
	ob_start(); ?>
	<?php if ( 'error' === $state ) : ?>
		<p role="alert" class="fs-formular-hinweis">Bitte prüfen Sie Ihre Angaben (Name, gültige E-Mail-Adresse, Nachricht und beide Einwilligungen sind erforderlich) und versuchen Sie es erneut.</p>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="fahrschule_kontakt">
		<input type="hidden" name="fs_back" value="<?php echo esc_url( $back ); ?>">
		<?php wp_nonce_field( 'fahrschule_kontakt' ); ?>
		<span class="fs-hp" aria-hidden="true"><label>Bitte leer lassen<input type="text" name="fs_hp" tabindex="-1" autocomplete="off"></label></span>
		<div class="fs-field"><label for="fs-kontakt-name">Name *</label><input type="text" id="fs-kontakt-name" name="fs_name" autocomplete="name" required></div>
		<div class="fs-field"><label for="fs-kontakt-email">E-Mail *</label><input type="email" id="fs-kontakt-email" name="fs_email" autocomplete="email" required></div>
		<div class="fs-field"><label for="fs-kontakt-phone">Telefon</label><input type="tel" id="fs-kontakt-phone" name="fs_phone" autocomplete="tel"></div>
		<div class="fs-field">
			<label for="fs-kontakt-stadt">Standort</label>
			<select id="fs-kontakt-stadt" name="fs_stadt">
				<option value="">Egal / noch unklar</option>
				<?php foreach ( $orte as $o ) : ?><option value="<?php echo esc_attr( $o ); ?>"><?php echo esc_html( $o ); ?></option><?php endforeach; ?>
			</select>
		</div>
		<div class="fs-field"><label for="fs-kontakt-message">Nachricht *</label><textarea id="fs-kontakt-message" name="fs_message" required></textarea></div>
		<?php echo fs_formular_einwilligungen(); ?>
		<button type="submit" class="fs-btn fs-btn--lg fs-btn--primary fs-btn--block">Nachricht senden</button>
	</form>
	<?php
	return ob_get_clean();
}

/** Rückfall ohne CF7: Schnellanmeldung im Standort-Hero. */
function fs_formular_fallback_schnell( $city ) {
	$state = isset( $_GET['fs_schnell'] ) ? sanitize_key( wp_unslash( $_GET['fs_schnell'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur Anzeige
	if ( 'ok' === $state ) {
		return '<p role="status" class="fs-formular-hinweis">Danke! Wir melden uns kurzfristig bei Ihnen.</p>';
	}
	$id = sanitize_key( $city );
	ob_start(); ?>
	<?php if ( 'error' === $state ) : ?>
		<p role="alert" class="fs-formular-hinweis">Bitte prüfen Sie Ihre Angaben (Name, gültige E-Mail-Adresse und beide Einwilligungen sind erforderlich) und versuchen Sie es erneut.</p>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="fahrschule_schnellanmeldung">
		<input type="hidden" name="fs_stadt" value="<?php echo esc_attr( $city ); ?>">
		<input type="hidden" name="fs_back" value="<?php echo esc_url( fs_url( $id ) ); ?>">
		<?php wp_nonce_field( 'fahrschule_schnellanmeldung' ); ?>
		<span class="fs-hp" aria-hidden="true"><label>Bitte leer lassen<input type="text" name="fs_hp" tabindex="-1" autocomplete="off"></label></span>
		<label class="fs-visually-hidden" for="fs-locform-name-<?php echo esc_attr( $id ); ?>">Name</label>
		<input type="text" id="fs-locform-name-<?php echo esc_attr( $id ); ?>" name="fs_name" placeholder="Name" autocomplete="name" required>
		<label class="fs-visually-hidden" for="fs-locform-email-<?php echo esc_attr( $id ); ?>">E-Mail</label>
		<input type="email" id="fs-locform-email-<?php echo esc_attr( $id ); ?>" name="fs_email" placeholder="E-Mail" autocomplete="email" required>
		<label class="fs-visually-hidden" for="fs-locform-phone-<?php echo esc_attr( $id ); ?>">Telefon</label>
		<input type="tel" id="fs-locform-phone-<?php echo esc_attr( $id ); ?>" name="fs_phone" placeholder="Telefon" autocomplete="tel">
		<label class="fs-visually-hidden" for="fs-locform-klasse-<?php echo esc_attr( $id ); ?>">Führerscheinklasse</label>
		<select id="fs-locform-klasse-<?php echo esc_attr( $id ); ?>" name="fs_klasse">
			<option value="">Führerscheinklasse wählen</option>
			<?php foreach ( fahrschule_pdf( 'f3' )['groups'] as $klassen ) : foreach ( $klassen as $kl ) : ?>
			<option value="<?php echo esc_attr( $kl ); ?>">Klasse <?php echo esc_html( $kl ); ?></option>
			<?php endforeach; endforeach; ?>
		</select>
		<?php echo fs_formular_einwilligungen(); ?>
		<button type="submit" class="fs-btn fs-btn--primary-white fs-btn--block">Kostenloses Beratungsgespräch</button>
	</form>
	<?php
	return ob_get_clean();
}

/* Rechtstext-Block (Impressum / Datenschutz) */
function fs_render_legal( $a ) {
	$d = fs_attrs( $a, array(
		'kind' => 'impressum',
	) );
	ob_start();
	if ( 'datenschutz' === $d['kind'] ) {
		fs_legal_datenschutz();
	} else {
		fs_legal_impressum();
	}
	return ob_get_clean();
}

function fs_legal_impressum() {
	// Stammdaten aus inc/seo.php - keine zweite Kopie von Adresse, Telefon,
	// E-Mail, USt-ID und Geschäftsführung im Impressum.
	$b   = fahrschule_business_data();
	$sitz = $b['locations']['duisburg'];
	?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-legal">
			<div class="fs-head" style="text-align:left;margin-bottom:2rem;"><h2><?php echo fs_icon( 'shield', 32, 'fs-icon-pink' ); ?> Angaben gemäß § 5 TMG</h2></div>
			<div class="fs-legal__card">
				<div class="fs-legal__block">
					<h2>Anbieterangaben</h2>
					<p><strong><?php echo esc_html( $b['legal_name'] ); ?></strong><br><?php echo esc_html( $sitz['street'] ); ?><br><?php echo esc_html( $sitz['zip'] . ' ' . $sitz['city'] ); ?><br>Deutschland</p>
				</div>
				<div class="fs-legal__block">
					<h2>Vertreten durch</h2>
					<p><?php echo esc_html( implode( ', ', $b['management'] ) ); ?> (Geschäftsführung)</p>
				</div>
				<div class="fs-legal__block">
					<h2>Kontaktdaten</h2>
					<p><strong>Telefon:</strong> <a href="tel:<?php echo esc_attr( fs_tel( $b['phone'] ) ); ?>"><?php echo esc_html( $b['phone'] ); ?></a></p>
					<p><strong>E-Mail:</strong> <a href="mailto:<?php echo esc_attr( $b['email'] ); ?>"><?php echo esc_html( $b['email'] ); ?></a></p>
				</div>
				<div class="fs-legal__block">
					<h2>Registereintrag</h2>
					<p><strong>Register:</strong> Handelsregister<br><strong>Register Ort:</strong> Duisburg<br><strong>Registernummer:</strong> HRB-Nr. 7858<br><strong>Umsatzsteuernummer:</strong> <?php echo esc_html( $b['vat'] ); ?></p>
				</div>
				<div class="fs-legal__block">
					<h2>Aufsichtsbehörde</h2>
					<p>Straßenverkehrsamt Duisburg</p>
				</div>
				<div class="fs-legal__block">
					<h2>Haftungsausschluss</h2>
					<h3>Haftung für Links</h3>
					<p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.</p>
					<h3>Haftung für Inhalte</h3>
					<p>Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Wir sind jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen.</p>
					<h3>Urheberrecht</h3>
					<p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Beiträge Dritter sind als solche gekennzeichnet.</p>
				</div>
			</div>
		</div>
	</section>
	<?php
}

function fs_legal_datenschutz() { ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-legal">
			<div class="fs-head" style="text-align:left;margin-bottom:2rem;"><h2><?php echo fs_icon( 'shield', 32, 'fs-icon-pink' ); ?> Datenschutzerklärung</h2></div>
			<div class="fs-legal__card">
				<div class="fs-legal__block">
					<h2>1. Datenschutz auf einen Blick</h2>
					<p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können.</p>
				</div>
				<div class="fs-legal__block">
					<h2>2. Verantwortliche Stelle</h2>
					<p>Fahrschule &amp; Fahrlehrerfachschule edi GmbH<br>Friedrich Ebert Str. 346, 47139 Duisburg<br>Telefon: +49 (0)203 57 85 703<br>E-Mail: <a href="mailto:info@fahrschule-edi.de">info@fahrschule-edi.de</a></p>
				</div>
				<div class="fs-legal__block">
					<h2>3. Ihre Rechte</h2>
					<p>Sie haben jederzeit das Recht, unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht auf Berichtigung oder Löschung dieser Daten.</p>
				</div>
				<div class="fs-legal__block">
					<h2>4. Erfassung von Daten</h2>
					<p>Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Manche Daten werden erhoben, wenn Sie uns diese mitteilen – etwa über das Anmeldeformular. Andere Daten werden automatisch beim Besuch der Website durch unsere IT-Systeme erfasst.</p>
				</div>
				<?php if ( function_exists( 'fahrschule_matomo_enabled' ) && fahrschule_matomo_enabled() ) :
					$cookieless = (bool) get_option( 'fahrschule_matomo_cookieless', true ); ?>
				<div class="fs-legal__block">
					<h2>5. Analyse-Tools (Matomo)</h2>
					<?php if ( $cookieless ) : ?>
					<p>Diese Website nutzt Matomo, eine selbst gehostete Webanalyse-Software. Die Einbindung ist so konfiguriert, dass im Browser keine Tracking-Cookies gesetzt werden und die Do-Not-Track-Einstellung Ihres Browsers respektiert wird.</p>
					<?php else : ?>
					<p>Diese Website nutzt Matomo, eine selbst gehostete Webanalyse-Software, zur statistischen Auswertung der Websitenutzung.</p>
					<?php endif; ?>
					<?php if ( function_exists( 'fahrschule_matomo_optout_shortcode' ) ) : ?>
					<p>Sie können der Erfassung Ihrer Daten durch Matomo jederzeit widersprechen:</p>
					<?php echo fahrschule_matomo_optout_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- eigene Funktion liefert bereits sicheres HTML (esc_url() intern) ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/* =========================================================================
 * TEIL 3: Unterseiten-Architektur
 * ====================================================================== */

/**
 * Seitenkopf für Unterseiten – ersetzt auf Unterseiten den großen Landing-Hero.
 * Titel und Einleitung stammen aus der zentralen Seiten-Registry.
 */
function fs_render_page_hero( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => '',
		'lead'      => '',
		'eyebrow'   => '',
		'highlight' => '',
	) );

	// Bringt der Seiteninhalt einen eigenen Hero mit (Standort, Erfahrungen,
	// Startseite), tritt der Seitenkopf der Vorlage zurück - sonst stünden
	// zwei Köpfe übereinander. So kommen alle Seitenvorlagen mit demselben
	// Aufbau aus, und die Redaktion kann den Hero frei setzen.
	$post = fs_context_post();
	if ( $post && preg_match( '#<!-- wp:fahrschule/(location-hero|testimonials-hero|video-hero)[ /]#', (string) $post->post_content ) ) {
		return '';
	}

	// Fallback auf die Registry-Daten der aktuellen Seite. Gehört zur Seite ein
	// PDF-Abschnitt ('pdf' in inc/pages.php), zeigt der Seitenkopf dessen Label
	// und Überschrift – der Abschnitt darunter verzichtet dann auf seinen Kopf.
	$pdf_id = '';
	if ( '' === $d['heading'] ) {
		$page = function_exists( 'fahrschule_current_page' ) ? fahrschule_current_page() : null;
		$pdf  = ( $page && ! empty( $page['pdf'] ) ) ? fahrschule_pdf( $page['pdf'] ) : array();
		if ( $pdf ) {
			$d['heading'] = $pdf['headline'];
			if ( '' === $d['eyebrow'] ) { $d['eyebrow'] = $pdf['label']; }
			if ( '' === $d['highlight'] && ! empty( $pdf['highlight'] ) ) { $d['highlight'] = $pdf['highlight']; }
			$pdf_id = $pdf['id'];
		} elseif ( $page ) {
			$d['heading'] = $page['title'];
		} elseif ( $post ) {
			$d['heading'] = get_the_title( $post );
		}
		if ( $page && '' === $d['lead'] && ! empty( $page['lead'] ) ) { $d['lead'] = $page['lead']; }
		// Website-Editor (Vorlage ohne Seite): Platzhalter statt leerer Fläche.
		if ( '' === $d['heading'] && fs_is_editor_preview() ) { $d['heading'] = 'Seitentitel'; }
	}
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-pagehero fs-surface-brand"<?php echo $pdf_id ? ' data-pdf-head="' . esc_attr( $pdf_id ) . '"' : ''; ?>>
		<div class="fs-container fs-z">
			<?php if ( $d['eyebrow'] ) : ?><?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?><?php endif; ?>
			<?php echo fs_el( 'h1', '', '', $heading ); ?>
			<?php if ( $d['lead'] ) : ?><?php echo fs_el( 'p', $d['lead'], 'class="fs-pagehero__lead"' ); ?><?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Themen-Teaser der Startseite: verweist auf die echten Unterseiten,
 * statt innerhalb einer Onepage zu Sektionen zu springen.
 */
function fs_render_page_teasers( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'  => '',
		'heading'  => '',
		'subtitle' => '',
		'seiten'   => array(),
	) );
	/* Seite => Icon. Label, Überschrift und erster Satz kommen wortgleich aus
	   dem PDF-Abschnitt der jeweiligen Unterseite (kein eigener Teasertext).
	   Jeder Teaser steht für einen eigenen Abschnitt – daher h2 statt h3. */
	$teasers = array();
	foreach ( fs_rows( $d['seiten'], 2 ) as $row ) {
		$teasers[ sanitize_key( $row[0] ) ] = $row[1] ? $row[1] : 'arrow-right';
	}
	$map = fahrschule_page_map();
	$teasers = array_filter( $teasers, static function ( $icon, $key ) use ( $map ) {
		return ! empty( $map[ $key ]['pdf'] );
	}, ARRAY_FILTER_USE_BOTH );
	if ( ! $teasers ) { return ''; }
	// Ein oder zwei Teaser stehen zentriert statt links im Viererraster.
	$cols = count( $teasers ) < 3 ? ' fs-teasers--wenige' : ' fs-teasers--3';
	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-z">
			<?php if ( $d['eyebrow'] || $d['heading'] || $d['subtitle'] ) : ?>
			<div class="fs-head">
				<?php if ( $d['eyebrow'] ) : ?><?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?><?php endif; ?>
				<?php if ( $d['heading'] ) : ?><?php echo fs_el( 'h2', $d['heading'] ); ?><?php endif; ?>
				<?php if ( $d['subtitle'] ) : ?><?php echo fs_el( 'p', $d['subtitle'] ); ?><?php endif; ?>
			</div>
			<?php endif; ?>
			<div class="fs-teasers<?php echo esc_attr( $cols ); ?>">
				<?php foreach ( $teasers as $key => $icon ) :
					$pdf = ( isset( $map[ $key ]['pdf'] ) ) ? fahrschule_pdf( $map[ $key ]['pdf'] ) : array();
					if ( ! $pdf ) { continue; } ?>
				<a class="fs-teaser" href="<?php echo esc_url( fs_url( $key ) ); ?>" data-pdf-teaser="<?php echo esc_attr( $pdf['id'] ); ?>">
					<span class="fs-teaser__ico"><?php echo fs_icon( $icon, 24 ); ?></span>
					<span class="fs-eyebrow"><?php echo esc_html( $pdf['label'] ); ?></span>
					<h2><?php echo esc_html( $pdf['headline'] ); ?></h2>
					<p><?php echo esc_html( fahrschule_pdf_first_sentence( $pdf['paragraphs'][0] ) ); ?></p>
					<span class="fs-teaser__more">Mehr erfahren <?php echo fs_icon( 'arrow-right', 16 ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Fahrlehrerfachschule – Ausbildung zum Fahrlehrer.
 * Eigener Geschäftsbereich laut Firmierung „Fahrschule & Fahrlehrerfachschule edi GmbH".
 */
function fs_render_instructor_training( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'   => 'Fahrlehrerfachschule',
		'heading'   => 'Machen Sie Ihr Können zum Beruf',
		'highlight' => 'Beruf',
		'p1'        => 'Als Fahrschule & Fahrlehrerfachschule edi bilden wir nicht nur Fahrschüler aus, sondern auch die Fahrlehrerinnen und Fahrlehrer von morgen.',
		'p2'        => 'Die Ausbildung erfolgt nach dem Fahrlehrergesetz und verbindet Fachtheorie, Verkehrspädagogik und Praxis. Sie lernen bei Ausbildern, die selbst täglich im Fahrschulalltag stehen.',
		'footer'    => 'Sie interessieren sich für die Fahrlehrerlaubnis? Wir beraten Sie persönlich zu Voraussetzungen, Ablauf und Fördermöglichkeiten.',
		'btn_label' => 'Beratung anfragen',
		'btn_url'   => '@kontakt',
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	$content = array(
		array( 'graduation', 'Fahrlehrerlaubnis BE', 'Die Grundqualifikation für die Ausbildung von PKW-Fahrschülern.' ),
		array( 'award', 'Erweiterungen A, CE, DE', 'Zusatzqualifikationen für Motorrad, LKW und Bus.' ),
		array( 'users', 'Verkehrspädagogik', 'Didaktik, Kommunikation und der sichere Umgang mit Fahrschülern.' ),
		array( 'shield', 'Recht & Technik', 'Verkehrsrecht, Fahrzeugtechnik und Gefahrenlehre fundiert vermittelt.' ),
	);
	$reasons = array(
		'Ausbildung nach dem Fahrlehrergesetz (FahrlG)',
		'Erfahrene Ausbilder aus dem täglichen Fahrschulbetrieb',
		'Eigener Fuhrpark für alle Klassen',
		'Persönliche Betreuung in kleinen Gruppen',
		'Beratung zu Förderung und Finanzierung',
		'Beste Perspektiven: Fahrlehrer werden gesucht',
	);
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', '', '', $heading ), 'class="fs-head"' ); ?>
			<div class="fs-rich fs-center">
				<?php echo fs_el( 'p', $d['p1'], 'style="color:var(--c-heading);font-weight:600;font-size:1.2rem;"' ); ?>
				<?php echo fs_el( 'p', $d['p2'] ); ?>
			</div>
			<div class="fs-grid fs-grid--2 fs-mt-8" style="max-width:56rem;margin-inline:auto;">
				<?php foreach ( $content as $c ) : ?>
				<div class="fs-value">
					<div class="fs-value__ico"><?php echo fs_icon( $c[0], 24 ); ?></div>
					<h3><?php echo esc_html( $c[1] ); ?></h3>
					<p><?php echo esc_html( $c[2] ); ?></p>
				</div>
				<?php endforeach; ?>
			</div>
			<h3 class="fs-center fs-mt-8" style="color:var(--c-heading);font-size:1.5rem;font-weight:800;">Warum bei edi?</h3>
			<div class="fs-features fs-mt-4">
				<?php foreach ( $reasons as $r ) : ?>
				<div class="fs-feature"><?php echo fs_icon( 'check-circle', 22 ); ?><span><?php echo esc_html( $r ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<div class="fs-center fs-mt-8">
				<?php echo fs_el( 'p', $d['footer'], 'style="color:var(--c-text);max-width:42rem;margin:0 auto 1.5rem;"' ); ?>
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( fs_link( $d['btn_url'] ) ); ?>"><?php echo esc_html( $d['btn_label'] ); ?> <?php echo fs_icon( 'arrow-right', 20 ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * BEREICHS-ÜBERSICHT (Ebene 1)
 *
 * Listet die Seiten auf, die der Registry nach diesem Bereich untergeordnet
 * sind. Die Karten entstehen damit aus der Seitenstruktur selbst: wer eine
 * Klassenseite anlegt und ihr 'parent' setzt, bekommt die Karte automatisch -
 * es gibt keine zweite Liste, die man vergessen könnte zu pflegen.
 * ---------------------------------------------------------------------- */
function fs_render_bereich_uebersicht( $a ) {
	$d = fs_attrs( $a, array(
		'bereich'  => '',
		'eyebrow'  => '',
		'heading'  => '',
		'subtitle' => '',
		'icon'     => 'award',
		'more'     => 'Zur Klasse',
	) );

	// Ohne ausdrückliche Angabe gilt die Seite, auf der der Block steht.
	// Ist auch die unbekannt (Editor-Vorschau, Pattern-Katalog), gibt es
	// nichts auszugeben - sonst würde parent === '' auf alle Wurzelseiten
	// passen und der Block die halbe Website auflisten.
	// "@eltern" = Bereich der Seite, auf der der Block steht (Vorlage
	// page-kurs: die Geschwisterklassen). Die eigene Seite fehlt dann.
	$current = fahrschule_current_key();
	$map     = fahrschule_page_map();
	if ( '@eltern' === $d['bereich'] ) {
		$parent = ( $current && ! empty( $map[ $current ]['parent'] ) ) ? $map[ $current ]['parent'] : '';
	} else {
		$parent = $d['bereich'] ? $d['bereich'] : $current;
	}
	if ( '' === $parent || ! isset( $map[ $parent ] ) ) {
		return fs_editor_hint( 'Bereichsübersicht', 'Zeigt hier die Klassen des Bereichs - auf einer Bereichs- oder Klassenseite.' );
	}

	$children = array();
	foreach ( $map as $key => $page ) {
		if ( isset( $page['parent'] ) && $page['parent'] === $parent && $key !== $current ) {
			$children[ $key ] = $page;
		}
	}
	if ( ! $children ) { return ''; }
	// {bereich} in Texten steht für den Namen des Bereichs - so passt eine
	// Überschrift in der Vorlage auf PKW, Motorrad und LKW & Bus zugleich.
	foreach ( array( 'eyebrow', 'heading', 'subtitle' ) as $k ) {
		$d[ $k ] = str_replace( '{bereich}', $map[ $parent ]['menu'], $d[ $k ] );
	}

	ob_start(); ?>
	<section class="fs-section fs-surface-brand">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>
			<div class="fs-teasers fs-teasers--3">
				<?php foreach ( $children as $key => $page ) : ?>
				<a class="fs-teaser" href="<?php echo esc_url( fs_url( $key ) ); ?>">
					<span class="fs-teaser__ico"><?php echo fs_icon( isset( $page['icon'] ) ? $page['icon'] : $d['icon'], 24 ); ?></span>
					<h2><?php echo esc_html( $page['menu'] ); ?></h2>
					<?php echo fs_el( 'p', isset( $page['lead'] ) ? $page['lead'] : '' ); ?>
					<span class="fs-teaser__more"><?php echo esc_html( $d['more'] ); ?> <?php echo fs_icon( 'arrow-right', 16 ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * KURS-/KLASSEN-DETAILS (Ebene 2)
 *
 * Feste Inhalts-Slots einer Klassenseite. Jeder Slot ist einzeln leerbar und
 * verschwindet dann samt Überschrift - eine Klasse ohne Förderung zeigt
 * keinen leeren Förderungs-Kasten.
 * ---------------------------------------------------------------------- */
function fs_render_kurs_details( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'         => '',
		'heading'         => 'Das Wichtigste auf einen Blick',
		'alter'           => '',
		'theorie'         => '',
		'pflichtfahrten'  => '',
		'dauer'           => '',
		'pruefung'        => '',
		'preis'           => '',
		'preis_link'      => '@preise',
		'preis_label'     => 'Alle Preise & Rechner',
		'voraussetzungen' => '',
		'ablauf'          => '',
		'foerderung'      => '',
		'hinweis'         => '',
	) );

	// Kurzdaten der Seitenleiste: Beschriftung => Wert. Leeres entfällt.
	$facts = array_filter( array(
		'Mindestalter'              => $d['alter'],
		'Theorieunterricht'         => $d['theorie'],
		'Pflichtfahrten'            => $d['pflichtfahrten'],
		'Fahrstunden (Richtwert)'   => $d['dauer'],
		'Prüfung'                   => $d['pruefung'],
		'Richtpreis'                => $d['preis'],
	), static function ( $v ) { return '' !== trim( (string) $v ); } );

	// Textkarten: Überschrift => Inhalt.
	$sections = array_filter( array(
		'Voraussetzungen'          => $d['voraussetzungen'],
		'Ablauf der Ausbildung'    => $d['ablauf'],
		'Förderung & Finanzierung' => $d['foerderung'],
	), static function ( $v ) { return '' !== trim( (string) $v ); } );

	$link = fs_has( $d, 'preis_link' ) && fs_has( $d, 'preis_label' );
	if ( ! $facts && ! $sections && ! fs_has( $d, 'hinweis' ) ) { return ''; }

	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] ), 'class="fs-head"' ); ?>

			<div class="<?php echo esc_attr( $sections ? 'fs-kurs' : 'fs-kurs fs-kurs--solo' ); ?>">
				<?php if ( $sections || fs_has( $d, 'hinweis' ) ) : ?>
				<div class="fs-kurs__main">
					<?php foreach ( $sections as $title => $text ) : ?>
					<div class="fs-card"><div class="fs-card__body">
						<h3><?php echo esc_html( $title ); ?></h3>
						<?php echo fs_paragraphs( $text ); ?>
					</div></div>
					<?php endforeach; ?>
					<?php echo fs_el( 'p', $d['hinweis'], 'class="fs-kurs__hinweis"' ); ?>
				</div>
				<?php endif; ?>

				<?php if ( $facts || $link ) : ?>
				<aside class="fs-card fs-kurs__facts" aria-label="Kurzdaten">
					<div class="fs-card__body">
						<h3>Kurzdaten</h3>
						<?php if ( $facts ) : ?>
						<dl>
							<?php foreach ( $facts as $label => $value ) : ?>
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd><?php echo esc_html( $value ); ?></dd>
							<?php endforeach; ?>
						</dl>
						<?php endif; ?>
						<?php if ( $link ) : ?>
						<a class="fs-kurs__preislink" href="<?php echo esc_url( fs_link( $d['preis_link'] ) ); ?>"><?php echo esc_html( $d['preis_label'] ); ?> <?php echo fs_icon( 'arrow-right', 16 ); ?></a>
						<?php endif; ?>
					</div>
				</aside>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FAHRLEHRER-FINDER
 *
 * Eine Fahrschule wird danach ausgesucht, ob der Fahrlehrer sympathisch wirkt
 * und ob der Standort passt. Das Raster ist deshalb filterbar statt eine
 * ungeordnete Fotowand.
 *
 * Das Foto kommt über edi-team-<slug> aus der Mediathek, das Zweitfoto über
 * denselben Namen mit -b als Wechselbild. Standort, Klassen und Rolle stehen
 * bewusst NICHT im Dateinamen, sondern im Wiederholfeld: ein Standortwechsel
 * wäre sonst ein Umbenennen samt Neuimport.
 * ---------------------------------------------------------------------- */
/**
 * Vorgabezeilen der Gruppe „Büro & Ansprechpartner": die Ansprechperson je
 * Standort aus den Stammdaten (inc/seo.php, 'contact'). Wer dort steht und
 * schon als Fahrlehrer geführt wird (Aslan Seymen), erscheint nicht doppelt.
 *
 * @param array $lehrer_zeilen Fahrlehrer-Zeilen des Finders (Name zuerst).
 */
function fs_team_ansprechpartner( $lehrer_zeilen ) {
	$lehrer = array();
	foreach ( $lehrer_zeilen as $z ) { $lehrer[] = mb_strtolower( strtok( $z[0], ' ' ) ); }
	$rows = array();
	foreach ( fahrschule_business_data()['locations'] as $loc ) {
		$name = trim( (string) $loc['contact'] );
		if ( '' === $name ) { continue; }
		$vorname = mb_strtolower( strtok( $name, ' ' ) );
		if ( in_array( $vorname, $lehrer, true ) ) { continue; }
		$rows[] = array( $name, '', $loc['city'], '', 'Ansprechperson vor Ort', 'Büro & Ansprechpartner' );
	}
	return $rows;
}

function fs_render_fahrlehrer_finder( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'  => 'Unser Team',
		'heading'  => 'Finden Sie Ihren Fahrlehrer',
		'subtitle' => 'Filtern Sie nach Standort und Führerscheinklasse.',
		'people'   => array(),
		'empty'    => 'Für diese Auswahl ist gerade niemand hinterlegt.',
	) );

	$people = fs_rows( $d['people'], 6 );
	if ( ! $people ) { return ''; }

	// Gruppen in der Reihenfolge des PDF (F9); Zeilen ohne Gruppe (Stand 2.x,
	// damals nur Fahrlehrer) zählen zu den Ausbildern. Leere Gruppen entfallen.
	$gruppen = array_fill_keys( array_keys( fahrschule_pdf( 'f9' )['groups'] ), array() );
	foreach ( $people as $p ) {
		$g = '' !== trim( $p[5] ) ? trim( $p[5] ) : 'Dozenten & Ausbilder';
		$gruppen[ $g ][] = $p;
	}
	$gruppen = array_filter( $gruppen );

	// Filterwerte aus den Daten ableiten - keine zweite Liste zu pflegen.
	$orte = array();
	$klassen = array();
	foreach ( $people as $p ) {
		if ( '' !== trim( $p[2] ) ) { $orte[ $p[2] ] = true; }
		foreach ( preg_split( '/\s*,\s*/', $p[3] ) as $k ) {
			if ( '' !== trim( $k ) ) { $klassen[ trim( $k ) ] = true; }
		}
	}
	$orte    = array_keys( $orte );
	$klassen = array_keys( $klassen );
	sort( $orte );
	sort( $klassen );

	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-fs-team>
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>

			<?php if ( count( $people ) > 3 ) : ?>
			<div class="fs-teamfilter" role="group" aria-label="Fahrlehrer filtern">
				<label>
					<span class="fs-visually-hidden">Standort</span>
					<select data-fs-team-ort>
						<option value="">Alle Standorte</option>
						<?php foreach ( $orte as $o ) : ?>
						<option value="<?php echo esc_attr( $o ); ?>"><?php echo esc_html( $o ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span class="fs-visually-hidden">Führerscheinklasse</span>
					<select data-fs-team-klasse>
						<option value="">Alle Klassen</option>
						<?php foreach ( $klassen as $k ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>">Klasse <?php echo esc_html( $k ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="fs-teamfilter__check">
					<input type="checkbox" data-fs-team-weiblich>
					<span>Nur Fahrlehrerinnen</span>
				</label>
			</div>
			<?php endif; ?>

			<div data-fs-team-grid>
				<?php foreach ( $gruppen as $gruppe => $mitglieder ) : ?>
				<div class="fs-teamgroup" data-fs-team-group>
				<h3 class="fs-teamgroup__title"><?php echo esc_html( $gruppe ); ?></h3>
				<div class="fs-teamgrid">
				<?php foreach ( $mitglieder as $p ) :
					list( $name, $slug, $ort, $kl, $rolle ) = $p;
					$foto  = $slug ? fs_media_by_slug( 'edi-team-' . $slug ) : '';
					$foto2 = $slug ? fs_media_by_slug( 'edi-team-' . $slug . '-b' ) : '';
					// Fahrlehrerin ergibt sich aus der Rolle - keine eigene Spalte nötig.
					$weiblich = ( false !== mb_stripos( $rolle, 'lehrerin' ) ) ? '1' : '0';
					$initialen = mb_strtoupper( mb_substr( trim( $name ), 0, 1 ) );
					?>
				<article class="fs-teamcard"
					data-ort="<?php echo esc_attr( $ort ); ?>"
					data-klassen="<?php echo esc_attr( $kl ); ?>"
					data-weiblich="<?php echo esc_attr( $weiblich ); ?>">
					<div class="fs-teamcard__photo">
						<?php if ( $foto ) : ?>
							<img src="<?php echo esc_url( $foto ); ?>" alt="<?php echo esc_attr( sprintf( '%s, %s%s', $name, $rolle, $ort ? ' in ' . $ort : '' ) ); ?>" width="800" height="1000" loading="lazy" decoding="async">
							<?php if ( $foto2 ) : ?>
							<img class="fs-teamcard__alt" src="<?php echo esc_url( $foto2 ); ?>" alt="" aria-hidden="true" width="800" height="1000" loading="lazy" decoding="async">
							<?php endif; ?>
						<?php else : ?>
							<div class="fs-teamcard__avatar" aria-hidden="true"><?php echo esc_html( $initialen ); ?></div>
						<?php endif; ?>
					</div>
					<div class="fs-teamcard__body">
						<?php echo fs_el( 'h4', $name ); ?>
						<?php echo fs_el( 'p', $rolle, 'class="fs-teamcard__names"' ); ?>
						<p class="fs-teamcard__meta">
							<?php if ( '' !== trim( $ort ) ) : ?><span><?php echo fs_icon( 'map-pin', 14 ); ?> <?php echo esc_html( $ort ); ?></span><?php endif; ?>
							<?php if ( '' !== trim( $kl ) ) : ?><span><?php echo fs_icon( 'award', 14 ); ?> <?php echo esc_html( $kl ); ?></span><?php endif; ?>
						</p>
					</div>
				</article>
				<?php endforeach; ?>
				</div>
				</div>
				<?php endforeach; ?>
			</div>
			<p class="fs-center fs-mt-8" data-fs-team-empty hidden><?php echo esc_html( $d['empty'] ); ?></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * FOTO-GALERIE
 *
 * Liest die Bilder live aus der Mediathek statt aus einer Handauswahl im
 * Editor: wer 73 Bilder einzeln in einen Block zieht, macht diese Arbeit bei
 * jeder Änderung erneut. Maßgeblich ist der Namenspräfix - ein Bild, das als
 * edi-galerie-fahrstunde-012 hochgeladen wird, erscheint ohne weiteres Zutun
 * unter "Fahrstunde".
 *
 * Solange nichts freigegeben ist, bleibt die Mediathek leer und der Block
 * blendet sich aus. Er erzwingt nichts - die Frage, welche Aufnahmen gezeigt
 * werden dürfen, entscheidet sich beim Hochladen, nicht hier.
 * ---------------------------------------------------------------------- */
/**
 * Alle Anhänge mit Slug edi-galerie-*, nach Name sortiert.
 *
 * WP_Query kennt keinen Präfixfilter auf post_name (name__like gibt es
 * nicht - früher holte die Galerie deshalb die ersten 200 Anhänge und
 * filterte nachträglich, in großen Mediatheken fielen Bilder heraus).
 * Der posts_where-Filter hängt nur für diese eine Abfrage an.
 */
function fs_galerie_anhaenge() {
	$where = static function ( $sql ) {
		global $wpdb;
		return $sql . $wpdb->prepare( " AND {$wpdb->posts}.post_name LIKE %s", $wpdb->esc_like( 'edi-galerie-' ) . '%' );
	};
	add_filter( 'posts_where', $where );
	$posts = get_posts( array(
		'post_type'        => 'attachment',
		'post_mime_type'   => 'image',
		'post_status'      => 'inherit',
		'posts_per_page'   => 500,
		'orderby'          => 'name',
		'order'            => 'ASC',
		'no_found_rows'    => true,
		'suppress_filters' => false, // sonst greift posts_where bei get_posts() nicht
	) );
	remove_filter( 'posts_where', $where );
	return $posts;
}

function fs_render_galerie( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'    => '',
		'heading'    => '',
		'subtitle'   => '',
		'batch'      => '24',
		'kategorien' => array(),
	) );

	$kats  = fs_rows( $d['kategorien'], 2 );
	$batch = max( 4, (int) $d['batch'] );

	// Kategorie-Präfixe; die Galerie zeigt aber auch Bilder ohne Kategorie
	// (edi-galerie-012, so benennt sie bin/edi-bilder.py).
	$prefixe = array();
	foreach ( $kats as $kat ) {
		$p = sanitize_title( $kat[1] );
		if ( '' !== $p ) { $prefixe[ $p ] = $kat[0]; }
	}

	$bilder = array();
	$genutzt = array();
	foreach ( fs_galerie_anhaenge() as $post ) {
		$url = wp_get_attachment_image_url( $post->ID, 'large' );
		if ( ! $url ) { continue; }
		$rest = substr( $post->post_name, strlen( 'edi-galerie-' ) );
		$kat  = '';
		if ( preg_match( '/^([a-z0-9-]+?)-\d+$/', $rest, $m ) && isset( $prefixe[ $m[1] ] ) ) {
			$kat = $m[1];
			$genutzt[ $kat ] = true;
		}
		$bilder[] = array(
			'kat'  => $kat,
			'url'  => $url,
			'full' => wp_get_attachment_image_url( $post->ID, 'full' ),
			'alt'  => (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
		);
	}
	if ( ! $bilder ) { return ''; }

	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-fs-galerie data-batch="<?php echo esc_attr( $batch ); ?>">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>

			<?php // Filter nur, wenn es kategorisierte Bilder gibt - eine Pille ohne Treffer wäre eine Sackgasse.
			if ( $genutzt ) : ?>
			<div class="fs-galfilter" role="group" aria-label="Galerie filtern">
				<button type="button" class="fs-pill is-active" data-kat="">Alle</button>
				<?php foreach ( $prefixe as $p => $label ) : if ( empty( $genutzt[ $p ] ) ) { continue; } ?>
				<button type="button" class="fs-pill" data-kat="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<div class="fs-galgrid" data-fs-gal-grid>
				<?php foreach ( $bilder as $i => $b ) : ?>
				<button type="button" class="fs-galitem" data-kat="<?php echo esc_attr( $b['kat'] ); ?>"
					data-full="<?php echo esc_url( $b['full'] ); ?>"
					aria-label="<?php echo esc_attr( 'Bild vergrößern: ' . ( '' !== $b['alt'] ? $b['alt'] : 'Foto ' . ( $i + 1 ) ) ); ?>"
					<?php echo $i >= $batch ? ' hidden' : ''; ?>>
					<img src="<?php echo esc_url( $b['url'] ); ?>" alt="<?php echo esc_attr( $b['alt'] ); ?>" loading="lazy" decoding="async">
				</button>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $bilder ) > $batch ) : ?>
			<div class="fs-center fs-mt-8">
				<button type="button" class="fs-btn fs-btn--primary" data-fs-gal-more>Weitere Bilder laden</button>
			</div>
			<?php endif; ?>
		</div>

		<div class="fs-lightbox" data-fs-lightbox hidden role="dialog" aria-modal="true" aria-label="Bildansicht">
			<button type="button" class="fs-lightbox__close" data-fs-lb-close aria-label="Schließen"><?php echo fs_icon( 'x', 24 ); ?></button>
			<button type="button" class="fs-lightbox__nav fs-lightbox__nav--prev" data-fs-lb-prev aria-label="Vorheriges Bild"><?php echo fs_icon( 'chevron-left', 28 ); ?></button>
			<img data-fs-lb-img src="" alt="">
			<button type="button" class="fs-lightbox__nav fs-lightbox__nav--next" data-fs-lb-next aria-label="Nächstes Bild"><?php echo fs_icon( 'chevron-right', 28 ); ?></button>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * BROTKRUMEN (Vorlagen) - Hülle um fs_breadcrumbs()
 * ---------------------------------------------------------------------- */
function fs_render_breadcrumbs( $a ) {
	$out = fs_breadcrumbs();
	if ( '' === $out ) {
		return fs_editor_hint( 'Brotkrumen', 'Startseite › Bereich › Seite – erscheinen auf jeder Unterseite.' );
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * FEHLERSEITE (Vorlage 404)
 * ---------------------------------------------------------------------- */
function fs_render_fehlerseite( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'   => 'Fehler 404',
		'heading'   => 'Diese Seite gibt es nicht mehr',
		'highlight' => 'nicht mehr',
		'text'      => '',
		'links'     => array(),
		'suche'     => '1',
	) );
	$links = fs_rows( $d['links'], 3 );
	ob_start(); ?>
	<section class="fs-section fs-surface-brand fs-fehlerseite">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h1', '', '', fs_highlight( $d['heading'], $d['highlight'] ) )
				. fs_el( 'p', $d['text'] ), 'class="fs-head"' ); ?>
			<?php /* Reihenfolge: Meldung, Suche, Wege zurück - wer sich vertippt
			         hat, sucht zuerst; keine Stellenangebote o. Ä. auf der 404. */ ?>
			<?php if ( '1' === (string) $d['suche'] ) : ?>
			<div class="fs-center fs-mt-8"><?php echo get_search_form( array( 'echo' => false ) ); ?></div>
			<?php endif; ?>
			<?php if ( $links ) : ?>
			<div class="fs-features fs-mt-8">
				<?php foreach ( $links as $l ) : if ( '' === trim( $l[0] ) ) { continue; } ?>
				<a class="fs-feature" href="<?php echo esc_url( fs_link( $l[1] ) ); ?>">
					<?php echo fs_icon( $l[2] ? $l[2] : 'arrow-right', 22 ); ?><span><?php echo esc_html( $l[0] ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * KARTE (Vorlage Standort) - Zwei-Klick-Lösung
 *
 * Harte Regel: keine Drittanbieter-Abrufe ohne Einwilligung. Ausgegeben wird
 * nur ein Platzhalter mit der Karten-Adresse im data-Attribut; das iframe
 * setzt frontend.js erst nach Klick oder bei Einwilligung „Alle akzeptieren".
 * Ohne JavaScript bleibt der Routen-Link (externer Aufruf nur auf Klick).
 * ---------------------------------------------------------------------- */
function fs_render_karte( $a ) {
	$d = fs_attrs( $a, array(
		'city'    => '',
		'heading' => 'So finden Sie uns',
		'hinweis' => '',
		'button'  => 'Karte laden',
		'route'   => 'Route bei OpenStreetMap planen',
	) );
	$locs = fahrschule_business_data()['locations'];
	$key  = sanitize_key( '' !== trim( $d['city'] ) ? $d['city'] : fahrschule_current_key() );
	if ( ! isset( $locs[ $key ] ) ) {
		return fs_editor_hint( 'Karte', 'Zeigt hier die Karte des Standorts - auf einer Standortseite oder mit eingetragenem Standort.' );
	}
	$loc  = $locs[ $key ];
	$lat  = (float) $loc['lat'];
	$lng  = (float) $loc['lng'];
	$bbox = sprintf( '%.4f,%.4f,%.4f,%.4f', $lng - 0.008, $lat - 0.004, $lng + 0.008, $lat + 0.004 );
	$src  = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode( $bbox ) . '&layer=mapnik&marker=' . rawurlencode( $lat . ',' . $lng );
	$url  = 'https://www.openstreetmap.org/directions?to=' . rawurlencode( $lat . ',' . $lng );
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'h2', $d['heading'] ), 'class="fs-head"' ); ?>
			<div class="fs-karte" data-fs-karte data-src="<?php echo esc_url( $src ); ?>" data-title="<?php echo esc_attr( 'Karte: Fahrschule edi ' . $loc['city'] ); ?>">
				<div class="fs-karte__platzhalter">
					<p class="fs-karte__adresse"><?php echo fs_icon( 'map-pin', 20 ); ?> <?php echo esc_html( $loc['street'] . ', ' . $loc['zip'] . ' ' . $loc['city'] ); ?></p>
					<?php echo fs_el( 'p', $d['hinweis'] ); ?>
					<div class="fs-karte__aktionen">
						<?php if ( fs_has( $d, 'button' ) ) : ?>
						<button type="button" class="fs-btn fs-btn--primary" data-fs-karte-load><?php echo esc_html( $d['button'] ); ?></button>
						<?php endif; ?>
						<?php if ( fs_has( $d, 'route' ) ) : ?>
						<a class="fs-btn fs-btn--outline" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $d['route'] ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * STARTSEITE: Vertrauensleiste, Klassen-Kacheln, Seiten-Anriss
 * ---------------------------------------------------------------------- */
function fs_render_vertrauensleiste( $a ) {
	$d = fs_attrs( $a, array( 'items' => array() ) );
	$items = fs_rows( $d['items'], 3 );
	if ( ! $items ) { return ''; }
	ob_start(); ?>
	<section class="fs-trustbar fs-ctx-deep" aria-label="Auf einen Blick">
		<div class="fs-container">
			<ul class="fs-trustbar__list">
				<?php foreach ( $items as $it ) :
					list( $icon, $text, $logo ) = $it;
					if ( '' === trim( $text ) ) { continue; } ?>
				<li>
					<?php if ( '' !== trim( $logo ) && fs_has_media( $logo ) ) : ?>
						<img src="<?php echo esc_url( fs_media( $logo ) ); ?>" alt="" width="64" height="26" loading="lazy" decoding="async">
					<?php else : ?>
						<?php echo fs_icon( $icon ? $icon : 'check-circle', 20 ); ?>
					<?php endif; ?>
					<span><?php echo esc_html( $text ); ?></span>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Vorgabe der Klassen-Kacheln aus PDF F3 (Gruppen und Klassen). */
function fs_klassen_kacheln_vorgabe() {
	$ziel = array(
		'Motorrad' => array( '@motorrad', 'zap' ),
		'PKW'      => array( '@pkw', 'award' ),
		'LKW'      => array( '@lkw-bus', 'truck' ),
		'Bus'      => array( '@lkw-bus', 'users' ),
	);
	$rows = array();
	foreach ( fahrschule_pdf( 'f3' )['groups'] as $gruppe => $klassen ) {
		$z      = isset( $ziel[ $gruppe ] ) ? $ziel[ $gruppe ] : array( '@klassen', 'award' );
		$rows[] = array( $gruppe, implode( ', ', $klassen ), $z[0], $z[1] );
	}
	return $rows;
}

function fs_render_klassen_kacheln( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'    => '',
		'heading'    => '',
		'kacheln'    => array(),
		'btn1_label' => 'Klassen ansehen',
		'btn2_label' => 'Beratung',
		'btn2_url'   => '@kontakt',
	) );
	$kacheln = fs_rows( $d['kacheln'], 4 );
	if ( ! $kacheln ) { return ''; }
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-pdf-teaser="F3">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] ), 'class="fs-head"' ); ?>
			<div class="fs-kacheln">
				<?php foreach ( $kacheln as $k ) :
					list( $titel, $klassen, $link, $icon ) = $k;
					if ( '' === trim( $titel ) ) { continue; } ?>
				<article class="fs-card fs-kachel">
					<div class="fs-card__body">
						<span class="fs-teaser__ico"><?php echo fs_icon( $icon ? $icon : 'award', 24 ); ?></span>
						<h3><?php echo esc_html( $titel ); ?></h3>
						<?php if ( '' !== trim( $klassen ) ) : ?>
						<p class="fs-kachel__klassen"><?php echo esc_html( implode( ' · ', preg_split( '/\s*,\s*/', trim( $klassen ) ) ) ); ?></p>
						<?php endif; ?>
						<div class="fs-kachel__aktionen">
							<?php if ( fs_has( $d, 'btn1_label' ) && '' !== trim( $link ) ) : ?>
							<a class="fs-btn fs-btn--primary" href="<?php echo esc_url( fs_link( $link ) ); ?>"><?php echo esc_html( $d['btn1_label'] ); ?><span class="fs-visually-hidden"> – <?php echo esc_html( $titel ); ?></span></a>
							<?php endif; ?>
							<?php if ( fs_has( $d, 'btn2_label' ) && fs_has( $d, 'btn2_url' ) ) : ?>
							<a class="fs-btn fs-btn--outline" href="<?php echo esc_url( fs_link( $d['btn2_url'] ) ); ?>"><?php echo esc_html( $d['btn2_label'] ); ?><span class="fs-visually-hidden"> – <?php echo esc_html( $titel ); ?></span></a>
							<?php endif; ?>
						</div>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Anriss einer Unterseite. Texte wortgleich aus ihrem PDF-Abschnitt (Label,
 * Überschrift, erster Absatz) bzw. Titel und Einleitung aus der Registry.
 * Voll steht der Abschnitt nur auf der Unterseite - der Marker
 * data-pdf-teaser hält das für die Doppelprüfung auseinander.
 */
function fs_render_seiten_anriss( $a ) {
	$d = fs_attrs( $a, array(
		'seite'     => '',
		'eyebrow'   => '',
		'heading'   => '',
		'text'      => '',
		'btn_label' => 'Mehr erfahren',
	) );
	$map = fahrschule_page_map();
	$key = sanitize_key( $d['seite'] );
	if ( ! isset( $map[ $key ] ) ) {
		return fs_editor_hint( 'Seiten-Anriss', 'Im Feld „Seite“ einen Schlüssel aus der Seitenliste eintragen, z. B. ueber-uns.' );
	}
	$pg  = $map[ $key ];
	$pdf = ! empty( $pg['pdf'] ) ? fahrschule_pdf( $pg['pdf'] ) : array();
	if ( '' === $d['eyebrow'] ) { $d['eyebrow'] = $pdf ? $pdf['label'] : ''; }
	if ( '' === $d['heading'] ) { $d['heading'] = $pdf ? $pdf['headline'] : $pg['title']; }
	if ( '' === $d['text'] ) { $d['text'] = $pdf ? $pdf['paragraphs'][0] : ( isset( $pg['lead'] ) ? $pg['lead'] : '' ); }
	$marker  = $pdf ? ' data-pdf-teaser="' . esc_attr( $pdf['id'] ) . '"' : '';
	ob_start(); ?>
	<section class="fs-section fs-surface-brand"<?php echo $marker; ?>>
		<div class="fs-container fs-anriss">
			<?php echo fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' ); ?>
			<?php echo fs_el( 'h2', $d['heading'] ); ?>
			<?php echo fs_el( 'p', $d['text'] ); ?>
			<?php if ( fs_has( $d, 'btn_label' ) ) : ?>
			<a class="fs-btn fs-btn--primary" href="<?php echo esc_url( fs_url( $key ) ); ?>"><?php echo esc_html( $d['btn_label'] ); ?><span class="fs-visually-hidden">: <?php echo esc_html( $pg['menu'] ); ?></span> <?php echo fs_icon( 'arrow-right', 18 ); ?></a>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * STELLEN (WP Job Manager)
 * ---------------------------------------------------------------------- */

/** Offene Stellen - ohne WP Job Manager oder ohne Treffer leer. */
function fs_stellen( $anzahl, $kategorie = '' ) {
	if ( ! post_type_exists( 'job_listing' ) ) { return array(); }
	$args = array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, min( 50, (int) $anzahl ) ),
		'no_found_rows'  => true,
		// Als besetzt markierte Stellen (WP Job Manager: _filled = 1) nicht zeigen.
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => '_filled', 'value' => '1', 'compare' => '!=' ),
			array( 'key' => '_filled', 'compare' => 'NOT EXISTS' ),
		),
	);
	if ( '' !== trim( (string) $kategorie ) ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'job_listing_category', 'field' => 'slug', 'terms' => sanitize_title( $kategorie ) ) );
	}
	return get_posts( $args );
}

/** Eckdaten einer Stelle: Ort, Art, Kategorie (leer entfällt). */
function fs_stelle_eckdaten( $post ) {
	$terms = static function ( $tax ) use ( $post ) {
		$t = taxonomy_exists( $tax ) ? get_the_terms( $post, $tax ) : array();
		return ( $t && ! is_wp_error( $t ) ) ? implode( ', ', wp_list_pluck( $t, 'name' ) ) : '';
	};
	return array_filter( array(
		'map-pin'   => (string) get_post_meta( $post->ID, '_job_location', true ),
		'clock'     => $terms( 'job_listing_type' ),
		'users'     => $terms( 'job_listing_category' ),
	) );
}

function fs_render_jobs( $a ) {
	$d = fs_attrs( $a, array(
		'heading'   => 'Offene Stellen',
		'intro'     => '',
		'anzahl'    => '10',
		'kategorie' => '',
		'btn_label' => 'Zur Stelle',
		'leer'      => 'Aktuell ist keine Stelle ausgeschrieben. Initiativbewerbungen sind willkommen.',
		'initiativ' => 'Initiativ bewerben',
		'ziel'      => '#kontaktformular',
	) );
	$jobs = fs_stellen( $d['anzahl'], $d['kategorie'] );
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-fs-jobs>
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'h2', $d['heading'] ) . fs_el( 'p', $d['intro'] ), 'class="fs-head"' ); ?>
			<?php if ( $jobs ) : ?>
			<div class="fs-jobs">
				<?php foreach ( $jobs as $job ) : ?>
				<article class="fs-card fs-job">
					<div class="fs-card__body">
						<h3><?php echo esc_html( get_the_title( $job ) ); ?></h3>
						<?php $eck = fs_stelle_eckdaten( $job ); if ( $eck ) : ?>
						<ul class="fs-job__eck">
							<?php foreach ( $eck as $icon => $wert ) : ?><li><?php echo fs_icon( $icon, 16 ); ?> <?php echo esc_html( $wert ); ?></li><?php endforeach; ?>
						</ul>
						<?php endif; ?>
						<?php if ( fs_has( $d, 'btn_label' ) ) : ?>
						<a class="fs-btn fs-btn--primary" href="<?php echo esc_url( get_permalink( $job ) ); ?>"><?php echo esc_html( $d['btn_label'] ); ?><span class="fs-visually-hidden">: <?php echo esc_html( get_the_title( $job ) ); ?></span></a>
						<?php endif; ?>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<?php echo fs_el( 'p', $d['leer'], 'class="fs-jobs__leer"' ); ?>
			<?php endif; ?>
			<?php if ( fs_has( $d, 'initiativ' ) && fs_has( $d, 'ziel' ) ) : ?>
			<div class="fs-center fs-mt-8"><a class="fs-btn fs-btn--lg <?php echo $jobs ? 'fs-btn--outline' : 'fs-btn--primary'; ?>" href="<?php echo esc_url( fs_link( $d['ziel'] ) ); ?>"><?php echo esc_html( $d['initiativ'] ); ?></a></div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Einzelansicht einer Stelle. Die Beschreibung läuft bewusst NICHT durch
 * the_content: WP Job Manager hängt dort seine eigene Stellenausgabe an,
 * die neben diesem Block doppelt stünde.
 */
function fs_render_stelle( $a ) {
	$d    = fs_attrs( $a, array( 'form_heading' => 'Jetzt bewerben' ) );
	$post = fs_context_post();
	if ( ! $post || 'job_listing' !== $post->post_type ) {
		return fs_editor_hint( 'Stelle', 'Zeigt hier Eckdaten, Beschreibung und Bewerbungsformular der geöffneten Stellenanzeige.' );
	}
	$eck = fs_stelle_eckdaten( $post );
	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-stelle">
			<?php if ( $eck ) : ?>
			<ul class="fs-job__eck fs-job__eck--gross">
				<?php foreach ( $eck as $icon => $wert ) : ?><li><?php echo fs_icon( $icon, 18 ); ?> <?php echo esc_html( $wert ); ?></li><?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<div class="fs-rich"><?php echo wp_kses_post( wpautop( (string) $post->post_content ) ); ?></div>
		</div>
	</section>
	<section class="fs-section fs-surface-brand" id="kontaktformular">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'h2', $d['form_heading'] ), 'class="fs-head"' ); ?>
			<div class="fs-contactform">
				<?php echo fs_formular( 'bewerbung', array( 'stelle' => get_the_title( $post ), 'stelle_id' => $post->ID ) ); ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
