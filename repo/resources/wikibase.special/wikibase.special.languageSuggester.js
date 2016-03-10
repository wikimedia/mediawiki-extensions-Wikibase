/**
 * JavaScript for suggesting language names in Wikibase special pages
 *
 * @license GPL-2.0+
 * @author Jens Ohlig
 */
( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		var $inputs,
			languages = [];

		if ( !$.uls ) {
			return;
		}

		$inputs = $( '.wb-language-suggester' );
		if ( $inputs.length === 0 ) {
			return;
		}

		$.each( $.uls.data.getAutonyms(), function ( key, value ) {
			languages.push( value + ' (' + key + ')' );
		} );

		$inputs.each( function () {
			var $languageSelector = $( this );
			$languageSelector.attr( 'autocomplete', 'off' );
			// TODO: Might want to use the siteselector jQuery widget or some other suggester derivate
			$languageSelector.suggester( { source: languages } );

			$languageSelector.closest( 'form' ).submit( function () {
				// Replace human readable value like "English (en)" with actual language name ("en"):
				var languageCode = String( $languageSelector.val().replace( /.*\(|\).*/gi, '' ) );
				$languageSelector.val( languageCode );
			} );
		} );
	} );

} )( jQuery );
