/**
 * Replacing the native MediaWiki search suggestions with Wikibase's entity selector widget.
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Jens Ohlig
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw ) {
	'use strict';

	$( document ).ready( function() {

		/**
		 * Removes the native search box suggestion list.
		 *
		 * @param {Object} input Search box node
		 */
		function removeSuggestionContext( input ) {
			// Native fetch() updates/re-sets the data attribute with the suggestion context.
			$.data( input, 'suggestionsContext' ).config.fetch = function() {};
			$.removeData( input, 'suggestionsContext' );
		}

		$( '#searchInput' )
		.one( 'focus', function( event ) {
			if ( $.data( this, 'suggestionsContext' ) ) {
				removeSuggestionContext( this );
			} else {
				// Suggestion context might not be initialized when focusing the search box while
				// the page is still rendered.
				var $input = $( this );
				$input.on( 'keypress.entitysearch', function( event ) {
					if ( $.data( this, 'suggestionsContext' ) ) {
						removeSuggestionContext( this );
						$input.off( '.entitysearch' );
					}
				} );
			}
		} )
		.entityselector( {
			url: mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' ) + '/api.php',
			language: mw.config.get( 'wgUserLanguage' ),
			emulateSearchBox: true
		} );

	} );

}( jQuery, mediaWiki ) );
