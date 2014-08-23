/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for SiteLink objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 1.2
 */
MODULE.SiteLinkUnserializer = util.inherit( 'WbSiteLinkUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @param {Object} serialization
	 * @return {wikibase.datamodel.SiteLink}
	 */
	unserialize: function( serialization ) {
		return new wb.datamodel.SiteLink(
			serialization.site,
			serialization.title,
			serialization.badges
		);
	}
} );

}( wikibase, util ) );
