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

	var blocks = window.FahrschuleBlocks || [];

	blocks.forEach( function ( block ) {

		// Attribut-Schema + Default-Werte aus den Feldern ableiten.
		var attributes = {};
		( block.fields || [] ).forEach( function ( f ) {
			attributes[ f.key ] = { type: 'string', 'default': f['default'] };
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
					var cols = f.columns || [];
					var rows = Array.isArray( attrs[ f.key ] ) ? attrs[ f.key ] : [];

					function write( next ) { var u = {}; u[ f.key ] = next; setAttr( u ); }
					function clone() { return rows.map( function ( r ) { return Array.isArray( r ) ? r.slice() : [ r ]; } ); }

					var rowEls = rows.map( function ( row, i ) {
						var cells = cols.map( function ( colLabel, c ) {
							return el( TextControl, {
								key:      f.key + '-' + i + '-' + c,
								label:    colLabel,
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

				var preview = el( 'div', useBlockProps ? useBlockProps() : {},
					el( ServerSideRender, {
						block: block.name,
						attributes: attrs
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
