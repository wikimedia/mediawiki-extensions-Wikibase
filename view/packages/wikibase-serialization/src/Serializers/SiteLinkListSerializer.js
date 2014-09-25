/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for SiteLinkList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.SiteLinkListSerializer = util.inherit( 'WbSiteLinkListSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.SiteLinkList} siteLinkList
	 * @return {Object}
	 */
	serialize: function( siteLinkList ) {
		if( !( siteLinkList instanceof wb.datamodel.SiteLinkList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.SiteLinkList' );
		}

		var serialization = {},
			siteIds = siteLinkList.getSiteIds(),
			siteLinkSerializer = new MODULE.SiteLinkSerializer();

		for( var i = 0; i < siteIds.length; i++ ) {
			serialization[siteIds[i]] = siteLinkSerializer.serialize(
				siteLinkList.getBySiteId( siteIds[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
