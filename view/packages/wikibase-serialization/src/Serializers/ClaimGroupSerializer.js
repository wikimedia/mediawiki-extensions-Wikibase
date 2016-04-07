( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.ClaimGroupSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ClaimGroupSerializer = util.inherit( 'WbClaimGroupSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.ClaimGroup} claimGroup
	 * @return {Object}
	 *
	 * @throws {Error} if claimGroup is not a ClaimGroup instance.
	 */
	serialize: function( claimGroup ) {
		if( !( claimGroup instanceof wb.datamodel.ClaimGroup ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.ClaimGroup' );
		}

		var claimListSerializer = new MODULE.ClaimListSerializer();

		return claimListSerializer.serialize( claimGroup.getItemContainer() );
	}
} );

}( wikibase, util ) );
