/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ClaimGroupUnserializer = util.inherit( 'WbClaimGroupUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.ClaimGroup}
	 */
	unserialize: function( serialization ) {
		if( $.isEmptyObject( serialization ) ) {
			throw new Error( 'Cannot unserialize empty serialization' );
		}

		var claimListUnserializer = new MODULE.ClaimListUnserializer(),
			claimList = claimListUnserializer.unserialize( serialization );

		return new wb.datamodel.ClaimGroup( claimList.getPropertyIds()[0], claimList );
	}
} );

}( wikibase, util, jQuery ) );
