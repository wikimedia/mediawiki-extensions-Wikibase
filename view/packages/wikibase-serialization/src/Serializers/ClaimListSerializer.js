( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.ClaimListSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ClaimListSerializer = util.inherit( 'WbClaimListSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.ClaimList} claimList
	 * @return {Object[]}
	 *
	 * @throws {Error} if claimList is not a ClaimList instance.
	 */
	serialize: function( claimList ) {
		if( !( claimList instanceof wb.datamodel.ClaimList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.ClaimList' );
		}

		var serialization = [],
			claimSerializer = new MODULE.ClaimSerializer(),
			claims = claimList.toArray();

		for( var i = 0; i < claims.length; i++ ) {
			serialization.push( claimSerializer.serialize( claims[i] ) );
		}

		return serialization;
	}
} );

}( wikibase, util ) );
