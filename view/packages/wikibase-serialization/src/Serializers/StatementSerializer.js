( function() {
	'use strict';

	var PARENT = require( './Serializer.js' ),
		ClaimSerializer = require( './ClaimSerializer.js' ),
		ReferenceListSerializer = require( './ReferenceListSerializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class StatementSerializer
	 * @extends Serializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbStatementSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {datamodel.Statement} statement
		 * @return {Object}
		 *
		 * @throws {Error} if statement is not a Statement instance.
		 */
		serialize: function( statement ) {
			if( !( statement instanceof datamodel.Statement ) ) {
				throw new Error( 'Not an instance of datamodel.Statement' );
			}

			var claimSerializer = new ClaimSerializer(),
				referenceListSerializer = new ReferenceListSerializer(),
				references = statement.getReferences(),
				rank = statement.getRank();

			var serialization = claimSerializer.serialize( statement.getClaim() );
			serialization.type = 'statement';

			if( references.length ) {
				serialization.references = referenceListSerializer.serialize( references );
			}

			if( rank !== undefined ) {
				for( var rankName in datamodel.Statement.RANK ) {
					if( rank === datamodel.Statement.RANK[rankName] ) {
						serialization.rank = rankName.toLowerCase();
						break;
					}
				}
			}

			return serialization;
		}
	} );

}() );
