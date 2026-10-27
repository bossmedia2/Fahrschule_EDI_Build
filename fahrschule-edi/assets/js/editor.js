/**
 * Fahrschule edi – Generische Block-Editor-Registrierung.
 *
 * EINE Datei registriert die edit()-Oberfläche ALLER Blöcke. Die Block-Liste und
 * ihre editierbaren Felder kommen aus dem per wp_localize_script bereitgestellten
 * Objekt `FahrschuleBlocks`. Live-Vorschau über ServerSideRender (serverseitiges
 * Rendering identisch zum Frontend), Bearbeitung der Texte über InspectorControls.
 *
 * Kein Build-Step / kein JSX – reines wp.element.createElement.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks ) { return; }

	var el                 = wp.element.createElement;
	var registerBlockType  = wp.blocks.registerBlockType;
	var getBlockType       = wp.blocks.getBlockType;
	var unregisterBlockType = wp.blocks.unregisterBlockType;
	var InspectorControls  = wp.blockEditor.InspectorControls;
	var useBlockProps       = wp.blockEditor.useBlockProps;
	var PanelBody          = wp.components.PanelBody;
	var TextControl        = wp.components.TextControl;
	var TextareaControl    = wp.components.TextareaControl;
	var SelectControl      = wp.components.SelectControl;
	var Button             = wp.components.Button;
	var ServerSideRender   = wp.serverSideRender;
	var __                 = wp.i18n.__;

	var MediaUpload        = wp.blockEditor.MediaUpload;
	var MediaUploadCheck   = wp.blockEditor.MediaUploadCheck;
	var useRef             = wp.element.useRef;
	var useEffect          = wp.element.useEffect;

	var blocks = window.FahrschuleBlocks || [];

	/* Spalte eines Wiederholfelds: Zeichenkette (bis 3.3) = Textfeld. */
	function spalte( c ) { return 'string' === typeof c ? { label: c, type: 'text' } : c; }
	function istId( v ) { return /^\d+$/.test( String( v || '' ) ); }
	var GROSS = 5 * 1024 * 1024;   // Hinweis ab 5 MB je Video
	function mb( bytes ) { return ( bytes / 1048576 ).toFixed( 1 ).replace( '.', ',' ) + ' MB'; }

	/*
	 * Zelle vom Typ image/video: Mediathek-Auswahl. Upload und Auswahl sind in
	 * WordPress dasselbe - die Mediathek bringt den Reiter „Dateien hochladen“
	 * mit. Die Zelle hält die Attachment-ID (übersteht Domain-, HTTPS- und
	 * Systemwechsel). Alte Werte (Slug, URL) bleiben stehen, bis jemand ersetzt.
	 * Nach „Entfernen“ geht der Fokus auf „Auswählen“ zurück.
	 */
	function MediaZelle( p ) {
		var knopf   = useRef( null );
		var vorher  = useRef( p.value );
		useEffect( function () {
			if ( vorher.current && ! p.value && knopf.current ) { knopf.current.focus(); }
			vorher.current = p.value;
		}, [ p.value ] );
		var wort  = 'video' === p.typ ? __( 'Video', 'fahrschule-edi' ) : __( 'Bild', 'fahrschule-edi' );
		var m     = p.media;
		var info  = null;
		if ( p.value && ! istId( p.value ) ) {
			info = el( 'code', { style: { display: 'block', fontSize: '11px', wordBreak: 'break-all' } }, __( 'Alter Wert (bleibt gültig): ', 'fahrschule-edi' ) + p.value );
		} else if ( p.value && ! m ) {
			info = el( 'span', { style: { fontSize: '12px' } }, __( 'Wird geladen …', 'fahrschule-edi' ) );
		} else if ( p.value && 'image' === p.typ ) {
			var sizes = m.media_details && m.media_details.sizes;
			info = el( 'img', { src: sizes && sizes.thumbnail ? sizes.thumbnail.source_url : m.source_url, alt: '', style: { width: '64px', height: '64px', objectFit: 'cover', display: 'block' } } );
		} else if ( p.value ) {
			var datei = String( m.source_url || '' ).split( '/' ).pop();
			var bytes = m.media_details && m.media_details.filesize;
			info = el( 'span', { style: { display: 'block', fontSize: '12px' } }, datei,
				bytes > GROSS ? el( 'strong', { style: { display: 'block', color: '#a00' } }, __( 'Groß: ', 'fahrschule-edi' ) + mb( bytes ) + __( ' – bitte aufbereiten (siehe Redaktionsdokument).', 'fahrschule-edi' ) ) : null );
		}
		return el( 'div', { style: { marginBottom: '12px' } },
			el( 'span', { style: { display: 'block', fontSize: '11px', fontWeight: 500, textTransform: 'uppercase', marginBottom: '6px' } }, p.label ),
			info,
			el( 'div', { style: { display: 'flex', gap: '8px', marginTop: '6px' } },
				el( MediaUploadCheck, null,
					el( MediaUpload, {
						allowedTypes: [ p.typ ],
						value: istId( p.value ) ? parseInt( p.value, 10 ) : undefined,
						onSelect: function ( media ) { p.onChange( String( media.id ) ); },
						render: function ( o ) {
							return el( Button, {
								ref: knopf, variant: 'secondary', isSmall: true, onClick: o.open,
								'aria-label': wort + ' ' + __( 'für Zeile', 'fahrschule-edi' ) + ' ' + p.zeile + ' ' + ( p.value ? __( 'ersetzen', 'fahrschule-edi' ) : __( 'auswählen', 'fahrschule-edi' ) )
							}, p.value ? __( 'Ersetzen', 'fahrschule-edi' ) : __( 'Auswählen', 'fahrschule-edi' ) );
						}
					} )
				),
				p.value ? el( Button, {
					variant: 'link', isDestructive: true, isSmall: true,
					'aria-label': wort + ' ' + __( 'aus Zeile', 'fahrschule-edi' ) + ' ' + p.zeile + ' ' + __( 'entfernen', 'fahrschule-edi' ),
					onClick: function () { p.onChange( '' ); }
				}, __( 'Entfernen', 'fahrschule-edi' ) ) : null
			)
		);
	}

	blocks.forEach( function ( block ) {

		// Attribut-Schema + Default-Werte aus den Feldern ableiten.
		var attributes = {};
		( block.fields || [] ).forEach( function ( f ) {
			// Wiederholfelder sind Listen. Mit type 'string' verwirft der
			// Editor beim Laden jede gespeicherte Liste als ungültig und fällt
			// auf die Vorgabe zurück - Änderungen wären nach dem Neuladen weg.
			attributes[ f.key ] = { type: 'repeater' === f.control ? 'array' : 'string', 'default': f['default'] };
		} );

		var settings = {
			apiVersion:  3,
			title:       block.title,
			description: block.description,
			icon:        block.icon,
			category:    block.category || 'fahrschule',
			keywords:    block.keywords || [],
			attributes:  attributes,
			supports:    { html: false, align: [ 'wide', 'full' ] },

			edit: function ( props ) {
				var attrs   = props.attributes;
				var setAttr = props.setAttributes;

				// Medien der Mediathek-Spalten aus dem Datenspeicher (cached sich selbst).
				var ids = [];
				( block.fields || [] ).forEach( function ( f ) {
					if ( 'repeater' !== f.control || ! Array.isArray( attrs[ f.key ] ) ) { return; }
					( f.columns || [] ).map( spalte ).forEach( function ( c, ci ) {
						if ( 'text' === c.type ) { return; }
						attrs[ f.key ].forEach( function ( row ) { if ( Array.isArray( row ) && istId( row[ ci ] ) ) { ids.push( parseInt( row[ ci ], 10 ) ); } } );
					} );
				} );
				var medien = wp.data.useSelect( function ( select ) {
					var out = {};
					ids.forEach( function ( id ) { out[ id ] = select( 'core' ).getMedia( id ); } );
					return out;
				}, [ ids.join( ',' ) ] );

				/*
				 * Ein Feld auf die Vorgabe zurücksetzen heißt: das Attribut
				 * ENTFERNEN (undefined), nicht den Vorgabetext hineinschreiben.
				 * Nur dann lässt WordPress das Attribut unserialisiert und
				 * wendet den bei der Registrierung hinterlegten Default an -
				 * eine spätere Änderung der Vorgabe wirkt sich dann weiterhin
				 * auf diesen Block aus. Ein hineinkopierter Text würde sie
				 * dagegen für immer einfrieren.
				 */
				function resetButton( f ) {
					var changed = ( 'repeater' === f.control )
						? JSON.stringify( attrs[ f.key ] ) !== JSON.stringify( f['default'] )
						: attrs[ f.key ] !== f['default'];
					if ( ! changed ) { return null; }
					return el( Button, {
						key:      f.key + '-reset',
						variant:  'tertiary',
						isSmall:  true,
						icon:     'image-rotate',
						onClick:  function () { var u = {}; u[ f.key ] = undefined; setAttr( u ); },
						style:    { marginTop: '-8px', marginBottom: '12px' }
					}, __( 'Auf Vorgabe zurücksetzen', 'fahrschule-edi' ) );
				}

				/* Leeres Feld = bewusst leer. Der Hinweis macht sichtbar, dass
				   der Abschnitt im Frontend dann ganz verschwindet. */
				function emptyHint( f ) {
					if ( 'repeater' === f.control ) {
						if ( Array.isArray( attrs[ f.key ] ) && 0 === attrs[ f.key ].length ) {
							return el( 'p', { key: f.key + '-hint',
								style: { margin: '-8px 0 12px', fontSize: '12px', fontStyle: 'italic', opacity: 0.8 } },
								__( 'Keine Einträge – der Abschnitt wird ausgeblendet.', 'fahrschule-edi' ) );
						}
						return null;
					}
					if ( '' !== String( attrs[ f.key ] === undefined ? f['default'] : attrs[ f.key ] ).trim() ) { return null; }
					return el( 'p', {
						key:   f.key + '-hint',
						style: { margin: '-8px 0 12px', fontSize: '12px', fontStyle: 'italic', opacity: 0.8 }
					}, __( 'Leer – wird im Frontend ausgeblendet.', 'fahrschule-edi' ) );
				}

				/*
				 * Wiederholfeld: Zeilenliste mit Hinzufügen, Löschen und
				 * Verschieben. Die Werte liegen als Liste von Listen vor, in
				 * der Spaltenreihenfolge aus der Blockkonfiguration.
				 */
				function repeaterControl( f ) {
					var cols = ( f.columns || [] ).map( spalte );
					var rows = Array.isArray( attrs[ f.key ] ) ? attrs[ f.key ] : [];

					function write( next ) { var u = {}; u[ f.key ] = next; setAttr( u ); }
					function clone() { return rows.map( function ( r ) { return Array.isArray( r ) ? r.slice() : [ r ]; } ); }

					var rowEls = rows.map( function ( row, i ) {
						var cells = cols.map( function ( col, c ) {
							if ( 'text' !== col.type ) {
								return el( MediaZelle, {
									key:      f.key + '-' + i + '-' + c,
									label:    col.label,
									typ:      col.type,
									zeile:    i + 1,
									value:    row[ c ] === undefined ? '' : String( row[ c ] ),
									media:    istId( row[ c ] ) ? medien[ parseInt( row[ c ], 10 ) ] : null,
									onChange: function ( v ) { var n = clone(); n[ i ][ c ] = v; write( n ); }
								} );
							}
							return el( TextControl, {
								key:      f.key + '-' + i + '-' + c,
								label:    col.label,
								value:    row[ c ] === undefined ? '' : row[ c ],
								onChange: function ( v ) { var n = clone(); n[ i ][ c ] = v; write( n ); }
							} );
						} );
						var tools = el( 'div', { key: 'tools', style: { display: 'flex', gap: '4px', marginBottom: '16px' } },
							el( Button, { icon: 'arrow-up-alt2', isSmall: true, variant: 'tertiary',
								disabled: 0 === i,
								label: __( 'Nach oben', 'fahrschule-edi' ),
								onClick: function () { var n = clone(); var t = n[ i - 1 ]; n[ i - 1 ] = n[ i ]; n[ i ] = t; write( n ); } } ),
							el( Button, { icon: 'arrow-down-alt2', isSmall: true, variant: 'tertiary',
								disabled: i === rows.length - 1,
								label: __( 'Nach unten', 'fahrschule-edi' ),
								onClick: function () { var n = clone(); var t = n[ i + 1 ]; n[ i + 1 ] = n[ i ]; n[ i ] = t; write( n ); } } ),
							el( Button, { icon: 'trash', isSmall: true, variant: 'tertiary', isDestructive: true,
								label: __( 'Eintrag löschen', 'fahrschule-edi' ),
								onClick: function () { var n = clone(); n.splice( i, 1 ); write( n ); } } )
						);
						return el( 'div', {
							key:   f.key + '-row-' + i,
							style: { border: '1px solid #ddd', borderRadius: '4px', padding: '12px', marginBottom: '8px' }
						}, el( 'strong', { style: { display: 'block', marginBottom: '8px' } }, f.label + ' ' + ( i + 1 ) ), cells, tools );
					} );

					return el( 'div', { key: f.key },
						rowEls,
						el( Button, {
							variant: 'secondary', isSmall: true, icon: 'plus-alt2',
							onClick: function () {
								var n = clone();
								n.push( cols.map( function () { return ''; } ) );
								write( n );
							}
						}, __( 'Eintrag hinzufügen', 'fahrschule-edi' ) )
					);
				}

				// Steuerelemente je Feld erzeugen.
				var controls = [];
				( block.fields || [] ).forEach( function ( f ) {
					var common = {
						key:      f.key,
						label:    f.label,
						value:    attrs[ f.key ],
						onChange: function ( v ) {
							var u = {}; u[ f.key ] = v; setAttr( u );
						}
					};
					var control;
					if ( 'repeater' === f.control ) {
						control = repeaterControl( f );
					} else if ( 'textarea' === f.control ) {
						control = el( TextareaControl, common );
					} else if ( 'select' === f.control ) {
						var opts = Object.keys( f.options || {} ).map( function ( k ) {
							return { label: f.options[ k ], value: k };
						} );
						control = el( SelectControl, {
							key: f.key, label: f.label, value: attrs[ f.key ],
							options: opts,
							onChange: function ( v ) { var u = {}; u[ f.key ] = v; setAttr( u ); }
						} );
					} else {
						// text / url
						control = el( TextControl, common );
					}
					controls.push( control );
					controls.push( emptyHint( f ) );
					controls.push( resetButton( f ) );
				} );

				var inspector = ( block.fields && block.fields.length )
					? el( InspectorControls, { key: 'inspector' },
						el( PanelBody, { title: __( 'Inhalte bearbeiten', 'fahrschule-edi' ), initialOpen: true }, controls )
					)
					: null;

				// Seitenkontext für die Vorschau: Im Block-Editor die bearbeitete
				// Seite (der PHP-Renderer setzt damit $post, siehe
				// fahrschule_current_key). Im Website-Editor ist die "Seite" eine
				// Vorlage mit Text-ID - die darf nicht mitgeschickt werden.
				var editorStore = wp.data && wp.data.select( 'core/editor' );
				var postId = editorStore && editorStore.getCurrentPostId ? editorStore.getCurrentPostId() : null;
				var query  = ( 'number' === typeof postId ) ? { post_id: postId } : {};

				var preview = el( 'div', useBlockProps ? useBlockProps() : {},
					el( ServerSideRender, {
						block: block.name,
						attributes: attrs,
						urlQueryArgs: query,
						// Leere Ausgabe = der Abschnitt entfällt im Frontend. Statt eines
						// leeren Kastens sagt der Editor das ausdrücklich.
						EmptyResponsePlaceholder: function () {
							return el( 'div', { className: 'fs-editor-hint' },
								el( 'strong', null, block.title ),
								' ' + __( 'ist hier leer und wird im Frontend ausgeblendet. Inhalte rechts in der Seitenleiste ergänzen.', 'fahrschule-edi' ) );
						}
					} )
				);

				return [ inspector, preview ];
			},

			// Dynamischer Block: Ausgabe übernimmt der PHP render_callback.
			save: function () { return null; }
		};

		// Falls der Block serverseitig bereits registriert wurde: erst lösen,
		// dann mit unserer edit/save-Oberfläche neu registrieren (verhindert Warnungen).
		if ( getBlockType( block.name ) ) {
			unregisterBlockType( block.name );
		}
		registerBlockType( block.name, settings );
	} );

} )( window.wp );
