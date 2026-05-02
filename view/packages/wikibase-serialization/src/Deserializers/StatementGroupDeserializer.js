( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		StatementListDeserializer = require( './StatementListDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class StatementGroupDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbStatementGroupDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.StatementGroup}
		 *
		 * @throws {Error} if serialization is an empty object.
		 */
		deserialize: function( serialization ) {
			if( $.isEmptyObject( serialization ) ) {
				throw new Error( 'Cannot deserialize empty serialization' );
			}

			var statementListDeserializer = new StatementListDeserializer(),
				statementList = statementListDeserializer.deserialize( serialization );

			return new datamodel.StatementGroup( statementList.getPropertyIds()[0], statementList );
		}
	} );

}() );
