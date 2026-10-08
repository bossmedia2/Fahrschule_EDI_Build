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
			var mq = window.matchMedia( '(min-width: 1180px)' );
			var onChange = function ( ev ) { if ( ev.matches ) { toggleDrawer( false ); } };
			if ( mq.addEventListener ) { mq.addEventListener( 'change', onChange ); }
			else if ( mq.addListener ) { mq.addListener( onChange ); }
		}

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

		/* ---------- Instagram-Slider ---------- */
		document.querySelectorAll( '[data-fs-ig]' ).forEach( function ( section ) {
			var track = section.querySelector( '[data-fs-ig-track]' );
			var dots  = section.querySelector( '[data-fs-ig-dots]' );
			if ( ! track ) { return; }
			var posts = [];
			try { posts = JSON.parse( track.getAttribute( 'data-posts' ) ); } catch ( e ) {}
			if ( ! posts.length ) { return; }
			var current = 0;

			function buildCard( p ) {
				var fig = document.createElement( 'figure' );
				fig.className = 'fs-ig__post';

				var badge = document.createElement( 'span' );
				badge.className = 'fs-ig__badge';
				badge.textContent = 'Instagram';

				var img = document.createElement( 'img' );
				img.src = p[0];
				img.alt = p[1];
				img.width = 500; img.height = 500;
				img.loading = 'lazy'; img.decoding = 'async';

				var cap = document.createElement( 'figcaption' );
				cap.className = 'fs-ig__cap';
				var txt = document.createElement( 'p' );
				txt.textContent = p[1];
				var meta = document.createElement( 'div' );
				meta.className = 'fs-ig__meta';
				var likes = document.createElement( 'span' );
				likes.textContent = p[2] + ' Likes';
				var comments = document.createElement( 'span' );
				comments.textContent = p[3] + ' Kommentare';
				meta.appendChild( likes ); meta.appendChild( comments );
				cap.appendChild( txt ); cap.appendChild( meta );

				fig.appendChild( badge ); fig.appendChild( img ); fig.appendChild( cap );
				return fig;
			}

			function render() {
				track.textContent = '';
				for ( var i = 0; i < Math.min( 3, posts.length ); i++ ) {
					track.appendChild( buildCard( posts[ ( current + i ) % posts.length ] ) );
				}
				if ( dots ) {
					dots.textContent = '';
					posts.forEach( function ( _, idx ) {
						var d = document.createElement( 'button' );
						d.type = 'button';
						d.className = 'fs-ig__dot' + ( idx === current ? ' is-active' : '' );
						d.setAttribute( 'aria-label', 'Beitrag ' + ( idx + 1 ) + ' anzeigen' );
						d.addEventListener( 'click', function () { current = idx; render(); } );
						dots.appendChild( d );
					} );
				}
			}

			// Punkte-Navigation initial aufbauen; die ersten drei Karten kommen
			// bereits serverseitig gerendert aus PHP und bleiben bis zur ersten
			// Interaktion unangetastet.
			function renderDotsOnly() {
				if ( ! dots ) { return; }
				dots.textContent = '';
				posts.forEach( function ( _, idx ) {
					var d = document.createElement( 'button' );
					d.type = 'button';
					d.className = 'fs-ig__dot' + ( idx === current ? ' is-active' : '' );
					d.setAttribute( 'aria-label', 'Beitrag ' + ( idx + 1 ) + ' anzeigen' );
					d.addEventListener( 'click', function () { current = idx; render(); } );
					dots.appendChild( d );
				} );
			}

			on( section.querySelector( '[data-fs-ig-next]' ), 'click', function () { current = ( current + 1 ) % posts.length; render(); } );
			on( section.querySelector( '[data-fs-ig-prev]' ), 'click', function () { current = ( current - 1 + posts.length ) % posts.length; render(); } );
			renderDotsOnly();
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
