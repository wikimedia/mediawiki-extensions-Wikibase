( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class SiteLinkDeserializer
	 * @extends Deserializer
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

}() );
