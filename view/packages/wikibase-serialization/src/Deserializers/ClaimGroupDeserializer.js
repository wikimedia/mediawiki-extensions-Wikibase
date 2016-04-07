( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.ClaimGroupDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ClaimGroupDeserializer = util.inherit( 'WbClaimGroupDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.ClaimGroup}
	 *
	 * @throws {Error} if serialization is an empty object.
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
