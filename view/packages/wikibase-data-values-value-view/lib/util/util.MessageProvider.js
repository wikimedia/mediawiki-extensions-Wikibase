/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
this.util = this.util || {};

util.MessageProvider = ( function() {
	'use strict';

	/**
	 * Providing messages using specified default messages or a function provided.
	 *
	 * @param {Object} options Object that may contain the following key-value pairs:
	 *                 - {Object} defaultMessages
	 *                   Messages to use if no message getter function is provided or the getter
	 *                   does not return a message. The keys the messages are indexed with are
	 *                   passed to the message getter.
	 *                 - {Function} messageGetter
	 *                   Function to retrieve a message from. The function receives the message key
	 *                   as first argument and a variable number of arguments as message parameters.
	 *                 - {string} prefix
	 *                   String the message key should be prefixed with when querying the message
	 *                   getter function.
	 * @constructor
	 */
	function MessageProvider( options ) {
		this._options = options || {};
	}

	MessageProvider.prototype = {
		constructor: MessageProvider,

		/**
		 * @type {Object}
		 */
		_options: null,

		/**
		 * Tries to get a message via the message getter. If the getter is not set or no message is
		 * returned by it, and a corresponding default message is set, the default message is
		 * returned.
		 *
		 * @param {string} key
		 * @param {string[]} [params] Message parameters.
		 * @return {string|null}
		 */
		getMessage: function( key, params ) {
			params = params || [];

			var o = this._options,
				message = null;

			if( o.messageGetter ) {
				if( o.prefix ) {
					key = o.prefix + key;
				}
				message = o.messageGetter.apply( null, [ key ].concat( params ) );
			}

			if( !message && o.defaultMessages && o.defaultMessages[key] ) {
				message = o.defaultMessages[key];
			}

			return message || null;
		},

		/**
		 * Sets the default messages.
		 *
		 * @param {Object} messages
		 */
		setDefaultMessages: function( messages ) {
			this._options.defaultMessages = messages;
		}

	};

	return MessageProvider;

}() );
