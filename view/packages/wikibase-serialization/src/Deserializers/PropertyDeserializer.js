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
 * @extends {wikibase.serialization.Deserializer}
 * @since 2.0
 */
MODULE.PropertyDeserializer = util.inherit( 'WbPropertyDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.Property}
	 */
	deserialize: function( serialization ) {
		if( serialization.type !== wb.datamodel.Property.TYPE ) {
			throw new Error( 'Serialization does not resolve to a Property' );
		}

		var fingerprintDeserializer = new MODULE.FingerprintDeserializer(),
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
