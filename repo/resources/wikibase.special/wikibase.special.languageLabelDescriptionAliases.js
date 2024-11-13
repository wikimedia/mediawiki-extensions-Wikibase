/**
 * JavaScript for updating language-aware input placeholders
 *
 * @license GPL-2.0-or-later
 */
( function ( userLang, getLanguageNameByCodeForTerms, wb ) {
	'use strict';

	var hydrateLanguageLabelInputForm = function () {
		var $lang, fields, fieldCount, langWidget;

		$lang = $( document.getElementsByName( 'lang' ) ).closest( '.oo-ui-inputWidget' );
		if ( $lang.length === 0 ) {
			return;
		}

		fields = [
			{
				name: 'label',
				msgAware: 'wikibase-label-edit-placeholder-language-aware',
				msgMul: 'wikibase-label-edit-placeholder-mul'
			},
			{
				name: 'description',
				msgAware: 'wikibase-description-edit-placeholder-language-aware'
			},
			{
				name: 'aliases',
				msgAware: 'wikibase-aliases-edit-placeholder-language-aware',
				msgMul: 'wikibase-aliases-edit-placeholder-mul'
			}
		];

		fieldCount = 0;
		fields.forEach( ( field ) => {
			field.$element = $( document.getElementsByName( field.name ) )
				.closest( '.oo-ui-inputWidget' );
			fieldCount += field.$element.length;
		} );
		if ( fieldCount === 0 ) {
			// There must be at least one field whose placeholder we have to update
			return;
		}

		langWidget = OO.ui.infuse( $lang );
		fields.forEach( ( field ) => {
			field.fieldLayoutWidget = OO.ui.infuse( field.$element.closest( '.oo-ui-fieldLayout' ) );
			field.widget = OO.ui.infuse( field.$element );
		} );

		function updatePlaceholders( languageCode ) {
			var languageName = getLanguageNameByCodeForTerms( languageCode ),
				langDir = $.uls ? $.uls.data.getDir( languageCode ) : null;

			fields.forEach( ( field ) => {
				var $input = field.widget.$input;
				var placeholderText;
				if ( languageCode === 'mul' ) {
					if ( 'msgMul' in field ) {
						// The following messages can be used here:
						// * wikibase-label-edit-placeholder-mul
						// * wikibase-aliases-edit-placeholder-mul
						placeholderText = mw.msg( field.msgMul );
					} else {
						placeholderText = '';
					}
				} else {
					// The following messages can be used here:
					// * wikibase-label-edit-placeholder-language-aware
					// * wikibase-description-edit-placeholder-language-aware
					// * wikibase-aliases-edit-placeholder-language-aware
					placeholderText = mw.msg( field.msgAware, languageName );
				}
				$input.prop( 'placeholder', placeholderText );

				if ( langDir ) {
					$input.prop( 'dir', langDir );
					$input.addClass( 'wb-placeholder-dir-' + $.uls.data.getDir( userLang ) );
				}
			} );
		}

		function indicateDescriptionSupport( languageCode ) {
			var disabled = languageCode === 'mul';
			var messageKey;
			if ( mw.config.values.wgCanonicalSpecialPageName === 'NewProperty' ) {
				messageKey = 'wikibase-property-description-edit-not-supported';
			} else if ( mw.config.values.wgCanonicalSpecialPageName === 'NewItem' ) {
				messageKey = 'wikibase-item-description-edit-not-supported';
			}

			fields.forEach( ( field ) => {
				if ( field.name !== 'description' ) {
					return;
				}
				field.widget.setDisabled( disabled );
				field.fieldLayoutWidget.setNotices(
					// The following messages can be used here:
					// * wikibase-property-description-edit-not-supported
					// * wikibase-item-description-edit-not-supported
					disabled ? [ mw.msg( messageKey ) ] : []
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
	};

	$( hydrateLanguageLabelInputForm );

	wb.hydrateLanguageLabelInputForm = hydrateLanguageLabelInputForm;

}( mw.config.values.wgUserLanguage, wikibase.getLanguageNameByCodeForTerms, wikibase ) );
