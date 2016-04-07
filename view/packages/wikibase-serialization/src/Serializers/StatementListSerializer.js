( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.StatementListSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementListSerializer = util.inherit( 'WbStatementListSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.StatementList} statementList
	 * @return {Object[]}
	 *
	 * @throws {Error} if statementList is not a StatementList instance.
	 */
	serialize: function( statementList ) {
		if( !( statementList instanceof wb.datamodel.StatementList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.StatementList' );
		}

		var serialization = [],
			statementSerializer = new MODULE.StatementSerializer(),
			statements = statementList.toArray();

		for( var i = 0; i < statements.length; i++ ) {
			serialization.push( statementSerializer.serialize( statements[i] ) );
		}

		return serialization;
	}
} );

}( wikibase, util ) );
