/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for lists of Claims.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ClaimsUnserializer = util.inherit( 'WbClaimsUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Claim[]}
	 */
	unserialize: function( serialization ) {
		var claims = [],
			claimUnserializer = new MODULE.ClaimUnserializer();

		if( !serialization ) {
			return claims;
		}

		for( var propId in serialization || {} ) {
			var claimsPerProp = serialization[propId];

			for( var i = 0; i < claimsPerProp.length; i++ ) {
				var serializedClaim = claimsPerProp[i];
				claims.push( claimUnserializer.unserialize( serializedClaim ) );
			}
		}
		return claims;
	}
} );

}( wikibase, util ) );
