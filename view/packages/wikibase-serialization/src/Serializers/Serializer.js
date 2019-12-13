( function() {
	'use strict';

	/**
	 * Base for serializers.
	 * @class Serializer
	 * @abstract
	 * @since 1.0
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 */
	var SELF = function WbSerializationSerializer() {};

	$.extend( SELF.prototype, {
		/**
		 * Returns the serialized form of some object.
		 * @abstract
		 *
		 * @param {Object} object
		 */
		serialize: util.abstractFunction
	} );

	module.exports = SELF;
}() );
