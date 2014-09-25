/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for StatementList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.StatementListSerializer = util.inherit( 'WbStatementListSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.StatementList} statementList
	 * @return {Object[]}
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
