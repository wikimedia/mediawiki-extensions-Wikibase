/**
 * JavaScript for updating language-aware input placeholders
 *
 * @license GPL-2.0+
 */
( function ( $, mw, OO ) {
	'use strict';

	$( document ).ready( function () {
		var $lang, fields, fieldCount, autonyms, langWidget;

		if ( !$.uls ) {
			return;
		}

		$lang = $( document.getElementsByName( 'lang' ) ).closest( '.oo-ui-inputWidget' );
		if ( $lang.length === 0 ) {
			return;
		}

		fields = [
			{
				name: 'label',
				msgSimple: 'wikibase-label-edit-placeholder',
				msgAware: 'wikibase-label-edit-placeholder-language-aware'
			},
			{
				name: 'description',
				msgSimple: 'wikibase-description-edit-placeholder',
				msgAware: 'wikibase-description-edit-placeholder-language-aware'
			},
			{
				name: 'aliases',
				msgSimple: 'wikibase-aliases-edit-placeholder',
				msgAware: 'wikibase-aliases-edit-placeholder-language-aware'
			}
		];

		fieldCount = 0;
		$.each( fields, function ( i ) {
			fields[ i ].$element = $( document.getElementsByName( fields[ i ].name ) )
				.closest( '.oo-ui-inputWidget' );
			fieldCount += fields[ i ].$element.length;
		} );
		if ( fieldCount === 0 ) {
			// There must be at least one field whose placeholder we have to update
			return;
		}

		autonyms = $.uls.data.getAutonyms();
		langWidget = OO.ui.infuse( $lang );
		$.each( fields, function ( i ) {
			fields[ i ].$input = OO.ui.infuse( fields[ i ].$element ).$input;
		} );

		function updatePlaceholders( value ) {
			var autonym = autonyms[ value ];
			$.each( fields, function ( i ) {
				var msg;
				if ( autonym ) {
					msg = mw.message( fields[ i ].msgAware, autonym );
				} else {
					msg = mw.message( fields[ i ].msgSimple );
				}
				fields[ i ].$input.attr( 'placeholder', msg.text() );
			} );
		}

		updatePlaceholders( langWidget.getValue() );
		langWidget.on( 'change', updatePlaceholders );
	} );

} )( jQuery, mediaWiki, OO );
