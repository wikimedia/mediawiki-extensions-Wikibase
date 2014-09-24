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
 * @since 2.0
 */
MODULE.ClaimsSerializer = util.inherit( 'WbClaimsSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Claim[]} claims
	 * @return {Object}
	 */
	serialize: function( claims ) {
		var claimSerializer = new MODULE.ClaimSerializer(),
			serialization = {};

		for( var i = 0; i < claims.length; i++ ) {
			var propertyId = claims[i].getMainSnak().getPropertyId();

			if( !serialization[propertyId] ) {
				serialization[propertyId] = [];
			}

			serialization[propertyId].push( claimSerializer.serialize( claims[i] ) );
		}

		return serialization;
	}
} );

}( wikibase, util ) );
