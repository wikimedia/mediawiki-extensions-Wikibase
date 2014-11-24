( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.SiteLinkSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.SiteLinkSerializer = util.inherit( 'WbSiteLinkSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.SiteLink} siteLink
	 * @return {Object}
	 */
	serialize: function( siteLink ) {
		if( !( siteLink instanceof wb.datamodel.SiteLink ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.SiteLink' );
		}

		return {
			site: siteLink.getSiteId(),
			title: siteLink.getPageName(),
			badges: siteLink.getBadges()
		};
	}
} );

}( wikibase, util ) );
