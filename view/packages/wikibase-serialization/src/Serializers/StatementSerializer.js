( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.StatementSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementSerializer = util.inherit( 'WbStatementSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {Object}
	 *
	 * @throws {Error} if statement is not a Statement instance.
	 */
	serialize: function( statement ) {
		if( !( statement instanceof wb.datamodel.Statement ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Statement' );
		}

		var claimSerializer = new MODULE.ClaimSerializer(),
			referenceListSerializer = new MODULE.ReferenceListSerializer(),
			references = statement.getReferences(),
			rank = statement.getRank();

		var serialization = claimSerializer.serialize( statement.getClaim() );
		serialization.type = 'statement';

		if( references.length ) {
			serialization.references = referenceListSerializer.serialize( references );
		}

		if( rank !== undefined ) {
			for( var rankName in wb.datamodel.Statement.RANK ) {
				if( rank === wb.datamodel.Statement.RANK[rankName] ) {
					serialization.rank = rankName.toLowerCase();
					break;
				}
			}
		}

		return serialization;
	}
} );

}( wikibase, util ) );
