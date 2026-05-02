this.util = this.util || {};

( function( util, $ ) {
	'use strict';

	/**
	 * A simple event-like system for plugging in extensions.
	 *
	 * @class util.Extendable
	 * @uses jQuery
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 */
	util.Extendable = function() {
		this._extensions = [];
	};

	/**
	 * @class util.Extendable
	 */
	util.Extendable.prototype = {
		/**
		 * The list of registered extensions.
		 *
		 * @private
		 *
		 * @property {Object[]} [_extensions=[]]
		 */
		_extensions: null,

		/**
		 * Adds an extension to the extendable.
		 *
		 * @param {Object} extension
		 */
		addExtension: function( extension ) {
			this._extensions.push( extension );
		},

		/**
		 * Calls a specific method on all registered extensions, if present.
		 *
		 * @param {string} callName The method to call on the extensions
		 * @param {*[]} [args=[]] Arguments to be passed to all extensions
		 */
		callExtensions: function( callName, args ) {
			args = args || [];
			$.each( this._extensions, function( key, ext ) {
				if ( ext[callName] ) {
					ext[callName].apply( ext, args );
				}
			} );
		}
	};
}( util, jQuery ) );
