( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.ItemDeserializer
 * @extends {wikibase.serialization.Deserializer}
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ItemDeserializer = util.inherit( 'WbItemDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.Item}
	 */
	deserialize: function( serialization ) {
		if( serialization.type !== wb.datamodel.Item.TYPE ) {
			throw new Error( 'Serialization does not resolve to an Item' );
		}

		var fingerprintDeserializer = new MODULE.FingerprintDeserializer(),
			statementGroupSetDeserializer = new MODULE.StatementGroupSetDeserializer(),
			siteLinkSetDeserializer = new MODULE.SiteLinkSetDeserializer();

		return new wb.datamodel.Item(
			serialization.id,
			fingerprintDeserializer.deserialize( serialization ),
			statementGroupSetDeserializer.deserialize( serialization.claims ),
			siteLinkSetDeserializer.deserialize( serialization.sitelinks )
		);
	}
} );

}( wikibase, util ) );
