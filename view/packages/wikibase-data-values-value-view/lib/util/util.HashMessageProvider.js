this.util = this.util || {};

util.HashMessageProvider = ( function() {
	'use strict';

	/**
	 * Providing messages using specified default message
	 *
	 * @class util.HashMessageProvider
	 * @since 0.13.0
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {Object} messages
	 */
	function HashMessageProvider( messages ) {
		this._messages = messages;
	}

	/**
	 * @class util.HashMessageProvider
	 */
	HashMessageProvider.prototype = {
		constructor: HashMessageProvider,

		/**
		 * @property {Object}
		 * @private
		 */
		_messages: null,

		/**
		 * Returns a plain string message from the pre-defined hash.
		 *
		 * This ignores the passed parameters.
		 *
		 * @inheritdoc
		 */
		getMessage: function( key, params ) {
			return this._messages[ key ] || null;
		}

	};

	return HashMessageProvider;

}() );
