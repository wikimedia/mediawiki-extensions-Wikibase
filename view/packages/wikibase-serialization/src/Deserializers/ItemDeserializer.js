( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		Item = require( 'wikibase.datamodel' ).Item,
		StatementGroupSetDeserializer = require( './StatementGroupSetDeserializer.js' ),
		FingerprintDeserializer = require( './FingerprintDeserializer.js' ),
		SiteLinkSetDeserializer = require( './SiteLinkSetDeserializer.js' );

	/**
	 * @class ItemDeserializer
	 * @extends Deserializer
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
		 * @return {Item}
		 *
		 * @throws {Error} if serialization does not resolve to a serialized Item.
		 */
		deserialize: function( serialization ) {
			if( serialization.type !== Item.TYPE ) {
				throw new Error( 'Serialization does not resolve to an Item' );
			}

			var fingerprintDeserializer = new FingerprintDeserializer(),
				statementGroupSetDeserializer = new StatementGroupSetDeserializer(),
				siteLinkSetDeserializer = new SiteLinkSetDeserializer();

			return new Item(
				serialization.id,
				fingerprintDeserializer.deserialize( serialization ),
				statementGroupSetDeserializer.deserialize( serialization.claims ),
				siteLinkSetDeserializer.deserialize( serialization.sitelinks )
			);
		}
	} );

}() );
