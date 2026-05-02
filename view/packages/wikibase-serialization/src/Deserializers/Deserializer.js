( function() {
	'use strict';

	/**
	 * Base for deserializers.
	 * @class Deserializer
	 * @abstract
	 * @since 1.0
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 */
	var SELF = function WbSerializationDeserializer() {};

	$.extend( SELF.prototype, {
		/**
		 * Constructs the original object from the provided serialization.
		 * @abstract
		 *
		 * @param {Object} serialization
		 */
		deserialize: util.abstractFunction
	} );

	module.exports = SELF;
}() );
