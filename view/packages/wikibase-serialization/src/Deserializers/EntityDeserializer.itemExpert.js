/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for parts of an Item Entity that are specific to Items.
 *
 * @constructor
 * @extends {wikibase.serialization.Deserializer}
 * @since 1.1
 */
var ItemDeserializationExpert =
	util.inherit( 'WbEntityDeserializerItemExpert', PARENT,
{
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {Object}
	 */
	deserialize: function( serialization ) {
		return ( new MODULE.SiteLinkSetDeserializer() ).deserialize( serialization.sitelinks );
	}
} );

MODULE.EntityDeserializer.registerTypeSpecificExpert(
	wb.datamodel.Item.TYPE,
	ItemDeserializationExpert
);

}( wikibase, util ) );
