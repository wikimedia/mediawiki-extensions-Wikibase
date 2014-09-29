/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ClaimGroupSetUnserializer = util.inherit( 'WbClaimGroupSetUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.ClaimGroupSet}
	 */
	unserialize: function( serialization ) {
		var statemenGroupUnserializer = new MODULE.ClaimGroupUnserializer(),
			claimGroups = [];

		for( var propertyId in serialization ) {
			claimGroups.push(
				statemenGroupUnserializer.unserialize( serialization[propertyId] )
			);
		}

		return new wb.datamodel.ClaimGroupSet( claimGroups );
	}
} );

}( wikibase, util ) );
