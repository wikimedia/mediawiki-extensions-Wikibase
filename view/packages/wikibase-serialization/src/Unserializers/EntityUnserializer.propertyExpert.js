/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Unserializer;

	/**
	 * Unserializer for parts of a Property Entity that are specific to Property entities.
	 *
	 * @constructor
	 * @extends {wikibase.serialization.Unserializer}
	 * @since 1.0
	 */
	var PropertyUnserializationExpert =
		util.inherit( 'WbEntityUnserializerPropertyExpert', PARENT,
	{
		/**
		 * @see wikibase.serialization.Unserializer.unserialize
		 *
		 * @return {Object}
		 */
		unserialize: function( serialization ) {
			var dataTypeId = serialization.datatype;
			if( !dataTypeId ) {
				throw new Error( 'Property Entity unserializer expects a "datatype" field' );
			}

			return {
				datatype: dataTypeId
			};
		}
	} );

	MODULE.EntityUnserializer.registerTypeSpecificExpert(
		wb.datamodel.Property.TYPE,
		PropertyUnserializationExpert
	);

}( wikibase, util ) );
