( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.SiteLinkDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.SiteLinkDeserializer = util.inherit( 'WbSiteLinkDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {Object} serialization
	 * @return {wikibase.datamodel.SiteLink}
	 */
	deserialize: function( serialization ) {
		return new wb.datamodel.SiteLink(
			serialization.site,
			serialization.title,
			serialization.badges
		);
	}
} );

}( wikibase, util ) );
