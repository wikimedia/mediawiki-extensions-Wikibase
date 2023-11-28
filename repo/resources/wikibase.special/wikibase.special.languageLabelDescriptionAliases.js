/**
 * JavaScript for updating language-aware input placeholders
 *
 * @license GPL-2.0-or-later
 */
( function ( userLang, getLanguageNameByCode ) {
	'use strict';

	$( function () {
		var $lang, fields, fieldCount, langWidget;

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

		langWidget = OO.ui.infuse( $lang );
		fields.forEach( function ( field ) {
			field.fieldLayoutWidget = OO.ui.infuse( field.$element.closest( '.oo-ui-fieldLayout' ) );
			field.widget = OO.ui.infuse( field.$element );
		} );

		function updatePlaceholders( languageCode ) {
			var languageName = getLanguageNameByCode( languageCode ),
				langDir = $.uls ? $.uls.data.getDir( languageCode ) : null;

			fields.forEach( function ( field ) {
				var $input = field.widget.$input;
				// The following messages can be used here:
				// * wikibase-label-edit-placeholder-language-aware
				// * wikibase-description-edit-placeholder-language-aware
				// * wikibase-aliases-edit-placeholder-language-aware
				$input.prop( 'placeholder', mw.msg( field.msgAware, languageName ) );

				if ( langDir ) {
					$input.prop( 'dir', langDir );
					$input.addClass( 'wb-placeholder-dir-' + $.uls.data.getDir( userLang ) );
				}
			} );
		}

		function indicateDescriptionSupport( languageCode ) {
			var disabled = languageCode === 'mul';

			fields.forEach( function ( field ) {
				if ( field.name !== 'description' ) {
					return;
				}
				field.widget.setDisabled( disabled );
				field.fieldLayoutWidget.setNotices(
					disabled ? [ mw.msg( 'wikibase-description-edit-not-supported' ) ] : []
				);
				if ( disabled ) {
					// Clear the previous input if "mul" is selected.
					field.widget.$input.val( '' );
				}
			} );
		}

		updatePlaceholders( langWidget.getValue() );
		indicateDescriptionSupport( langWidget.getValue() );
		langWidget.on( 'change', updatePlaceholders );
		langWidget.on( 'change', indicateDescriptionSupport );
	} );

}( mw.config.values.wgUserLanguage, wikibase.getLanguageNameByCode ) );
