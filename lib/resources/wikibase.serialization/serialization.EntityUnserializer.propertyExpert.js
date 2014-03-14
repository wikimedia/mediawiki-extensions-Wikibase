/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, dataTypeStore ) {
	'use strict';

	var MODULE = wb.serialization,
		PARENT = MODULE.Unserializer;

	/**
	 * Unserializer for parts of a Property Entity that are specific to Properties.
	 *
	 * @constructor
	 * @extends wb.Unserializer
	 * @since 0.4
	 */
	var PropertyUnserializationExpert =
		util.inherit( 'WbEntityUnserializerPropertyExpert', PARENT,
	{
		/**
		 * @see wb.serialization.Unserializer.unserialize
		 *
		 * @return Object
		 */
		unserialize: function( serialization ) {
			var dataTypeId = serialization.datatype;
			if( !dataTypeId ) {
				throw new Error( 'Property Entity unserializer expects a "datatype" field' );
			}

			return {
				datatype: dataTypeStore.getDataType( serialization.datatype )
			};
		}
	} );

	// register to EntityUnserializer:
	MODULE.EntityUnserializer.registerTypeSpecificExpert(
		wb.Property.TYPE,
		PropertyUnserializationExpert
	);

}( wikibase, util, wikibase.dataTypes ) );
