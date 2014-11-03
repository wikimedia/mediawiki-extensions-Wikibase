/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */

this.util = this.util || {};

( function( util, $ ) {
	'use strict';

	/**
	 * A simple event-like system for plugging in extensions
	 *
	 * @constructor
	 */
	util.Extendable = function() {
		this._extensions = [];
	};

	util.Extendable.prototype = {
		/**
		 * The list of registered extensions
		 *
		 * @type {Object[]}
		 */
		_extensions: null,

		/**
		 * Add an extension to the extendable
		 *
		 * @param {Object} extension
		 */
		addExtension: function( extension ) {
			this._extensions.push( extension );
		},

		/**
		 * Call a specific method on all registered extensions, if present
		 *
		 * @param string callName The method to call on the extensions
		 * @param [Array] args Arguments to be passed to all extensions
		 */
		callExtensions: function( callName, args ) {
			args = args || [];
			$.each( this._extensions, function( key, ext ) {
				if( ext[callName] ) {
					ext[callName].apply( ext, args );
				}
			} );
		}
	};
}( util, jQuery ) );
