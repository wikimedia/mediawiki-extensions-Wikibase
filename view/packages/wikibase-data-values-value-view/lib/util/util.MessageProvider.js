this.util = this.util || {};

util.MessageProvider = ( function( util ) {
	'use strict';

	/**
	 * @class util.MessageProvider
	 * @abstract
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 */
	function MessageProvider() {
	}

	/**
	 * @class util.MessageProvider
	 */
	MessageProvider.prototype = {
		/**
		 * Tries to get a message
		 *
		 * @param {string} key
		 * @param {string[]} [params=[]] Message parameters.
		 * @return {string|null}
		 */
		getMessage: util.abstractMember

	};

	return MessageProvider;

}( util ) );
