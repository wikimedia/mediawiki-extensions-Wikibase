'use strict';

const MWBot = require( 'mwbot' );

class CirrusUtil {

	waitForCirrus( entityIds, remainingTimeout, intervalWait ) {
		if ( remainingTimeout < 0 ) {
			throw Error( 'Cirrus failed to update within the time limit for ids ' + entityIds.join( ', ' ) );
		}

		let bot = new MWBot( {
			apiUrl: browser.options.baseUrl + '/api.php'
		} );

		return bot.request( {
			action: 'query',
			prop: 'cirrusdoc|revisions',
			titles: entityIds.join( '|' ),
			rvprop: 'ids'
		} ).then( ( response ) => {
			if ( response.warnings ) {
				// that is to be expected if cirrus is not there
				return;
			}
			if ( this._isCirrusUpToDate( response ) ) {
				return;
			}
			return new Promise( ( resolve ) => {
				setTimeout( resolve, intervalWait );
			} ).then( () => {
				return this.waitForCirrus( entityIds, remainingTimeout - intervalWait, intervalWait );
			} );
		} );
	}

	_isCirrusUpToDate( apiResponse ) {
		const pagesData = apiResponse.query.pages.values();
		const cirrusPagesOutOfDate = pagesData.filter( this._isCirrusPageVersionOutOfDate );
		return cirrusPagesOutOfDate.length === 0;
	}

	_isCirrusPageVersionOutOfDate( pageData ) {
		if ( !pageData.cirrusdoc || pageData.cirrusdoc.length === 0 ) {
			return true;
		}
		const cirrusVersion = pageData.cirrusdoc[ 0 ].source.version;
		const revId = pageData.revisions[ 0 ].revid;

		return cirrusVersion !== revId;
	}

}

module.exports = new CirrusUtil();
