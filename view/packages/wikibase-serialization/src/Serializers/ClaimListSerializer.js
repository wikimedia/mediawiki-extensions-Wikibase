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
MODULE.ClaimListSerializer = util.inherit( 'WbClaimListSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.ClaimList} claimList
	 * @return {Object[]}
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
