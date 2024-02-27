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
	 *
	 * @constructor
	 */
	var SELF = MODULE.TempUserWatcher = function WbTempUserWatcher() {
		this._redirectUrl = null;
	};

	$.extend( SELF.prototype, {

		getRedirectUrl: function () {
			return this._redirectUrl;
		},

		/**
		 * Called from set{Label|Description|Alias} API calls to read
		 * tempuser information from API response.
		 */
		processApiResult: function ( result ) {
			if ( result.tempuserredirect ) {
				this._redirectUrl = result.tempuserredirect;
			}
		}
	} );

	module.exports = SELF;
}( wikibase ) );
