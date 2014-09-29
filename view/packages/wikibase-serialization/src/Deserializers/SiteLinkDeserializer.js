/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.SiteLinkDeserializer = util.inherit( 'WbSiteLinkDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
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
