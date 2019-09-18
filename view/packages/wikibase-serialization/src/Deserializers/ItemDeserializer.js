( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	FingerprintDeserializer = require( './FingerprintDeserializer.js' ),
	SiteLinkSetDeserializer = require( './SiteLinkSetDeserializer.js' );

/**
 * @class wikibase.serialization.ItemDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbItemDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.Item}
	 *
	 * @throws {Error} if serialization does not resolve to a serialized Item.
	 */
	deserialize: function( serialization ) {
		if( serialization.type !== wb.datamodel.Item.TYPE ) {
			throw new Error( 'Serialization does not resolve to an Item' );
		}

		var fingerprintDeserializer = new FingerprintDeserializer(),
			statementGroupSetDeserializer = new MODULE.StatementGroupSetDeserializer(),
			siteLinkSetDeserializer = new SiteLinkSetDeserializer();

		return new wb.datamodel.Item(
			serialization.id,
			fingerprintDeserializer.deserialize( serialization ),
			statementGroupSetDeserializer.deserialize( serialization.claims ),
			siteLinkSetDeserializer.deserialize( serialization.sitelinks )
		);
	}
} );

}( wikibase, util ) );
