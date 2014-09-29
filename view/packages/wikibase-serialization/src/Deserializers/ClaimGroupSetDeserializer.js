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
MODULE.ClaimGroupSetDeserializer = util.inherit( 'WbClaimGroupSetDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.ClaimGroupSet}
	 */
	deserialize: function( serialization ) {
		var statemenGroupDeserializer = new MODULE.ClaimGroupDeserializer(),
			claimGroups = [];

		for( var propertyId in serialization ) {
			claimGroups.push(
				statemenGroupDeserializer.deserialize( serialization[propertyId] )
			);
		}

		return new wb.datamodel.ClaimGroupSet( claimGroups );
	}
} );

}( wikibase, util ) );
