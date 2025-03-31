/**
 * JavaScript storing revision ids about different sections.
 *
 * @license GPL-2.0-or-later
 * @author Arthur Taylor
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb.entityChangers;

	/**
	 * Supports tracking of and responding to the creation
	 * of TempUsers during API requests
	 */
	MODULE.TempUserWatcher = class {
		constructor() {
			this._redirectUrl = null;
		}

		getRedirectUrl() {
			return this._redirectUrl;
		}

		/**
		 * Called from set{Label|Description|Alias} API calls to read
		 * tempuser information from API response.
		 */
		processApiResult( result ) {
			if ( result.tempuserredirect ) {
				this._redirectUrl = result.tempuserredirect;
			}
		}
	};
}( wikibase ) );
