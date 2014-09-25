/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for Statement objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.StatementSerializer = util.inherit( 'WbStatementSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Statement} statement
	 * @return {Object}
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
