/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization;

	/**
	 * Base for serializers.
	 *
	 * @param {Object} [options]
	 *
	 * @constructor
	 * @abstract
	 * @since 1.0
	 */
	var SELF = MODULE.Serializer = function WbSerializer( options ) {
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
		 * Returns the serialized form of some object.
		 *
		 * @param {Object} object
		 */
		serialize: util.abstractFunction,

		/**
		 * Sets the serializer's options.
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
		 * Returns the serializer's options.
		 *
		 * @return {Object}
		 */
		getOptions: function() {
			return $.extend( {}, this._options );
		}
	} );

}( wikibase, util, jQuery ) );
