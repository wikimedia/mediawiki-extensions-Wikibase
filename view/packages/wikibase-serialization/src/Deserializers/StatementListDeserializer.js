( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		StatementDeserializer = require( './StatementDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class StatementListDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbStatementListDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.StatementList}
		 */
		deserialize: function( serialization ) {
			var statements = [],
				statementDeserializer = new StatementDeserializer();

			for( var i = 0; i < serialization.length; i++ ) {
				statements.push( statementDeserializer.deserialize( serialization[i] ) );
			}

			return new datamodel.StatementList( statements );
		}
	} );

}() );
