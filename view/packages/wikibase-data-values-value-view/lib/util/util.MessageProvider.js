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
	 * @param {Object} defaultMessages Messages to use if no message getter function is provided or
	 *                 the getter does not return a message. The keys the messages are indexed with
	 *                 are passed to the message getter.
	 * @param {Function} [messageGetter] Function to retrieve a message from. The function receives
	 *                   the message key as first argument and and array containing the message
	 *                   parameters as second argument.
	 * @constructor
	 */
	function MessageProvider( defaultMessages, messageGetter ) {
		this._defaultMessages = defaultMessages;
		this._messageGetter = messageGetter || null;
	}

	MessageProvider.prototype = {
		constructor: MessageProvider,

		/**
		 * @type {Object}
		 */
		_defaultMessages: null,

		/**
		 * @type {Function|null}
		 */
		_messageGetter: null,

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

			var message = null;

			if( this._messageGetter ) {
				message = this._messageGetter( key, params );
			}

			if( !message && this._defaultMessages && this._defaultMessages[key] ) {
				message = this._defaultMessages[key];
			}

			return message || null;
		}

	};

	return MessageProvider;

}() );
