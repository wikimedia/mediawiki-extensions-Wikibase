/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization;

	/**
	 * Base for unserializers.
	 *
	 * @param {Object} [options]
	 *
	 * @constructor
	 * @abstract
	 * @since 1.0
	 */
	var SELF = MODULE.Unserializer = function WbUnserializer( options ) {
		if( options ) {
			this.setOptions( options );
		} else {
			this._options = {};
		}
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {Object}
		 */
		_options: null,

		/**
		 * Constructs the original object from the provided serialization.
		 *
		 * @param {Object} serialization
		 */
		unserialize: util.abstractFunction,

		/**
		 * Sets the unserializer's options.
		 *
		 * @param {Object} options
		 *
		 * @throws {Error} if options are not an object.
		 */
		setOptions: function( options ) {
			if( !$.isPlainObject( options ) ) {
				throw new Error( 'Options need to be an object' );
			}
			this._options = $.extend( {}, options );
		},

		/**
		 * Returns the unserializer's options.
		 *
		 * @return {Object}
		 */
		getOptions: function() {
			return $.extend( {}, this._options );
		}
	} );

}( wikibase, util, jQuery ) );
