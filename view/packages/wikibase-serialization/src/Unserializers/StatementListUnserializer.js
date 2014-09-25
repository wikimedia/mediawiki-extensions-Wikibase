/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for StatementList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.StatementListUnserializer = util.inherit( 'WbStatementListUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.StatementList}
	 */
	unserialize: function( serialization ) {
		var statements = [],
			statementUnserializer = new MODULE.StatementUnserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			statements.push( statementUnserializer.unserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.StatementList( statements );
	}
} );

}( wikibase, util ) );
