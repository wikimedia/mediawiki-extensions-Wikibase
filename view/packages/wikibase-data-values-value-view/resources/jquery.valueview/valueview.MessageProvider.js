/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
jQuery.valueview.MessageProvider = ( function( $ ) {
	'use strict';

	/**
	 * Providing messages using specified default messages or MediaWiki messages if available.
	 *
	 * @param {Object} defaultMessages Messages to use if mediaWiki is not available. These should
	 *                 be keyed by the message key that is used for the corresponding MediaWiki
	 *                 message. The value is the replacement message to use when the MessageProvider
	 *                 is not loaded within MediaWiki context.
	 * @param {Object} [mediaWiki] mediaWiki JavaScript object.
	 * @constructor
	 */
	function MessageProvider( defaultMessages, mediaWiki ) {
		this._defaultMessages = defaultMessages;
		this._mw = mediaWiki;
	}

	$.extend( MessageProvider.prototype, {

		/**
		 * Default messages to use if the MessageProvider is not loaded within MediaWiki context.
		 * @type {Object}
		 */
		_defaultMessages: null,

		/**
		 * Reference to the mediaWiki JavaScript object.
		 * @type {Object}
		 */
		_mw: null,

		/**
		 * Tries to get a message via mediaWiki (if set) or from the default options.
		 *
		 * @param {string} key
		 * @param {string[]} [params] Message parameters (forwarded to mediaWiki messages only).
		 * @return {string|null}
		 */
		getMessage: function( key, params ) {
			params = params || [];

			if( this._mw ) {
				return this._mw.msg( key, params );
			}

			if( this._defaultMessages && this._defaultMessages && this._defaultMessages[key] ) {
				return this._defaultMessages[key];
			}

			return null;
		}

	} );

	return MessageProvider;

}( jQuery ) );
