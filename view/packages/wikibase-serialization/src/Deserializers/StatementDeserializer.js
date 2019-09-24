( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
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
	 * @return {wikibase.datamodel.Statement}
	 */
	deserialize: function( serialization ) {
		var claim = ( new ClaimDeserializer() ).deserialize( serialization ),
			references = null,
			rank = wb.datamodel.Statement.RANK[serialization.rank.toUpperCase()];

		if( serialization.references !== undefined ) {
			var referenceDeserializer = new ReferenceListDeserializer();
			references = referenceDeserializer.deserialize( serialization.references );
		}

		return new wb.datamodel.Statement( claim, references, rank );
	}
} );

}( wikibase, util ) );
