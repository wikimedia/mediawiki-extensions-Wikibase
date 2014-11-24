( function( wb, util, $ ) {
	'use strict';

	var MODULE = wb.serialization;

	/**
	 * Base for deserializers.
	 * @class wikibase.serialization.Deserializer
	 * @abstract
	 * @since 1.0
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @constructor
	 */
	var SELF = MODULE.Deserializer = function WbDeserializer() {};

	$.extend( SELF.prototype, {
		/**
		 * Constructs the original object from the provided serialization.
		 *
		 * @param {Object} serialization
		 */
		deserialize: util.abstractFunction
	} );

}( wikibase, util, jQuery ) );
