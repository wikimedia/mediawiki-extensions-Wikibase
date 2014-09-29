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
MODULE.StatementGroupSetDeserializer = util.inherit( 'WbStatementGroupSetDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.StatementGroupSet}
	 */
	deserialize: function( serialization ) {
		var statemenGroupDeserializer = new MODULE.StatementGroupDeserializer(),
			statementGroups = [];

		for( var propertyId in serialization ) {
			statementGroups.push(
				statemenGroupDeserializer.deserialize( serialization[propertyId] )
			);
		}

		return new wb.datamodel.StatementGroupSet( statementGroups );
	}
} );

}( wikibase, util ) );
