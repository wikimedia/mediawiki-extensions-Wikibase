/**
 * JavaScript for suggesting site names in Wikibase special pages
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		// this will build a drop-down for the site selection:
		var $inputs, sites, siteList;

		$inputs = $( '.wb-site-suggester' );
		if ( $inputs.length === 0 ) {
			return;
		}

		sites = wb.sites.getSites();
		siteList = [];
		for( var siteId in sites ) {
			if( sites.hasOwnProperty( siteId ) ) {
				siteList.push( sites[ siteId ].getName() + ' (' + siteId + ')' );
			}
		}

		$inputs.each( function () {
			var $siteSelector = $( this );
			$siteSelector.attr( 'autocomplete', 'off' );
			// TODO: Might want to use the siteselector jQuery widget or some other suggester derivate
			$siteSelector.suggester( { source: siteList } );

			// Hackety hack hack...
			// On submit, replace human readable value like "English (en)" with actual sitename ("enwiki")
			$siteSelector.closest( 'form' ).submit( function() {
				var langID = String( $siteSelector.val().replace( /.*\(|\).*/gi, '' ) ),
					site = wb.sites.getSite( langID );
				if ( site !== null ) {
					$siteSelector.val( site.getId() );
				}
			} );
		} );
	} );

} )( jQuery, mediaWiki, wikibase );
