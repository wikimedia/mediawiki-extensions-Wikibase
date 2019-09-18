( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	FingerprintDeserializer = require( './FingerprintDeserializer.js' );

/**
 * @class wikibase.serialization.PropertyDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbPropertyDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.Property}
	 *
	 * @throws {Error} if serialization does not resolve to a serialized Property.
	 */
	deserialize: function( serialization ) {
		if( serialization.type !== wb.datamodel.Property.TYPE ) {
			throw new Error( 'Serialization does not resolve to a Property' );
		}

		var fingerprintDeserializer = new FingerprintDeserializer(),
			statementGroupSetDeserializer = new MODULE.StatementGroupSetDeserializer();

		return new wb.datamodel.Property(
			serialization.id,
			serialization.datatype,
			fingerprintDeserializer.deserialize( serialization ),
			statementGroupSetDeserializer.deserialize( serialization.claims )
		);
	}
} );

}( wikibase, util ) );
