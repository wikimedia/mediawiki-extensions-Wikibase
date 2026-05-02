this.util = this.util || {};

util.PrefixingMessageProvider = ( function() {
	'use strict';

	/**
	 * Calls another `MessageProvider` with prefixed message keys
	 *
	 * @class util.PrefixingMessageProvider
	 * @since 0.13.0
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {string} prefix
	 * @param {util.MessageProvider} messageProvider
	 */
	function PrefixingMessageProvider( prefix, messageProvider ) {
		this._prefix = prefix;
		this._messageProvider = messageProvider;
	}

	/**
	 * @class util.PrefixingMessageProvider
	 */
	PrefixingMessageProvider.prototype = {
		constructor: PrefixingMessageProvider,

		/**
		 * @property {string}
		 * @private
		 */
		_prefix: null,

		/**
		 * @property {util.MessageProvider}
		 * @private
		 */
		_messageProvider: null,

		/**
		 * Tries to get a message via the provided `MessageProvider`.
		 *
		 * @inheritdoc
		 */
		getMessage: function( key, params ) {
			return this._messageProvider.getMessage( this._prefix + key, params );
		}

	};

	return PrefixingMessageProvider;

}() );
