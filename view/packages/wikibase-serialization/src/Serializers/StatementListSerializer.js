( function() {
	'use strict';

	var PARENT = require( './Serializer.js' ),
		StatementSerializer = require( './StatementSerializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class StatementListSerializer
	 * @extends Serializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbStatementListSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {datamodel.StatementList} statementList
		 * @return {Object[]}
		 *
		 * @throws {Error} if statementList is not a StatementList instance.
		 */
		serialize: function( statementList ) {
			if( !( statementList instanceof datamodel.StatementList ) ) {
				throw new Error( 'Not an instance of datamodel.StatementList' );
			}

			var serialization = [],
				statementSerializer = new StatementSerializer(),
				statements = statementList.toArray();

			for( var i = 0; i < statements.length; i++ ) {
				serialization.push( statementSerializer.serialize( statements[i] ) );
			}

			return serialization;
		}
	} );

}() );
