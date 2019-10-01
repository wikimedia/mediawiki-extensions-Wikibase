( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.SiteLinkSetSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.SiteLinkSetSerializer = util.inherit( 'WbSiteLinkSetSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {datamodel.SiteLinkSet} siteLinkSet
	 * @return {Object}
	 *
	 * @throws {Error} if siteLinkSet is not a SiteLinkSet instance.
	 */
	serialize: function( siteLinkSet ) {
		if( !( siteLinkSet instanceof datamodel.SiteLinkSet ) ) {
			throw new Error( 'Not an instance of datamodel.SiteLinkSet' );
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
