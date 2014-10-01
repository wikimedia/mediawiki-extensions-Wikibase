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
MODULE.SiteLinkSetSerializer = util.inherit( 'WbSiteLinkSetSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.SiteLinkSet} siteLinkSet
	 * @return {Object}
	 */
	serialize: function( siteLinkSet ) {
		if( !( siteLinkSet instanceof wb.datamodel.SiteLinkSet ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.SiteLinkSet' );
		}

		var serialization = {},
			siteIds = siteLinkSet.getKeys(),
			siteLinkSerializer = new MODULE.SiteLinkSerializer();

		for( var i = 0; i < siteIds.length; i++ ) {
			serialization[siteIds[i]] = siteLinkSerializer.serialize(
				siteLinkSet.getItemByKey( siteIds[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
