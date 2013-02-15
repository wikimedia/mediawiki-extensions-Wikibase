/**
 * Replacing the native MediaWiki search suggestions with Wikibase's entity selector widget.
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw ) {
	'use strict';

	$( document ).ready( function() {

		$( '#searchInput' )
		.one( 'focus', function( event ) {
			// Native fetch() updates/re-sets the data attribute with the suggestion context.
			$.data( this, 'suggestionsContext' ).config.fetch = function() {};
			$.removeData( this, 'suggestionsContext' );
		} )
		.entityselector( {
			url: mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' ) + '/api.php',
			language: mw.config.get( 'wgUserLanguage' )
		} );

	} );

}( jQuery, mediaWiki ) );
