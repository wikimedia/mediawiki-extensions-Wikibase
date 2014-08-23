/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for Claim objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 1.2
 */
MODULE.ClaimSerializer = util.inherit( 'WbClaimSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Claim} claim
	 * @return {Object}
	 */
	serialize: function( claim ) {
		if( !( claim instanceof wb.datamodel.Claim ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Claim' );
		}

		var snakSerializer = new MODULE.SnakSerializer(),
			snakListSerializer = new MODULE.SnakListSerializer(),
			guid = claim.getGuid(),
			qualifiers = claim.getQualifiers();

		var serialization = {
			type: claim.constructor.TYPE,
			mainsnak: snakSerializer.serialize( claim.getMainSnak() )
		};

		if( guid ) {
			serialization.id = guid;
		}

		if( qualifiers.length ) {
			serialization.qualifiers = snakListSerializer.serialize( qualifiers );
			serialization['qualifiers-order'] = qualifiers.getPropertyOrder();
		}

		if( !( claim instanceof wb.datamodel.Statement ) ) {
			return serialization;
		}

		var references = claim.getReferences(),
			referenceSerializer = new MODULE.ReferenceSerializer(),
			rank = claim.getRank();

		if( references.length ) {
			serialization.references = [];
			for( var i = 0; i < references.length; i++ ) {
				serialization.references.push( referenceSerializer.serialize( references[i] ) );
			}
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
