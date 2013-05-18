/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.serialization,
		SELF;

	/**
	 * Base for unserializers.
	 *
	 * @param {Object} [options]
	 *
	 * @constructor
	 * @abstract
	 * @since 0.4
	 */
	SELF = MODULE.Unserializer = function WbUnserializer( options ) {
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
		 * Constructs the original object from the provided serialization.
		 *
		 * @since 0.4
		 *
		 * @param {Object} serialization
		 */
		unserialize: wb.utilities.abstractFunction,

		/**
		 * Sets the unserializer's options without just keeping a reference to the given object.
		 *
		 * @since 0.4
		 *
		 * @param options
		 */
		setOptions: function( options ) {
			this._options = $.extend( {}, options );
		},

		/**
		 * Returns the unserializer's options. Changing the returned object will have no affect on the
		 * unserializer's actual options until they are set via setOptions.
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
