/**
 * JavaScript for 'wikibase' extension special page 'ItemByTitle'
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jens Ohlig
 */
( function( $, mw, OO, wb ) {
	'use strict';

	$( document ).ready( function() {
		if ( ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ItemByTitle' ) ) {
			return; // not the right special page
		}

		// this will build a drop-down for the language selection:
		var sites = wb.sites.getSites(),
			siteList = [];
		for ( var siteId in sites ) {
			if ( sites.hasOwnProperty( siteId ) ) {
				siteList.push( sites[ siteId ].getName() + ' (' + siteId + ')' );
			}
		}

		var $input = OO.ui.infuse( $( '#wb-itembytitle-sitename' ) ).$input;

		$input
		.attr( 'autocomplete', 'off' )
		.suggester( { source: siteList } );
		// Hackety hack hack...
		// On submit, replace human readable value like "English (en)" with actual sitename ("enwiki")
		$( '#wb-itembytitle-form1' ).submit( function() {
			var langID = String( $input.val().replace( /.*\(|\).*/gi, '' ) );
			if ( wb.sites.getSite( langID ).getId() !== undefined ) {
				$input.val( wb.sites.getSite( langID ).getId() );
			}
		} );
	} );

} )( jQuery, mediaWiki, OO, wikibase );
