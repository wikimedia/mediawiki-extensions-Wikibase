/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for SiteLinkSet objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.SiteLinkSetDeserializer = util.inherit( 'WbSiteLinkSetDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.SiteLinkSet}
	 */
	deserialize: function( serialization ) {
		var siteLinks = [],
			siteLinkDeserializer = new MODULE.SiteLinkDeserializer();

		for( var siteId in serialization ) {
			siteLinks.push( siteLinkDeserializer.deserialize( serialization[siteId] ) );
		}

		return new wikibase.datamodel.SiteLinkSet( siteLinks );
	}
} );

}( wikibase, util ) );
