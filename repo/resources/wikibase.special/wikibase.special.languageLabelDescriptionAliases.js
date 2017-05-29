/**
 * JavaScript for updating language-aware input placeholders
 *
 * @license GPL-2.0+
 */
( function ( $, mw, OO ) {
	'use strict';

	$( function () {
		var $lang, fields, fieldCount, autonyms, langWidget;

		$lang = $( document.getElementsByName( 'lang' ) ).closest( '.oo-ui-inputWidget' );
		if ( $lang.length === 0 ) {
			return;
		}

		fields = [
			{
				name: 'label',
				msgAware: 'wikibase-label-edit-placeholder-language-aware'
			},
			{
				name: 'description',
				msgAware: 'wikibase-description-edit-placeholder-language-aware'
			},
			{
				name: 'aliases',
				msgAware: 'wikibase-aliases-edit-placeholder-language-aware'
			}
		];

		fieldCount = 0;
		fields.forEach( function ( field ) {
			field.$element = $( document.getElementsByName( field.name ) )
				.closest( '.oo-ui-inputWidget' );
			fieldCount += field.$element.length;
		} );
		if ( fieldCount === 0 ) {
			// There must be at least one field whose placeholder we have to update
			return;
		}

		autonyms = $.uls ? $.uls.data.getAutonyms() : {};
		langWidget = OO.ui.infuse( $lang );
		fields.forEach( function ( field ) {
			field.$input = OO.ui.infuse( field.$element ).$input;
		} );

		function updatePlaceholders( languageCode ) {
			var autonym = autonyms[ languageCode ];

			if ( typeof autonym !== 'string' ) {
				autonym = '[' + languageCode + ']';
			}

			fields.forEach( function ( field ) {
				field.$input.attr( 'placeholder', mw.msg( field.msgAware, autonym ) );
			} );
		}

		updatePlaceholders( langWidget.getValue() );
		langWidget.on( 'change', updatePlaceholders );
	} );

}( jQuery, mediaWiki, OO ) );
