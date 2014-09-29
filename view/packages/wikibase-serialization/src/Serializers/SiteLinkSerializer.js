/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.SiteLinkSerializer = util.inherit( 'WbSiteLinkSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 * @return {Object}
	 */
	serialize: function( siteLink ) {
		return {
			site: siteLink.getSiteId(),
			title: siteLink.getPageName(),
			badges: siteLink.getBadges()
		};
	}
} );

}( wikibase, util ) );
