/**
 * JavaScript for 'wikibase' extension special page 'EntitiesWithout*'
 *
 * @licence GNU GPL v2+
 */
( function( $ ) {
	'use strict';

	$( document ).ready( function() {
		var languages = [];

		if( $.uls ) {
			$.each( $.uls.data.getAutonyms(), function( key, value ) {
				languages.push( value + ' (' + key + ')' );
			} );
		}

		var $languageSelector = $( '#wb-entitieswithoutpage-language' );
		$languageSelector.attr( 'autocomplete', 'off' );
		$languageSelector.suggester( { source: languages } );

		$( '#wb-entitieswithoutpage-form' ).submit( function() {
			// Replace human readable value like "English (en)" with actual language name ("en"):
			var languageCode = String( $languageSelector.val().replace( /.*\(|\).*/gi, '' ) );
			$languageSelector.val( languageCode );
		} );

	} );

} )( jQuery );
