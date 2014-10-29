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
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.StatementDeserializer = util.inherit( 'WbStatementDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.Statement}
	 */
	deserialize: function( serialization ) {
		var claim = ( new MODULE.ClaimDeserializer() ).deserialize( serialization ),
			references = null,
			rank = wb.datamodel.Statement.RANK[serialization.rank.toUpperCase()];

		if( serialization.references !== undefined ) {
			var referenceDeserializer = new MODULE.ReferenceListDeserializer();
			references = referenceDeserializer.deserialize( serialization.references );
		}

		return new wb.datamodel.Statement( claim, references, rank );
	}
} );

}( wikibase, util ) );
