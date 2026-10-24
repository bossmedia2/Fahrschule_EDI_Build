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
	) );
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	/* Absätze im Textfeld durch Leerzeilen getrennt (PDF-Abschnitt F1: vier Absätze). */
	$paragraphs = array_filter( array_map( 'trim', preg_split( '/\R\s*\R/u', (string) $d['text'] ) ) );
	ob_start(); ?>
	<section class="fs-vhero" data-pdf-section="F1">
		<video class="fs-vhero__media" autoplay muted loop playsinline preload="metadata" aria-label="Imagevideo der Fahrschule edi">
			<source src="<?php echo esc_url( $d['video_url'] ); ?>" type="video/mp4">
		</video>
		<div class="fs-vhero__overlay"></div>
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
	) );
	$photos = array( 'Duisburg' => 'loc_duisburg', 'Gelsenkirchen' => 'loc_gelsenkirchen', 'Essen' => 'loc_essen' );
	$hx     = fahrschule_pdf_show_head( $d ) ? 'h3' : 'h2'; // Gliederung ohne Sprung h1 -> h3
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-pdf-section="F8">
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
					<div class="fs-loc__img"><img src="<?php echo esc_url( fs_media( $photos[ $city ] ) ); ?>" alt="Fahrschule edi Standort <?php echo esc_attr( $city ); ?> – Außenansicht" width="600" height="400" loading="lazy" decoding="async"></div>
					<div class="fs-card__body">
						<<?php echo $hx; ?> class="fs-loc__title"><?php echo esc_html( $city ); ?></<?php echo $hx; ?>>
						<div class="fs-loc__line"><?php echo fs_icon( 'map-pin', 20 ); ?><span><?php echo esc_html( $loc['street'] ); ?><br><?php echo esc_html( $loc['zip'] ); ?></span></div>
						<div class="fs-loc__line"><?php echo fs_icon( 'phone', 20 ); ?><span class="fs-visually-hidden">Telefon:</span> <a href="tel:<?php echo esc_attr( fs_tel( $loc['phone'] ) ); ?>"><?php echo esc_html( $loc['phone'] ); ?></a></div>
						<div class="fs-loc__line"><?php echo fs_icon( 'mail', 20 ); ?><span class="fs-visually-hidden">E-Mail:</span> <a href="mailto:<?php echo esc_attr( $loc['email'] ); ?>"><?php echo esc_html( $loc['email'] ); ?></a></div>
						<div class="fs-loc__line"><?php echo fs_icon( 'clock', 20 ); ?><span><?php echo fs_nl2br( fahrschule_location_hours_text( $city ) ); ?></span></div>
						<a class="fs-btn fs-btn--primary fs-btn--block fs-mt-6" href="<?php echo esc_url( fs_url( 'kontakt' ) ); ?>">Kontakt</a>
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
		'heading'  => 'Folgen Sie uns auf Instagram',
		'highlight'=> 'Instagram',
		'subtitle' => 'Erleben Sie die beste Fahrschule im Ruhrgebiet direkt in Ihrem Feed. Tipps, Erfolgsgeschichten und Behind-the-Scenes.',
		'url'      => 'https://www.instagram.com/fahrschuleedi/',
		'cap_1'    => 'Endlich bestanden! Herzlichen Glückwunsch an alle neuen Fahrerinnen und Fahrer.',
		'cap_2'    => 'Moderne Fahrzeuge, moderne Ausbildung.',
		'cap_3'    => 'Team edi – Ihre Ansprechpartner im Ruhrgebiet.',
		'cap_4'    => 'Motorradtraining – Sicherheit an erster Stelle.',
		'cap_5'    => 'Klasse C erfolgreich bestanden.',
		'cap_6'    => 'Bei uns lernen Sie nicht nur fahren.',
	) );
	/*
	 * Die Like-/Kommentarzahlen standen hier frei erfunden im Code (234 Likes
	 * usw.). Erfundene Reichweitenzahlen sind Werbung mit falschen Angaben -
	 * sie sind ersatzlos entfallen. Es bleiben Bild und Bildtext.
	 */
	$posts = array();
	foreach ( array( 'ig_1', 'ig_2', 'ig_3', 'ig_4', 'ig_5', 'ig_6' ) as $i => $key ) {
		if ( ! fs_has_media( $key ) ) { continue; }
		$posts[] = array( fs_media( $key ), $d[ 'cap_' . ( $i + 1 ) ] );
	}
	$heading = fs_highlight( $d['heading'], $d['highlight'] );

	/*
	 * Ohne hinterlegte Beiträge entfällt der Slider und es bleibt der reine
	 * Verweis auf das echte Profil - kein leerer Rahmen, keine Platzhalter.
	 */
	if ( ! $posts ) {
		ob_start(); ?>
		<section class="fs-section fs-surface-light">
			<div class="fs-container fs-z">
				<div class="fs-head">
					<div class="fs-ig__head"><?php echo fs_icon( 'instagram', 32 ); ?><?php echo fs_el( 'h2', '', '', $heading ); ?></div>
					<?php echo fs_el( 'p', $d['subtitle'] ); ?>
				</div>
				<div class="fs-center">
					<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( $d['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo fs_icon( 'instagram', 20 ); ?> Besuchen Sie uns auf Instagram</a>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-fs-ig>
		<div class="fs-container fs-wide" style="margin-inline:auto;">
			<div class="fs-head">
				<div class="fs-ig__head"><?php echo fs_icon( 'instagram', 32 ); ?><?php echo fs_el( 'h2', '', '', $heading ); ?></div>
				<?php echo fs_el( 'p', $d['subtitle'] ); ?>
			</div>
			<div class="fs-ig__track" id="fs-ig-track" aria-live="polite" data-fs-ig-track
				data-posts='<?php echo esc_attr( wp_json_encode( $posts ) ); ?>'><?php
				// Die ersten drei Beiträge werden serverseitig ausgegeben: dadurch sind
				// Inhalte für Suchmaschinen sichtbar und ohne JavaScript nutzbar.
				for ( $i = 0; $i < min( 3, count( $posts ) ); $i++ ) :
					$p = $posts[ $i ]; ?>
					<figure class="fs-ig__post">
						<span class="fs-ig__badge">Instagram</span>
						<img src="<?php echo esc_url( $p[0] ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $p[1] ) ); ?>" width="500" height="500" loading="lazy" decoding="async">
						<figcaption class="fs-ig__cap">
							<p><?php echo esc_html( $p[1] ); ?></p>
						</figcaption>
					</figure>
				<?php endfor; ?></div>
			<div class="fs-ig__nav">
				<button type="button" class="fs-ig__arrow" data-fs-ig-prev aria-label="Vorherige Beiträge" aria-controls="fs-ig-track"><?php echo fs_icon( 'chevron-left', 24 ); ?></button>
				<div class="fs-ig__dots" data-fs-ig-dots></div>
				<button type="button" class="fs-ig__arrow" data-fs-ig-next aria-label="Nächste Beiträge" aria-controls="fs-ig-track"><?php echo fs_icon( 'chevron-right', 24 ); ?></button>
			</div>
			<div class="fs-center fs-mt-8">
				<a class="fs-btn fs-btn--lg fs-btn--primary" href="<?php echo esc_url( $d['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo fs_icon( 'instagram', 20 ); ?> Besuchen Sie uns auf Instagram</a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
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
	 * Drei Gruppen laut PDF, dort je mit „(Fotos)" markiert. Fotos aus der
	 * Medien-Registry; solange keine hinterlegt sind, zeigen wir bewusst die
	 * Initialen statt eines fremden Stockfotos. Bei der Geschäftsleitung stehen
	 * die Namen aus dem Impressum („Vertreten durch").
	 */
	$photos = array(
		'Büro & Ansprechpartner' => 'team_buero',
		'Geschäftsleitung'       => 'team_geschaeftsleitung',
		'Dozenten & Ausbilder'   => 'team_dozenten',
	);
	$names = array(
		'Geschäftsleitung' => array( 'Gülsüm Alkan', 'Robert Peter Fiedler', 'Rainer Fritz Erich Grunwald' ),
	);
	$initials = static function ( $group ) {
		$words = preg_split( '/\s+/u', trim( str_replace( '&', ' ', $group ) ) );
		$first = $words[0];
		$last  = end( $words );
		return mb_strtoupper( mb_substr( $first, 0, 1 ) . ( $last !== $first ? mb_substr( $last, 0, 1 ) : '' ) );
	};
	$hx = fahrschule_pdf_show_head( $d ) ? 'h3' : 'h2'; // Gliederung ohne Sprung h1 -> h3
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
			<div class="fs-grid fs-grid--3 fs-mt-8" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $f9['groups'] as $group => $where ) : ?>
				<div class="fs-teamcard">
					<?php if ( fs_has_media( $photos[ $group ] ) ) : ?>
						<img class="fs-teamcard__photo" src="<?php echo esc_url( fs_media( $photos[ $group ] ) ); ?>" alt="<?php echo esc_attr( $group . ' der Fahrschule edi' ); ?>" width="400" height="400" loading="lazy" decoding="async">
					<?php else : ?>
						<div class="fs-teamcard__avatar" aria-hidden="true"><?php echo esc_html( $initials( $group ) ); ?></div>
					<?php endif; ?>
					<div class="fs-teamcard__body">
						<<?php echo $hx; ?>><?php echo esc_html( $group ); ?></<?php echo $hx; ?>>
						<p><?php echo esc_html( $where ); ?></p>
						<?php if ( ! empty( $names[ $group ] ) ) : ?>
						<ul class="fs-teamcard__names"><?php foreach ( $names[ $group ] as $n ) : ?><li><?php echo esc_html( $n ); ?></li><?php endforeach; ?></ul>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
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
		'address'  => fahrschule_pdf_location( 'Duisburg' )['street'],
		'zip'      => fahrschule_pdf_location( 'Duisburg' )['zip'],
		'phone'    => fahrschule_pdf_location( 'Duisburg' )['phone'],
		'email'    => fahrschule_pdf_location( 'Duisburg' )['email'],
		'hours'    => str_replace( "\n", ', ', fahrschule_location_hours_text( 'Duisburg' ) ),
		'contact'  => '',
	) );
	$herocls = 'fs-lochero fs-surface-brand';
	ob_start(); ?>
	<section class="<?php echo esc_attr( $herocls ); ?>">
		<div class="fs-container fs-lochero__inner">
			<div class="fs-lochero__grid">
				<div class="fs-stack" style="gap:1.5rem;">
					<span class="fs-badge"><?php echo fs_icon( $d['icon'], 16 ); ?> <?php echo esc_html( $d['badge'] ); ?></span>
					<?php echo fs_el( 'h1', $d['heading'] ); ?>
					<?php echo fs_el( 'p', $d['lead'], 'class="lead"' ); ?>
					<div class="fs-loc__line"><?php echo fs_icon( 'map-pin', 20 ); ?><div><?php echo fs_el( 'p', $d['address'], 'style="font-weight:600;color:var(--c-heading);margin:0;"' ); ?><?php echo fs_el( 'p', $d['zip'], 'style="margin:0;"' ); ?></div></div>
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
					<?php $fs_schnell_state = isset( $_GET['fs_schnell'] ) ? $_GET['fs_schnell'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur Anzeige, keine Aktion ?>
					<?php if ( 'ok' === $fs_schnell_state ) : ?>
						<p role="status" style="color:var(--c-heading);font-weight:600;">Danke! Wir melden uns kurzfristig bei Ihnen.</p>
					<?php else : ?>
					<?php if ( 'error' === $fs_schnell_state ) : ?>
						<p role="alert" style="color:var(--c-heading);font-weight:600;margin-bottom:1rem;">Bitte prüfen Sie Ihre Angaben (Name und gültige E-Mail-Adresse sind erforderlich) und versuchen Sie es erneut.</p>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="fahrschule_schnellanmeldung">
						<input type="hidden" name="fs_stadt" value="<?php echo esc_attr( $d['city'] ); ?>">
						<input type="hidden" name="fs_back" value="<?php echo esc_url( fs_url( strtolower( $d['city'] ) ) ); ?>">
						<?php wp_nonce_field( 'fahrschule_schnellanmeldung' ); ?>
						<p style="position:absolute;left:-9999px;" aria-hidden="true">
							<label>Bitte leer lassen<input type="text" name="fs_hp" tabindex="-1" autocomplete="off"></label>
						</p>
						<label class="fs-visually-hidden" for="fs-locform-name-<?php echo esc_attr( $d['city'] ); ?>">Name</label>
						<input type="text" id="fs-locform-name-<?php echo esc_attr( $d['city'] ); ?>" name="fs_name" placeholder="Name" required>
						<label class="fs-visually-hidden" for="fs-locform-email-<?php echo esc_attr( $d['city'] ); ?>">E-Mail</label>
						<input type="email" id="fs-locform-email-<?php echo esc_attr( $d['city'] ); ?>" name="fs_email" placeholder="E-Mail" required>
						<label class="fs-visually-hidden" for="fs-locform-phone-<?php echo esc_attr( $d['city'] ); ?>">Telefon</label>
						<input type="tel" id="fs-locform-phone-<?php echo esc_attr( $d['city'] ); ?>" name="fs_phone" placeholder="Telefon">
						<label class="fs-visually-hidden" for="fs-locform-klasse-<?php echo esc_attr( $d['city'] ); ?>">Führerscheinklasse</label>
						<select id="fs-locform-klasse-<?php echo esc_attr( $d['city'] ); ?>" name="fs_klasse">
							<option value="">Führerscheinklasse wählen</option>
							<option value="b">Klasse B</option>
							<option value="c">Klasse C</option>
							<option value="ce">Klasse CE</option>
							<option value="d">Klasse D</option>
							<option value="de">Klasse DE</option>
						</select>
						<button type="submit" class="fs-btn fs-btn--primary-white fs-btn--block">Kostenloses Beratungsgespräch</button>
					</form>
					<?php endif; ?>
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
		'img1'    => fs_media( 'loc_duisburg' ),
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
		'heading' => 'Schreiben Sie uns',
		'lead'    => 'Fragen zur Ausbildung, den Kosten oder einem Standort? Wir melden uns kurzfristig bei Ihnen zurück.',
	) );
	$back  = function_exists( 'fahrschule_current_url' ) ? fahrschule_current_url() : home_url( '/' );
	$state = isset( $_GET['fs_kontakt'] ) ? $_GET['fs_kontakt'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur Anzeige, keine Aktion
	ob_start(); ?>
	<section class="fs-section fs-surface-light" id="kontaktformular">
		<div class="fs-container fs-z">
			<?php echo fs_wrap( 'div', fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['lead'] ), 'class="fs-head"' ); ?>
			<?php if ( 'ok' === $state ) : ?>
				<p role="status" class="fs-contactform" style="text-align:center;font-weight:600;color:var(--c-card-heading);">Danke für Ihre Nachricht! Wir melden uns kurzfristig bei Ihnen.</p>
			<?php else : ?>
				<?php if ( 'error' === $state ) : ?>
					<p role="alert" style="text-align:center;font-weight:600;color:var(--c-heading);">Bitte prüfen Sie Ihre Angaben (Name, gültige E-Mail-Adresse und Nachricht sind erforderlich) und versuchen Sie es erneut.</p>
				<?php endif; ?>
				<form class="fs-contactform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="fahrschule_kontakt">
					<input type="hidden" name="fs_back" value="<?php echo esc_url( $back ); ?>">
					<?php wp_nonce_field( 'fahrschule_kontakt' ); ?>
					<p style="position:absolute;left:-9999px;" aria-hidden="true">
						<label>Bitte leer lassen<input type="text" name="fs_hp" tabindex="-1" autocomplete="off"></label>
					</p>
					<div class="fs-field">
						<label for="fs-kontakt-name">Name *</label>
						<input type="text" id="fs-kontakt-name" name="fs_name" required>
					</div>
					<div class="fs-field">
						<label for="fs-kontakt-email">E-Mail *</label>
						<input type="email" id="fs-kontakt-email" name="fs_email" required>
					</div>
					<div class="fs-field">
						<label for="fs-kontakt-phone">Telefon</label>
						<input type="tel" id="fs-kontakt-phone" name="fs_phone">
					</div>
					<div class="fs-field">
						<label for="fs-kontakt-stadt">Standort</label>
						<select id="fs-kontakt-stadt" name="fs_stadt">
							<option value="">Egal / noch unklar</option>
							<option value="Duisburg">Duisburg</option>
							<option value="Essen">Essen</option>
							<option value="Gelsenkirchen">Gelsenkirchen</option>
						</select>
					</div>
					<div class="fs-field">
						<label for="fs-kontakt-message">Nachricht *</label>
						<textarea id="fs-kontakt-message" name="fs_message" required></textarea>
					</div>
					<div class="fs-field">
						<label style="display:flex;align-items:flex-start;gap:.5rem;font-weight:400;">
							<input type="checkbox" name="fs_privacy" value="1" required style="width:auto;margin-top:.2rem;">
							<span>Mit dem Absenden meiner Daten stimme ich der <a href="<?php echo esc_url( fs_url( 'datenschutz' ) ); ?>" style="text-decoration:underline;">Datenschutzerklärung</a> zu. *</span>
						</label>
					</div>
					<button type="submit" class="fs-btn fs-btn--lg fs-btn--primary fs-btn--block">Nachricht senden</button>
				</form>
			<?php endif; ?>
		</div>
	</section>
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

function fs_legal_impressum() { ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container fs-legal">
			<div class="fs-head" style="text-align:left;margin-bottom:2rem;"><h2><?php echo fs_icon( 'shield', 32, 'fs-icon-pink' ); ?> Angaben gemäß § 5 TMG</h2></div>
			<div class="fs-legal__card">
				<div class="fs-legal__block">
					<h2>Anbieterangaben</h2>
					<p><strong>Fahrschule &amp; Fahrlehrerfachschule edi GmbH</strong><br>Friedrich Ebert Str. 346<br>47139 Duisburg<br>Deutschland</p>
				</div>
				<div class="fs-legal__block">
					<h2>Vertreten durch</h2>
					<p>Gülsüm Alkan, Robert Peter Fiedler, Rainer Fritz Erich Grunwald (Geschäftsführung)</p>
				</div>
				<div class="fs-legal__block">
					<h2>Kontaktdaten</h2>
					<p><strong>Telefon:</strong> +49 (0)203 57 85 703</p>
					<p><strong>E-Mail:</strong> <a href="mailto:info@fahrschule-edi.de">info@fahrschule-edi.de</a></p>
				</div>
				<div class="fs-legal__block">
					<h2>Registereintrag</h2>
					<p><strong>Register:</strong> Handelsregister<br><strong>Register Ort:</strong> Duisburg<br><strong>Registernummer:</strong> HRB-Nr. 7858<br><strong>Umsatzsteuernummer:</strong> DE195505422</p>
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
		} else {
			$d['heading'] = get_the_title();
		}
		if ( $page && '' === $d['lead'] && ! empty( $page['lead'] ) ) { $d['lead'] = $page['lead']; }
	}
	$heading = fs_highlight( $d['heading'], $d['highlight'] );
	ob_start(); ?>
	<section class="fs-pagehero fs-surface-brand"<?php echo $pdf_id ? ' data-pdf-section="' . esc_attr( $pdf_id ) . '"' : ''; ?>>
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
	) );
	/* Seite => Icon. Label, Überschrift und erster Satz kommen wortgleich aus
	   dem PDF-Abschnitt der jeweiligen Unterseite (kein eigener Teasertext).
	   Jeder Teaser steht für einen eigenen Abschnitt – daher h2 statt h3. */
	$teasers = array(
		'klassen'            => 'award',
		'leistungen'         => 'check-circle',
		'berufskraftfahrer'  => 'truck',
		'asf-fes'            => 'shield',
		'moderne-ausbildung' => 'zap',
		'team'               => 'users',
	);
	$map = fahrschule_page_map();
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
			<div class="fs-teasers fs-teasers--3">
				<?php foreach ( $teasers as $key => $icon ) :
					$pdf = ( isset( $map[ $key ]['pdf'] ) ) ? fahrschule_pdf( $map[ $key ]['pdf'] ) : array();
					if ( ! $pdf ) { continue; } ?>
				<a class="fs-teaser" href="<?php echo esc_url( fs_url( $key ) ); ?>" data-pdf-section="<?php echo esc_attr( $pdf['id'] ); ?>">
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
	$parent = $d['bereich'] ? $d['bereich'] : fahrschule_current_key();
	if ( '' === $parent ) { return ''; }
	$map    = fahrschule_page_map();

	$children = array();
	foreach ( $map as $key => $page ) {
		if ( isset( $page['parent'] ) && $page['parent'] === $parent ) {
			$children[ $key ] = $page;
		}
	}
	if ( ! $children ) { return ''; }

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
		'eyebrow'        => '',
		'heading'        => 'Das Wichtigste auf einen Blick',
		'alter'          => '',
		'dauer'          => '',
		'preis'          => '',
		'voraussetzungen'=> '',
		'ablauf'         => '',
		'foerderung'     => '',
		'hinweis'        => '',
	) );

	// Kennzahlen-Kacheln: Beschriftung => Wert.
	$facts = array(
		'Mindestalter' => $d['alter'],
		'Ausbildungsdauer' => $d['dauer'],
		'Richtpreis' => $d['preis'],
	);
	$facts = array_filter( $facts, static function ( $v ) { return '' !== trim( (string) $v ); } );

	// Textblöcke: Überschrift => Inhalt.
	$sections = array(
		'Voraussetzungen' => $d['voraussetzungen'],
		'Ablauf der Ausbildung' => $d['ablauf'],
		'Förderung & Finanzierung' => $d['foerderung'],
	);
	$sections = array_filter( $sections, static function ( $v ) { return '' !== trim( (string) $v ); } );

	if ( ! $facts && ! $sections && ! fs_has( $d, 'hinweis' ) ) { return ''; }

	ob_start(); ?>
	<section class="fs-section fs-surface-light">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] ), 'class="fs-head"' ); ?>

			<?php if ( $facts ) : ?>
			<div class="fs-grid fs-grid--3" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $facts as $label => $value ) : ?>
				<div class="fs-stat">
					<div class="big"><?php echo esc_html( $value ); ?></div>
					<p><?php echo esc_html( $label ); ?></p>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( $sections ) : ?>
			<div class="fs-grid fs-grid--2 fs-mt-8" style="max-width:64rem;margin-inline:auto;">
				<?php foreach ( $sections as $title => $text ) : ?>
				<div class="fs-card"><div class="fs-card__body">
					<h3><?php echo esc_html( $title ); ?></h3>
					<?php echo fs_paragraphs( $text ); ?>
				</div></div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php echo fs_el( 'p', $d['hinweis'], 'class="fs-center fs-mt-8" style="color:var(--c-text);max-width:42rem;margin-inline:auto;"' ); ?>
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
function fs_render_fahrlehrer_finder( $a ) {
	$d = fs_attrs( $a, array(
		'eyebrow'  => 'Unser Team',
		'heading'  => 'Finden Sie Ihren Fahrlehrer',
		'subtitle' => 'Filtern Sie nach Standort und Führerscheinklasse.',
		'people'   => array(),
		'empty'    => 'Für diese Auswahl ist gerade niemand hinterlegt.',
	) );

	$people = fs_rows( $d['people'], 5 );
	if ( ! $people ) { return ''; }

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

			<div class="fs-teamgrid" data-fs-team-grid>
				<?php foreach ( $people as $p ) :
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
						<?php echo fs_el( 'h3', $name ); ?>
						<?php echo fs_el( 'p', $rolle, 'class="fs-teamcard__names"' ); ?>
						<p class="fs-teamcard__meta">
							<?php if ( '' !== trim( $ort ) ) : ?><span><?php echo fs_icon( 'map-pin', 14 ); ?> <?php echo esc_html( $ort ); ?></span><?php endif; ?>
							<?php if ( '' !== trim( $kl ) ) : ?><span><?php echo fs_icon( 'award', 14 ); ?> <?php echo esc_html( $kl ); ?></span><?php endif; ?>
						</p>
					</div>
				</article>
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
	if ( ! $kats ) { return ''; }

	$bilder = array();
	foreach ( $kats as $kat ) {
		list( $label, $prefix ) = $kat;
		$prefix = sanitize_title( $prefix );
		if ( '' === $prefix ) { continue; }

		// Anhänge über den Namenspräfix holen. Mehr als 200 je Kategorie wäre
		// für eine Website-Galerie ohnehin zu viel.
		$posts = get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => 200,
			'orderby'        => 'name',
			'order'          => 'ASC',
			'name__like'     => 'edi-galerie-' . $prefix . '-',
			'no_found_rows'  => true,
		) );
		foreach ( $posts as $post ) {
			// name__like kennt WordPress nicht als Standardfilter - deshalb
			// hier noch einmal prüfen, statt einem Zufallstreffer zu trauen.
			if ( 0 !== strpos( $post->post_name, 'edi-galerie-' . $prefix . '-' ) ) { continue; }
			$url = wp_get_attachment_image_url( $post->ID, 'large' );
			if ( ! $url ) { continue; }
			$bilder[] = array(
				'kat'  => $prefix,
				'url'  => $url,
				'full' => wp_get_attachment_image_url( $post->ID, 'full' ),
				'alt'  => (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
			);
		}
	}
	if ( ! $bilder ) { return ''; }

	ob_start(); ?>
	<section class="fs-section fs-surface-light" data-fs-galerie data-batch="<?php echo esc_attr( $batch ); ?>">
		<div class="fs-container">
			<?php echo fs_wrap( 'div', fs_el( 'span', $d['eyebrow'], 'class="fs-eyebrow"' )
				. fs_el( 'h2', $d['heading'] )
				. fs_el( 'p', $d['subtitle'] ), 'class="fs-head"' ); ?>

			<?php if ( count( $kats ) > 1 ) : ?>
			<div class="fs-galfilter" role="group" aria-label="Galerie filtern">
				<button type="button" class="fs-pill is-active" data-kat="">Alle</button>
				<?php foreach ( $kats as $kat ) : ?>
				<button type="button" class="fs-pill" data-kat="<?php echo esc_attr( sanitize_title( $kat[1] ) ); ?>"><?php echo esc_html( $kat[0] ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<div class="fs-galgrid" data-fs-gal-grid>
				<?php foreach ( $bilder as $i => $b ) : ?>
				<button type="button" class="fs-galitem" data-kat="<?php echo esc_attr( $b['kat'] ); ?>"
					data-full="<?php echo esc_url( $b['full'] ); ?>"
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
