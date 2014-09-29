/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for StatementList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.StatementListDeserializer = util.inherit( 'WbStatementListDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.StatementList}
	 */
	deserialize: function( serialization ) {
		var statements = [],
			statementDeserializer = new MODULE.StatementDeserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			statements.push( statementDeserializer.deserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.StatementList( statements );
	}
} );

}( wikibase, util ) );
