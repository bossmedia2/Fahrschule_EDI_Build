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

				// Steuerelemente je Feld erzeugen.
				var controls = ( block.fields || [] ).map( function ( f ) {
					var common = {
						key:      f.key,
						label:    f.label,
						value:    attrs[ f.key ],
						onChange: function ( v ) {
							var u = {}; u[ f.key ] = v; setAttr( u );
						}
					};
					if ( 'textarea' === f.control ) {
						return el( TextareaControl, common );
					}
					if ( 'select' === f.control ) {
						var opts = Object.keys( f.options || {} ).map( function ( k ) {
							return { label: f.options[ k ], value: k };
						} );
						return el( SelectControl, {
							key: f.key, label: f.label, value: attrs[ f.key ],
							options: opts,
							onChange: function ( v ) { var u = {}; u[ f.key ] = v; setAttr( u ); }
						} );
					}
					// text / url
					return el( TextControl, common );
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
