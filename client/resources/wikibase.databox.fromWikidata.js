'use strict';
/**
 * Add tracking to the "From Wikidata" links in Databox
 * note that the class databox-from-wikidata-link in the module is only used for this tracking so could be removed after
 *
 * Temporary: We will remove it by Feb. 2026
 * Bug: T408709
 *
 * @license GPL-2.0-or-later
 */
// EventLogging may not be installed
mw.loader.using( 'ext.eventLogging' ).then( () => {
	$( '.databox-from-wikidata-link' ).on( 'click', 'a.extiw', () => {
		mw.eventLog.submitClick( 'wikibase.client.interaction', {
			// eslint-disable-next-line camelcase
			action_source: 'wbFromWikidataClick'
		} );
	} );
} );
