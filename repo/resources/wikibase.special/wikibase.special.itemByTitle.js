/**
 * JavaScript for 'wikibase' extension special page 'ItemByTitle'
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseRepo
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
		var siteList = [],
			siteId, site;

		// this will build a drop-down for the language selection:
		for ( siteId in wb.getSites() ) {
			site = wb.getSite( siteId );
			siteList.push( {
				'label': site.getName() + ' (' + site.getId() + ')',
				'value': site.getName() + ' (' + site.getId() + ')'
			} );
		}
		$( '#wb-itembytitle-sitename' ).suggester( { 'source': siteList } );
		// Hackety hack hack...
		// On submit, replace human readable value like "English (en)" with actual sitename ("enwiki")
		$( '#wb-itembytitle-form1' ).submit( function() {
			var langID = String( $( '#wb-itembytitle-sitename' ).val().replace(/.*\(|\).*/gi,'') );
			if ( wb._siteList[langID].getGlobalSiteId() !== undefined ) {
				$( '#wb-itembytitle-sitename' ).val( wb._siteList[langID].getGlobalSiteId() );
			}
		});
	} );

} )( jQuery, mediaWiki, wikibase );
