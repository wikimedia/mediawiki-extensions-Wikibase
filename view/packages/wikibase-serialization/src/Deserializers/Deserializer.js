/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization;

	/**
	 * Base for deserializers.
	 *
	 * @param {Object} [options]
	 *
	 * @constructor
	 * @abstract
	 * @since 1.0
	 */
	var SELF = MODULE.Deserializer = function WbDeserializer( options ) {
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
		deserialize: util.abstractFunction,

		/**
		 * Sets the deserializer's options.
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
		 * Returns the deserializer's options.
		 *
		 * @return {Object}
		 */
		getOptions: function() {
			return $.extend( {}, this._options );
		}
	} );

}( wikibase, util, jQuery ) );
