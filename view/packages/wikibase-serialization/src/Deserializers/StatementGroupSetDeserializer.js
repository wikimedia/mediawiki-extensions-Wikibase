( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		StatementGroupDeserializer = require( './StatementGroupDeserializer.js' );

	/**
	 * @class StatementGroupSetDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbStatementGroupSetDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.StatementGroupSet}
		 */
		deserialize: function( serialization ) {
			var statemenGroupDeserializer = new StatementGroupDeserializer(),
				statementGroups = [];

			for( var propertyId in serialization ) {
				statementGroups.push(
					statemenGroupDeserializer.deserialize( serialization[propertyId] )
				);
			}

			return new datamodel.StatementGroupSet( statementGroups );
		}
	} );

}() );
