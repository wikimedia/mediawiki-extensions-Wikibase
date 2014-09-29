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
	 * @constructor
	 * @abstract
	 * @since 1.0
	 */
	var SELF = MODULE.Serializer = function WbSerializer() {};

	$.extend( SELF.prototype, {
		/**
		 * Returns the serialized form of some object.
		 *
		 * @param {Object} object
		 */
		serialize: util.abstractFunction
	} );

}( wikibase, util, jQuery ) );
