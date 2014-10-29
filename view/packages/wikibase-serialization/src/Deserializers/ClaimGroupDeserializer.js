/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.ClaimGroupDeserializer = util.inherit( 'WbClaimGroupDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.ClaimGroup}
	 */
	deserialize: function( serialization ) {
		if( $.isEmptyObject( serialization ) ) {
			throw new Error( 'Cannot deserialize empty serialization' );
		}

		var claimListDeserializer = new MODULE.ClaimListDeserializer(),
			claimList = claimListDeserializer.deserialize( serialization );

		return new wb.datamodel.ClaimGroup( claimList.getPropertyIds()[0], claimList );
	}
} );

}( wikibase, util, jQuery ) );
