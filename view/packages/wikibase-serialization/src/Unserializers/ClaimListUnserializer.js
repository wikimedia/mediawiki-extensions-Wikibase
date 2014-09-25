/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for ClaimList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ClaimListUnserializer = util.inherit( 'WbClaimListUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.ClaimList}
	 */
	unserialize: function( serialization ) {
		var claims = [],
			claimUnserializer = new MODULE.ClaimUnserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			claims.push( claimUnserializer.unserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.ClaimList( claims );
	}
} );

}( wikibase, util ) );
