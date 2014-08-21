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
 * Unserializer for multilingual values.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 1.2
 */
MODULE.ClaimsUnserializer = util.inherit( 'WbClaimsUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Claim[]}
	 */
	unserialize: function( serialization ) {
		var claims = [];

		if( !serialization ) {
			return claims;
		}

		for( var propId in serialization || {} ) {
			var claimsPerProp = serialization[propId];

			for( var i = 0; i < claimsPerProp.length; i++ ) {
				var serializedClaim = claimsPerProp[i],
					// TODO: use ClaimUnserializer here after it got implemented
					claim = wb.datamodel.Claim.newFromJSON( serializedClaim );

				claims.push( claim );
			}
		}
		return claims;
	}
} );

}( wikibase, util ) );
