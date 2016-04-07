( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.StatementGroupSetDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementGroupSetDeserializer = util.inherit( 'WbStatementGroupSetDeserializer', PARENT, {
	/**
	 * @inheritdoc
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
