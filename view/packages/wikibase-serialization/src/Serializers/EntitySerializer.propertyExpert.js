/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for parts of an Item Entity that are specific to Property entities.
 *
 * @constructor
 * @extends {wikibase.serialization.Serializer}
 * @since 2.0
 */
var PropertySerializationExpert =
	util.inherit( 'WbEntitySerializerPropertyExpert', PARENT,
{
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Property} property
	 * @return {Object}
	 */
	serialize: function( property ) {
		if( !( property instanceof wb.datamodel.Property ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Property' );
		}

		return {
			datatype: property.getDataTypeId()
		};
	}
} );

MODULE.EntitySerializer.registerTypeSpecificExpert(
	wb.datamodel.Property.TYPE,
	PropertySerializationExpert
);

}( wikibase, util ) );
