/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
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
			type: 'claim',
			mainsnak: snakSerializer.serialize( claim.getMainSnak() )
		};

		if( guid ) {
			serialization.id = guid;
		}

		if( qualifiers.length ) {
			serialization.qualifiers = snakListSerializer.serialize( qualifiers );
			serialization['qualifiers-order'] = qualifiers.getPropertyOrder();
		}

		return serialization;
	}
} );

}( wikibase, util ) );
