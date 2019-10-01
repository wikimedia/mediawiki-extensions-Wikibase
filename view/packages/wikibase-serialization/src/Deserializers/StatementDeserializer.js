( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	datamodel = require( 'wikibase.datamodel' ),
	ReferenceListDeserializer = require( './ReferenceListDeserializer.js' ),
	ClaimDeserializer = require( './ClaimDeserializer.js' );

/**
 * @class StatementDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementDeserializer = util.inherit( 'WbStatementDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {datamodel.Statement}
	 */
	deserialize: function( serialization ) {
		var claim = ( new ClaimDeserializer() ).deserialize( serialization ),
			references = null,
			rank = datamodel.Statement.RANK[serialization.rank.toUpperCase()];

		if( serialization.references !== undefined ) {
			var referenceDeserializer = new ReferenceListDeserializer();
			references = referenceDeserializer.deserialize( serialization.references );
		}

		return new datamodel.Statement( claim, references, rank );
	}
} );

module.exports = MODULE.StatementDeserializer;
}( wikibase, util ) );
