/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for parts of an Item Entity that are specific to Item entities.
 *
 * @constructor
 * @extends {wikibase.serialization.Serializer}
 * @since 2.0
 */
var ItemSerializationExpert = util.inherit( 'WbEntitySerializerItemExpert', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Item} item
	 * @return {Object}
	 */
	serialize: function( item ) {
		if( !( item instanceof wb.datamodel.Item ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Item' );
		}

		var siteLinkSetSerializer = new MODULE.SiteLinkSetSerializer();

		return {
			sitelinks: siteLinkSetSerializer.serialize( item.getSiteLinks() )
		};
	}
} );

MODULE.EntitySerializer.registerTypeSpecificExpert(
	wb.datamodel.Item.TYPE,
	ItemSerializationExpert
);

}( wikibase, util ) );
