( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		StatementGroupSetDeserializer = require( './StatementGroupSetDeserializer.js' ),
		FingerprintDeserializer = require( './FingerprintDeserializer.js' );

	/**
	 * @class PropertyDeserializer
	 * @extends Deserializer
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
		 * @return {datamodel.Property}
		 *
		 * @throws {Error} if serialization does not resolve to a serialized Property.
		 */
		deserialize: function( serialization ) {
			if( serialization.type !== datamodel.Property.TYPE ) {
				throw new Error( 'Serialization does not resolve to a Property' );
			}

			var fingerprintDeserializer = new FingerprintDeserializer(),
				statementGroupSetDeserializer = new StatementGroupSetDeserializer();

			return new datamodel.Property(
				serialization.id,
				serialization.datatype,
				fingerprintDeserializer.deserialize( serialization ),
				statementGroupSetDeserializer.deserialize( serialization.claims )
			);
		}
	} );

}() );
