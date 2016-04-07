( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.StatementListDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementListDeserializer = util.inherit( 'WbStatementListDeserializer', PARENT, {
	/**
	 * @inheritdoc
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
