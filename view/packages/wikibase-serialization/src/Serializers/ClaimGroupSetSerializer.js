/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.ClaimGroupSetSerializer = util.inherit( 'WbClaimGroupSetSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.ClaimGroupSet} claimGroupSet
	 * @return {Object}
	 */
	serialize: function( claimGroupSet ) {
		if( !( claimGroupSet instanceof wb.datamodel.ClaimGroupSet ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.ClaimGroupSet' );
		}

		var serialization = {},
			propertyIds = claimGroupSet.getKeys(),
			claimGroupSerializer = new MODULE.ClaimGroupSerializer();

		for( var i = 0; i < propertyIds.length; i++ ) {
			serialization[propertyIds[i]] = claimGroupSerializer.serialize(
				claimGroupSet.getByKey( propertyIds[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
