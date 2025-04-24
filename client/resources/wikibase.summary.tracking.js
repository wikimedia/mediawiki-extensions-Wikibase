'use strict';

/**
 * Add tracking to the summary Item and Property links
 * and the changelog Item links in class mw-changeslist.
 * We would only be measuring it on Recentchanges and
 * Watchlist special pages.
 *
 * Temporary: We will remove it by Sept. 2025
 * Bug: T392469
 *
 * @license GPL-2.0-or-later
 */
// EventLogging may not be installed
mw.loader.using( 'ext.eventLogging' ).then( () => {
	$( '.mw-changeslist' ).on( 'click', 'a.external', ( e ) => {
		// Regex to log summary Property clicks
		if ( e.target.href.match( /P\d+/g ) ) {
			mw.eventLog.submitClick( 'wikibase.client.interaction', {
				// eslint-disable-next-line camelcase
				action_source: 'wbSummaryPropertyClick'
			} );
		}

		// Regex to log summary Item clicks
		if ( e.target.href.match( /Q\d+/g ) ) {
			mw.eventLog.submitClick( 'wikibase.client.interaction', {
				// eslint-disable-next-line camelcase
				action_source: 'wbSummaryItemClick'
			} );
		}
	} );

	$( '.mw-changeslist' ).on( 'click', 'a.wb-entity-link', () => {
		mw.eventLog.submitClick( 'wikibase.client.interaction', {
			// eslint-disable-next-line camelcase
			action_source: 'wbChangelogItemClick'
		} );
	} );
} );
