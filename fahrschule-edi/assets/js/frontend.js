/**
 * Fahrschule edi – Frontend-Interaktionen (Vanilla JS, ohne Abhängigkeiten).
 * Navigation, FAQ-Akkordeon, Rechner, Distanz-/Standort-Rechner,
 * Instagram-Slider und TikTok-Badge.
 */
( function () {
	'use strict';

	var fmt = function ( n ) {
		return Math.round( n ).toString().replace( /\B(?=(\d{3})+(?!\d))/g, '.' );
	};
	var on = function ( elm, ev, cb ) { if ( elm ) { elm.addEventListener( ev, cb ); } };

	document.addEventListener( 'DOMContentLoaded', function () {

		/* ---------- Navigation: Scroll-Zustand ---------- */
		var nav = document.querySelector( '[data-fs-nav]' );
		if ( nav ) {
			var onScroll = function () { nav.classList.toggle( 'is-scrolled', window.scrollY > 10 ); };
			onScroll();
			window.addEventListener( 'scroll', onScroll, { passive: true } );
		}

		/* ---------- Hero-Video ----------
		 * Kein autoplay im Markup: gestartet wird nur, wenn der Nutzer keine
		 * reduzierte Bewegung wünscht - sonst bleibt das Standbild (Poster
		 * und Hintergrund; das Video blendet main.css dann aus). Wechselt die
		 * Einstellung, hält das Video an bzw. läuft wieder. */
		var heroVideos = document.querySelectorAll( '[data-fs-hero-video]' );
		if ( heroVideos.length ) {
			var still = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : null;
			var heroSync = function () {
				heroVideos.forEach( function ( v ) {
					if ( still && still.matches ) { v.pause(); }
					else { var p = v.play(); if ( p && p.catch ) { p.catch( function () {} ); } }
				} );
			};
			heroSync();
			if ( still && still.addEventListener ) { still.addEventListener( 'change', heroSync ); }
		}

		/* ---------- Mobile Drawer ---------- */
		var burger  = document.querySelector( '[data-fs-burger]' );
		var drawer  = document.querySelector( '[data-fs-drawer]' );
		var overlay = document.querySelector( '[data-fs-overlay]' );
		var toggleDrawer = function ( open ) {
			if ( ! drawer ) { return; }
			drawer.classList.toggle( 'is-open', open );
			if ( overlay ) { overlay.classList.toggle( 'is-open', open ); }
			if ( burger ) {
				burger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				burger.setAttribute( 'aria-label', open ? 'Menü schließen' : 'Menü öffnen' );
			}
			document.body.style.overflow = open ? 'hidden' : '';
		};
		on( burger, 'click', function () { toggleDrawer( ! drawer.classList.contains( 'is-open' ) ); } );
		on( overlay, 'click', function () { toggleDrawer( false ); } );
		// Mehrere aufklappbare Gruppen im mobilen Menü.
		document.querySelectorAll( '[data-fs-subtoggle]' ).forEach( function ( toggle ) {
			var group = toggle.parentNode;
			var sub   = group ? group.querySelector( '[data-fs-sub]' ) : null;
			on( toggle, 'click', function () {
				if ( ! sub ) { return; }
				var open = sub.classList.toggle( 'is-open' );
				toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
		} );

		// Menü mit Escape schließen und Fokus zurückgeben.
		on( document, 'keydown', function ( e ) {
			if ( 'Escape' === e.key && drawer && drawer.classList.contains( 'is-open' ) ) {
				toggleDrawer( false );
				if ( burger ) { burger.focus(); }
			}
		} );

		// Wechsel auf Desktop-Breite: Drawer sauber zurücksetzen.
		if ( window.matchMedia ) {
			var mq = window.matchMedia( '(min-width: 1240px)' );
			var onChange = function ( ev ) { if ( ev.matches ) { toggleDrawer( false ); } };
			if ( mq.addEventListener ) { mq.addEventListener( 'change', onChange ); }
			else if ( mq.addListener ) { mq.addListener( onChange ); }
		}

		/* ---------- Desktop-Dropdowns ----------
		 * Sichtbar werden die Flyouts per CSS (:hover/:focus-within). Die
		 * zugehörigen Buttons meldeten dabei dauerhaft aria-expanded="false" –
		 * Screenreader bekamen den geöffneten Zustand also nie mit (SC 4.1.2).
		 * Hier wird der Zustand nachgeführt und zusätzlich Klick/Escape
		 * bedienbar gemacht, damit der Button kein reines No-op bleibt. */
		document.querySelectorAll( '.fs-nav__drop' ).forEach( function ( drop ) {
			var toggle = drop.querySelector( 'button.fs-nav__toplink' );
			var menu   = drop.querySelector( '.fs-nav__menu' );
			if ( ! menu ) { return; }
			var setState = function ( open ) {
				drop.classList.toggle( 'is-open', open );
				if ( toggle ) { toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' ); }
			};
			// Zeiger und Tastaturfokus spiegeln, was das CSS ohnehin schon zeigt.
			on( drop, 'mouseenter', function () { setState( true ); } );
			on( drop, 'mouseleave', function () { setState( false ); } );
			on( drop, 'focusin', function () { setState( true ); } );
			on( drop, 'focusout', function () {
				if ( ! drop.contains( document.activeElement ) ) { setState( false ); }
			} );
			if ( toggle ) {
				on( toggle, 'click', function ( e ) {
					e.preventDefault();
					setState( 'true' !== toggle.getAttribute( 'aria-expanded' ) );
				} );
			}
			on( drop, 'keydown', function ( e ) {
				if ( 'Escape' === e.key && drop.classList.contains( 'is-open' ) ) {
					setState( false );
					if ( toggle ) { toggle.focus(); }
				}
			} );
		} );

		/* ---------- FAQ-Akkordeon ---------- */
		document.querySelectorAll( '[data-fs-faq] .fs-faq__item' ).forEach( function ( item ) {
			var q = item.querySelector( '.fs-faq__q' );
			on( q, 'click', function () {
				var open = item.classList.toggle( 'is-open' );
				if ( q ) { q.setAttribute( 'aria-expanded', open ? 'true' : 'false' ); }
			} );
		} );

		/* ---------- Generische Pill-Gruppen ---------- */
		function bindPills( root, onChange ) {
			root.querySelectorAll( '[data-fs-group]' ).forEach( function ( group ) {
				group.querySelectorAll( '.fs-pill' ).forEach( function ( btn ) {
					on( btn, 'click', function () {
						group.querySelectorAll( '.fs-pill' ).forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
						btn.classList.add( 'is-active' );
						onChange();
					} );
				} );
			} );
		}
		function activeVal( root, name ) {
			var a = root.querySelector( '[data-fs-group="' + name + '"] .fs-pill.is-active' );
			return a ? a.getAttribute( 'data-val' ) : null;
		}

		/* ---------- Führerschein-Rechner (Startseite) ---------- */
		document.querySelectorAll( '[data-fs-calc]' ).forEach( function ( calc ) {
			var prices = {};
			try { prices = JSON.parse( calc.getAttribute( 'data-prices' ) ); } catch ( e ) {}
			var hoursEl = calc.querySelector( '[data-fs-hours]' );
			var priceEl = calc.querySelector( '[data-fs-price]' );
			function update() {
				var cls = activeVal( calc, 'class' ) || 'B';
				var p = prices[ cls ];
				if ( ! p ) { return; }
				if ( hoursEl ) { hoursEl.textContent = p[2] + ' Stunden'; }
				if ( priceEl ) { priceEl.textContent = '€' + fmt( p[0] ) + ' - €' + fmt( p[1] ); }
			}
			bindPills( calc, update );
			update();
		} );

		/* ---------- Standort-Rechner (Slider) ---------- */
		document.querySelectorAll( '[data-fs-loccalc]' ).forEach( function ( calc ) {
			var prices = {};
			try { prices = JSON.parse( calc.getAttribute( 'data-prices' ) ); } catch ( e ) {}
			var driving  = calc.querySelector( '[data-fs-driving]' );
			var theory   = calc.querySelector( '[data-fs-theory]' );
			var dVal     = calc.querySelector( '[data-fs-driving-val]' );
			var tVal     = calc.querySelector( '[data-fs-theory-val]' );
			var totalEl  = calc.querySelector( '[data-fs-loctotal]' );
			function update() {
				var cls  = activeVal( calc, 'class' ) || 'b';
				var type = activeVal( calc, 'type' ) || 'basic';
				var perHour = ( prices[ cls ] && prices[ cls ][ type ] ) ? prices[ cls ][ type ] : 65;
				var dh = driving ? parseInt( driving.value, 10 ) : 40;
				var th = theory ? parseInt( theory.value, 10 ) : 14;
				if ( dVal ) { dVal.textContent = dh; }
				if ( tVal ) { tVal.textContent = th; }
				var total = dh * perHour + th * 15;
				if ( totalEl ) { totalEl.textContent = '€' + fmt( total ); }
			}
			bindPills( calc, update );
			on( driving, 'input', update );
			on( theory, 'input', update );
			update();
		} );

		/* ---------- Distanz-Rechner ---------- */
		document.querySelectorAll( '[data-fs-distance]' ).forEach( function ( box ) {
			var range = box.querySelector( '[data-fs-dist-range]' );
			var val   = box.querySelector( '[data-fs-dist-val]' );
			var out   = box.querySelector( '[data-fs-dist-out]' );
			function paint() {
				var km = range ? parseInt( range.value, 10 ) : 5;
				if ( val ) { val.textContent = km + ' km'; }
				if ( out ) { out.textContent = Math.round( km * 3 ) + ' Minuten'; }
				// Kein Farbverlauf: Die Fortschrittsfarbe übernimmt accent-color aus dem CSS.
			}
			on( range, 'input', paint );
			paint();
		} );

		/* ---------- Social-Feed (Karussell) ----------
		 * Alle Kacheln stehen bereits im HTML (Editor und Seite ohne JS zeigen
		 * sie vollständig). Hier wird nur ein Fenster von drei Kacheln
		 * eingeblendet; die übrigen bekommen hidden. Die Reihenfolge im Fenster
		 * läuft über CSS order, damit das Blättern ringförmig bleibt, ohne das
		 * Markup umzubauen. */
		document.querySelectorAll( '[data-fs-ig]' ).forEach( function ( section ) {
			var track = section.querySelector( '[data-fs-ig-track]' );
			var dots  = section.querySelector( '[data-fs-ig-dots]' );
			if ( ! track ) { return; }
			var cards = [].slice.call( track.querySelectorAll( '.fs-ig__post' ) );
			var n = cards.length, current = 0, sichtbar = 3;
			if ( n <= sichtbar ) { return; }

			function render() {
				cards.forEach( function ( c, i ) {
					var off = ( i - current + n ) % n;
					c.hidden = off >= sichtbar;
					c.style.order = off;
				} );
				if ( dots ) {
					[].slice.call( dots.children ).forEach( function ( d, i ) {
						d.classList.toggle( 'is-active', i === current );
						d.setAttribute( 'aria-current', i === current ? 'true' : 'false' );
					} );
				}
			}
			if ( dots ) {
				dots.textContent = '';
				cards.forEach( function ( _, idx ) {
					var d = document.createElement( 'button' );
					d.type = 'button';
					d.className = 'fs-ig__dot';
					d.setAttribute( 'aria-label', 'Beitrag ' + ( idx + 1 ) + ' anzeigen' );
					d.addEventListener( 'click', function () { current = idx; render(); } );
					dots.appendChild( d );
				} );
			}
			on( section.querySelector( '[data-fs-ig-next]' ), 'click', function () { current = ( current + 1 ) % n; render(); } );
			on( section.querySelector( '[data-fs-ig-prev]' ), 'click', function () { current = ( current - 1 + n ) % n; render(); } );
			render();
		} );

		/* ---------- TikTok-Badge schließen ---------- */
		var badge = document.querySelector( '[data-fs-tiktok]' );
		if ( badge ) {
			try {
				if ( window.localStorage && localStorage.getItem( 'tiktok-badge-dismissed' ) ) {
					badge.style.display = 'none';
				}
			} catch ( e ) {}
			on( badge.querySelector( '[data-fs-tiktok-close]' ), 'click', function ( e ) {
				e.preventDefault(); e.stopPropagation();
				badge.style.display = 'none';
				try { localStorage.setItem( 'tiktok-badge-dismissed', 'true' ); } catch ( err ) {}
			} );
		}

		/* ---------- Cookie-Consent ---------- */
		var cookieBanner = document.querySelector( '[data-fs-cookiebanner]' );
		function fsSetConsent( value ) {
			var maxAge = 60 * 60 * 24 * 180; // 180 Tage
			document.cookie = 'fs_consent=' + value + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
		}
		function fsOpenCookieBanner() {
			if ( ! cookieBanner ) { return; }
			cookieBanner.removeAttribute( 'hidden' );
			cookieBanner.classList.add( 'is-open' );
			document.body.classList.add( 'fs-cookie-open' );
		}
		function fsCloseCookieBanner() {
			if ( ! cookieBanner ) { return; }
			cookieBanner.classList.remove( 'is-open' );
			cookieBanner.setAttribute( 'hidden', '' );
			document.body.classList.remove( 'fs-cookie-open' );
		}
		if ( cookieBanner ) {
			// Beim ersten Besuch rendert PHP das Banner bereits ohne hidden-Attribut -
			// den TikTok-Badge dann sofort mit ausblenden, nicht erst nach einem Klick.
			if ( ! cookieBanner.hasAttribute( 'hidden' ) ) { document.body.classList.add( 'fs-cookie-open' ); }
			cookieBanner.querySelectorAll( '[data-fs-consent]' ).forEach( function ( btn ) {
				on( btn, 'click', function () {
					fsSetConsent( btn.getAttribute( 'data-fs-consent' ) );
					fsCloseCookieBanner();
				} );
			} );
		}
		on( document.querySelector( '[data-fs-consent-reopen]' ), 'click', function ( e ) {
			e.preventDefault();
			fsOpenCookieBanner();
		} );

		/* ---------- Karte (Zwei-Klick) ----------
		 * Das iframe entsteht erst hier - im HTML steht nur die Adresse im
		 * data-Attribut. Bei Einwilligung „Alle akzeptieren" sofort, sonst
		 * auf Klick. Vorher geht keine Anfrage an OpenStreetMap. */
		function fsLoadKarte( box ) {
			if ( box.querySelector( 'iframe' ) ) { return; }
			var f = document.createElement( 'iframe' );
			f.src = box.getAttribute( 'data-src' );
			f.title = box.getAttribute( 'data-title' ) || 'Karte';
			f.loading = 'lazy';
			f.referrerPolicy = 'no-referrer';
			box.innerHTML = '';
			box.appendChild( f );
			box.classList.add( 'is-loaded' );
		}
		var consentAll = /(?:^|;\s*)fs_consent=all(?:;|$)/.test( document.cookie );
		document.querySelectorAll( '[data-fs-karte]' ).forEach( function ( box ) {
			if ( consentAll ) { fsLoadKarte( box ); return; }
			on( box.querySelector( '[data-fs-karte-load]' ), 'click', function () { fsLoadKarte( box ); } );
		} );

		/* ---------- Fahrlehrer-Finder ----------
		 * Ohne JavaScript bleiben schlicht alle Karten sichtbar - die
		 * Filterleiste ist dann wirkungslos, aber nichts ist unerreichbar. */
		document.querySelectorAll( '[data-fs-team]' ).forEach( function ( sec ) {
			var ort      = sec.querySelector( '[data-fs-team-ort]' );
			var klasse   = sec.querySelector( '[data-fs-team-klasse]' );
			var weiblich = sec.querySelector( '[data-fs-team-weiblich]' );
			var grid     = sec.querySelector( '[data-fs-team-grid]' );
			var leer     = sec.querySelector( '[data-fs-team-empty]' );
			if ( ! grid ) { return; }
			var cards = [].slice.call( grid.querySelectorAll( '.fs-teamcard' ) );

			function apply() {
				var vo = ort ? ort.value : '';
				var vk = klasse ? klasse.value : '';
				var vw = weiblich ? weiblich.checked : false;
				var sichtbar = 0;
				cards.forEach( function ( c ) {
					var kl = ( c.getAttribute( 'data-klassen' ) || '' )
						.split( ',' ).map( function ( x ) { return x.trim(); } );
					var ok = ( ! vo || c.getAttribute( 'data-ort' ) === vo )
						&& ( ! vk || kl.indexOf( vk ) !== -1 )
						&& ( ! vw || '1' === c.getAttribute( 'data-weiblich' ) );
					c.hidden = ! ok;
					if ( ok ) { sichtbar++; }
				} );
				// Gruppe ohne sichtbare Person: Überschrift mit ausblenden.
				[].slice.call( grid.querySelectorAll( '[data-fs-team-group]' ) ).forEach( function ( g ) {
					g.hidden = ! g.querySelector( '.fs-teamcard:not([hidden])' );
				} );
				if ( leer ) { leer.hidden = sichtbar > 0; }
			}
			on( ort, 'change', apply );
			on( klasse, 'change', apply );
			on( weiblich, 'change', apply );
		} );

		/* ---------- Foto-Galerie mit Lightbox ---------- */
		document.querySelectorAll( '[data-fs-galerie]' ).forEach( function ( sec ) {
			var grid  = sec.querySelector( '[data-fs-gal-grid]' );
			var more  = sec.querySelector( '[data-fs-gal-more]' );
			var box   = sec.querySelector( '[data-fs-lightbox]' );
			var bild  = sec.querySelector( '[data-fs-lb-img]' );
			if ( ! grid ) { return; }

			var batch   = parseInt( sec.getAttribute( 'data-batch' ), 10 ) || 24;
			var items   = [].slice.call( grid.querySelectorAll( '.fs-galitem' ) );
			var kat     = '';
			var gezeigt = batch;
			var offen   = -1;
			var letzterFokus = null;

			function passend() {
				return items.filter( function ( i ) {
					return ! kat || i.getAttribute( 'data-kat' ) === kat;
				} );
			}
			function zeichnen() {
				var liste = passend();
				items.forEach( function ( i ) { i.hidden = true; } );
				liste.slice( 0, gezeigt ).forEach( function ( i ) { i.hidden = false; } );
				if ( more ) { more.hidden = liste.length <= gezeigt; }
			}
			sec.querySelectorAll( '.fs-galfilter .fs-pill' ).forEach( function ( b ) {
				on( b, 'click', function () {
					sec.querySelectorAll( '.fs-galfilter .fs-pill' ).forEach( function ( x ) {
						x.classList.remove( 'is-active' );
					} );
					b.classList.add( 'is-active' );
					kat = b.getAttribute( 'data-kat' ) || '';
					gezeigt = batch;
					zeichnen();
				} );
			} );
			on( more, 'click', function () { gezeigt += batch; zeichnen(); } );

			function oeffnen( idx ) {
				var liste = passend().filter( function ( i ) { return ! i.hidden; } );
				if ( ! liste.length || ! box || ! bild ) { return; }
				offen = ( idx + liste.length ) % liste.length;
				var el = liste[ offen ];
				bild.src = el.getAttribute( 'data-full' ) || '';
				var img = el.querySelector( 'img' );
				bild.alt = img ? img.alt : '';
				box.hidden = false;
				document.body.style.overflow = 'hidden';
				var close = box.querySelector( '[data-fs-lb-close]' );
				if ( close ) { close.focus(); }
			}
			function schliessen() {
				if ( ! box ) { return; }
				box.hidden = true;
				document.body.style.overflow = '';
				if ( letzterFokus ) { letzterFokus.focus(); }
			}
			items.forEach( function ( it ) {
				on( it, 'click', function () {
					letzterFokus = it;
					var liste = passend().filter( function ( i ) { return ! i.hidden; } );
					oeffnen( liste.indexOf( it ) );
				} );
			} );
			on( sec.querySelector( '[data-fs-lb-close]' ), 'click', schliessen );
			on( sec.querySelector( '[data-fs-lb-prev]' ), 'click', function () { oeffnen( offen - 1 ); } );
			on( sec.querySelector( '[data-fs-lb-next]' ), 'click', function () { oeffnen( offen + 1 ); } );
			on( box, 'click', function ( e ) { if ( e.target === box ) { schliessen(); } } );
			on( document, 'keydown', function ( e ) {
				if ( ! box || box.hidden ) { return; }
				if ( 'Escape' === e.key )     { schliessen(); }
				if ( 'ArrowLeft' === e.key )  { oeffnen( offen - 1 ); }
				if ( 'ArrowRight' === e.key ) { oeffnen( offen + 1 ); }
			} );
			zeichnen();
		} );

		/* ---------- Fade-Up beim Scrollen ---------- */
		var faders = document.querySelectorAll( '.fs-fade-up' );
		if ( 'IntersectionObserver' in window ) {
			var io = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( en ) {
					if ( en.isIntersecting ) {
						en.target.classList.add( 'is-visible' );
						io.unobserve( en.target );
					}
				} );
			}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' } );
			faders.forEach( function ( elm ) { io.observe( elm ); } );
		} else {
			// Fallback für sehr alte Browser: sofort sichtbar schalten.
			faders.forEach( function ( elm ) { elm.classList.add( 'is-visible' ); } );
		}
	} );
} )();
