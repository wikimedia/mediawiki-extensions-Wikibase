/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for SiteLinkSet objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.SiteLinkSetUnserializer = util.inherit( 'WbSiteLinkSetUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.SiteLinkSet}
	 */
	unserialize: function( serialization ) {
		var siteLinks = [],
			siteLinkUnserializer = new MODULE.SiteLinkUnserializer();

		for( var siteId in serialization ) {
			siteLinks.push( siteLinkUnserializer.unserialize( serialization[siteId] ) );
		}

		return new wikibase.datamodel.SiteLinkSet( siteLinks );
	}
} );

}( wikibase, util ) );
