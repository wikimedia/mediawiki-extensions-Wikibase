/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.StatementGroupSetUnserializer = util.inherit( 'WbStatementGroupSetUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.StatementGroupSet}
	 */
	unserialize: function( serialization ) {
		var statemenGroupUnserializer = new MODULE.StatementGroupUnserializer(),
			statementGroups = [];

		for( var propertyId in serialization ) {
			statementGroups.push(
				statemenGroupUnserializer.unserialize( serialization[propertyId] )
			);
		}

		return new wb.datamodel.StatementGroupSet( statementGroups );
	}
} );

}( wikibase, util ) );
