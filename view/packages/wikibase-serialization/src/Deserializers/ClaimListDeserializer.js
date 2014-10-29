/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.ClaimListDeserializer = util.inherit( 'WbClaimListDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.ClaimList}
	 */
	deserialize: function( serialization ) {
		var claims = [],
			claimDeserializer = new MODULE.ClaimDeserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			claims.push( claimDeserializer.deserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.ClaimList( claims );
	}
} );

}( wikibase, util ) );
