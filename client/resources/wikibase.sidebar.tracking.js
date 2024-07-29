'use strict';

/**
 * Add tracking to the sidebar link to wikidata item and other projects link
 *
 * @license GPL-2.0-or-later
 */
// EventLogging may not be installed
mw.loader.using( 'ext.eventLogging' ).then( () => {
	$( '#t-wikibase' ).on( 'click', () => {
		mw.eventLog.submitClick( 'wikibase.client.interaction', {
			actionSource: 'wbSidebarWikidataItemLinkClick'
		} );
	} );

	$( '.wb-otherproject-link.wb-otherproject-wikidata.mw-list-item' ).on( 'click', () => {
		mw.eventLog.submitClick( 'wikibase.client.interaction', {
			actionSource: 'wbSidebarWikidataOtherProjectsLinkClick'
		} );
	} );
} );
