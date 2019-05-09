'use strict';

const MWBot = require( 'mwbot' );

class WikibaseUtil {

	/**
	 * Wait until a given new Entity is known to wbsearchentities
	 *
	 * @param {string} entityId Title of the newly created entity
	 * @param {int} waitIntervalMS time between two checks to the api in milliseconds
	 * @param {int} remainingWaitTimeMS remaining waiting time in milliseconds after which an error is thrown
	 *
	 * @returns {Promise}
	 */
	waitTillEntityIsKnown( entityId, waitIntervalMS, remainingWaitTimeMS ) {
		if ( remainingWaitTimeMS <= 0 ) {
			throw Error( 'Search failed to update within the time limit for id ' + entityId );
		}

		let bot = new MWBot( {
			apiUrl: browser.options.baseUrl + '/api.php'
		} );

		return bot.request( {}, {
			method: 'GET',
			qs: {
				action: 'wbsearchentities',
				search: entityId,
				language: 'en',
				uselang: 'en',
				logME: true,
				type: 'item',
				format: 'json'
			}
		} ).then( ( response ) => {
			if ( response.search.length !== 0 ) {
				return;
			}
			return new Promise( ( resolve ) => {
				setTimeout( resolve, waitIntervalMS );
			} ).then( () => {
				return this.waitTillEntityIsKnown( entityId, remainingWaitTimeMS - waitIntervalMS, waitIntervalMS );
			} );
		} );
	}

}

module.exports = new WikibaseUtil();
