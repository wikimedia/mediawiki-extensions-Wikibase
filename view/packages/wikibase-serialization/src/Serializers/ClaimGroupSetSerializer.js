( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.ClaimGroupSetSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ClaimGroupSetSerializer = util.inherit( 'WbClaimGroupSetSerializer', PARENT, {
	/**
	 * @inheritdoc
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
				claimGroupSet.getItemByKey( propertyIds[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
