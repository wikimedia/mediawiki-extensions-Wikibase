/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for parts of a Item Entity that are specific to Items.
 *
 * @constructor
 * @extends wikibase.Unserializer
 * @since 1.1
 */
var ItemUnserializationExpert =
	util.inherit( 'WbEntityUnserializerItemExpert', PARENT,
{
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {Object}
	 */
	unserialize: function( serialization ) {
		var siteLinks = [];

		for( var siteId in serialization.sitelinks ) {
			siteLinks.push( new wb.datamodel.SiteLink(
				siteId,
				serialization.sitelinks[siteId].title,
				serialization.sitelinks[siteId].badges
			) );
		}

		return {
			sitelinks: siteLinks
		};
	}
} );

// register to EntityUnserializer:
MODULE.EntityUnserializer.registerTypeSpecificExpert(
	wb.datamodel.Item.TYPE,
	ItemUnserializationExpert
);

}( wikibase, util ) );
