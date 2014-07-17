/**
 * JavaScript for 'wikibase' extension special page 'ItemByTitle'
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
( function( $, mw, wb, undefined ) {
	'use strict';

	$( document ).ready( function() {
		if( ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ItemByTitle' ) ) {
			return; // not the right special page
		}

		// this will build a drop-down for the language selection:
		var siteList = [];
		for ( var siteId in wb.sites.getSites() ) {
			var site = wb.sites.getSite( siteId );
			siteList.push( {
				'label': site.getName() + ' (' + site.getId() + ')',
				'value': site.getName() + ' (' + site.getId() + ')'
			} );
		}
		$( '#wb-itembytitle-sitename' ).suggester( { 'source': siteList } );
		// Hackety hack hack...
		// On submit, replace human readable value like "English (en)" with actual sitename ("enwiki")
		$( '#wb-itembytitle-form1' ).submit( function() {
			var $input = $( '#wb-itembytitle-sitename' );
			var langID = String( $input.val().replace(/.*\(|\).*/gi,'') );
			if ( wb.sites.getSite( langID ).getId() !== undefined ) {
				$input.val( wb.sites.getSite( langID ).getId() );
			}
		});
	} );

} )( jQuery, mediaWiki, wikibase );
