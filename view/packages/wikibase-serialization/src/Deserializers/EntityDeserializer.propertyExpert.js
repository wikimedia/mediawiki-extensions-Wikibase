/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Deserializer;

	/**
	 * Deserializer for parts of a Property Entity that are specific to Property entities.
	 *
	 * @constructor
	 * @extends {wikibase.serialization.Deserializer}
	 * @since 1.0
	 */
	var PropertyDeserializationExpert =
		util.inherit( 'WbEntityDeserializerPropertyExpert', PARENT,
	{
		/**
		 * @see wikibase.serialization.Deserializer.deserialize
		 *
		 * @return {Object}
		 */
		deserialize: function( serialization ) {
			var dataTypeId = serialization.datatype;
			if( !dataTypeId ) {
				throw new Error( 'Property Entity deserializer expects a "datatype" field' );
			}

			return {
				datatype: dataTypeId
			};
		}
	} );

	MODULE.EntityDeserializer.registerTypeSpecificExpert(
		wb.datamodel.Property.TYPE,
		PropertyDeserializationExpert
	);

}( wikibase, util ) );
