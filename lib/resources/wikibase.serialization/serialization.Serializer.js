/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.serialization;

	/**
	 * Base for serializers.
	 *
	 * @param {Object} options
	 *
	 * @constructor
	 * @abstract
	 * @since 0.4
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
		 * @type Object
		 */
		_options: null,

		/**
		 * Returns the serialized form of some object.
		 *
		 * @since 0.4
		 *
		 * @param {Object} object
		 */
		serialize: wb.utilities.abstractFunction,

		/**
		 * Sets the serializer's options without just keeping a reference to the given object.
		 *
		 * @since 0.4
		 *
		 * @param options
		 */
		setOptions: function( options ) {
			this._options = $.extend( {}, options );
		},

		/**
		 * Returns the serializer's options. Changing the returned object will have no affect on the
		 * serializer's actual options until they are set via setOptions.
		 *
		 * @since 0.4
		 *
		 * @return Object
		 */
		getOptions: function() {
			return $.extend( {}, this._options );
		}
	} );

}( wikibase, jQuery ) );
