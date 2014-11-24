( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.ClaimListDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ClaimListDeserializer = util.inherit( 'WbClaimListDeserializer', PARENT, {
	/**
	 * @inheritdoc
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
