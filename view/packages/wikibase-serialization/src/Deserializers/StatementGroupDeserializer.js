( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.StatementGroupDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementGroupDeserializer = util.inherit( 'WbStatementGroupDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.StatementGroup}
	 *
	 * @throws {Error} if serialization is an empty object.
	 */
	deserialize: function( serialization ) {
		if( $.isEmptyObject( serialization ) ) {
			throw new Error( 'Cannot deserialize empty serialization' );
		}

		var statementListDeserializer = new MODULE.StatementListDeserializer(),
			statementList = statementListDeserializer.deserialize( serialization );

		return new wb.datamodel.StatementGroup( statementList.getPropertyIds()[0], statementList );
	}
} );

}( wikibase, util, jQuery ) );
