/**
 * JavaScript for updating language-aware input placeholders
 *
 * @license GPL-2.0-or-later
 */
( function ( userLang, contentLanguages ) {
	'use strict';

	$( function () {
		var $lang, fields, fieldCount, availableLangs, langWidget;

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

		availableLangs = contentLanguages.getAllPairs() || {};
		langWidget = OO.ui.infuse( $lang );
		fields.forEach( function ( field ) {
			field.$input = OO.ui.infuse( field.$element ).$input;
		} );

		function updatePlaceholders( languageCode ) {
			var language = availableLangs[ languageCode ],
				langDir = $.uls ? $.uls.data.getDir( languageCode ) : null;

			if ( typeof language !== 'string' ) {
				language = '[' + languageCode + ']';
			}

			fields.forEach( function ( field ) {
				// The following messages can be used here:
				// * wikibase-label-edit-placeholder-language-aware
				// * wikibase-description-edit-placeholder-language-aware
				// * wikibase-aliases-edit-placeholder-language-aware
				field.$input.prop( 'placeholder', mw.msg( field.msgAware, language ) );

				if ( langDir ) {
					field.$input.prop( 'dir', langDir );
					field.$input.addClass( 'wb-placeholder-dir-' + $.uls.data.getDir( userLang ) );
				}
			} );
		}

		updatePlaceholders( langWidget.getValue() );
		langWidget.on( 'change', updatePlaceholders );
	} );

}( mw.config.values.wgUserLanguage, new window.wb.WikibaseContentLanguages() ) );
