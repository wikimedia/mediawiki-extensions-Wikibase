this.util = this.util || {};

util.CombiningMessageProvider = ( function() {
	'use strict';

	/**
	 * Providing messages using two `MessageProvider`s
	 *
	 * @class util.CombiningMessageProvider
	 * @since 0.13.0
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {util.MessageProvider} preferredMessageProvider
	 * @param {util.MessageProvider} alternativeMessageProvider
	 */
	function CombiningMessageProvider( preferredMessageProvider, alternativeMessageProvider ) {
		this._a = preferredMessageProvider;
		this._b = alternativeMessageProvider;
	}

	/**
	 * @class util.CombiningMessageProvider
	 */
	CombiningMessageProvider.prototype = {
		constructor: CombiningMessageProvider,

		/**
		 * @property {util.MessageProvider}
		 * @private
		 */
		_a: null,

		/**
		 * @property {util.MessageProvider}
		 * @private
		 */
		_b: null,

		/**
		 * Tries to get a message via the `MessageProvider`s.
		 *
		 * @inheritdoc
		 */
		getMessage: function( key, params ) {
			return this._a.getMessage( key, params ) || this._b.getMessage( key, params );
		}

	};

	return CombiningMessageProvider;

}() );
