( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		ReferenceListDeserializer = require( './ReferenceListDeserializer.js' ),
		ClaimDeserializer = require( './ClaimDeserializer.js' );

	/**
	 * @class StatementDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbStatementDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.Statement}
		 */
		deserialize: function( serialization ) {
			var claim = ( new ClaimDeserializer() ).deserialize( serialization ),
				references = null,
				rank = datamodel.Statement.RANK[serialization.rank.toUpperCase()];

			if( serialization.references !== undefined ) {
				var referenceDeserializer = new ReferenceListDeserializer();
				references = referenceDeserializer.deserialize( serialization.references );
			}

			return new datamodel.Statement( claim, references, rank );
		}
	} );

}() );
