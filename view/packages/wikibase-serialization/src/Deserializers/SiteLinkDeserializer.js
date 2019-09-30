( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.SiteLinkDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbSiteLinkDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {Object} serialization
	 * @return {datamodel.SiteLink}
	 */
	deserialize: function( serialization ) {
		return new datamodel.SiteLink(
			serialization.site,
			serialization.title,
			serialization.badges
		);
	}
} );

}( wikibase, util ) );
