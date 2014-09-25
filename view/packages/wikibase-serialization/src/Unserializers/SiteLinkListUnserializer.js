/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for SiteLinkList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.SiteLinkListUnserializer = util.inherit( 'WbSiteLinkListUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.SiteLinkList}
	 */
	unserialize: function( serialization ) {
		var siteLinks = [],
			siteLinkUnserializer = new MODULE.SiteLinkUnserializer();

		for( var siteId in serialization ) {
			siteLinks.push( siteLinkUnserializer.unserialize( serialization[siteId] ) );
		}

		return new wikibase.datamodel.SiteLinkList( siteLinks );
	}
} );

}( wikibase, util ) );
