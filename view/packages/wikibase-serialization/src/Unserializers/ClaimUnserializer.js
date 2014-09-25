/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for single Claims.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ClaimUnserializer = util.inherit( 'WbClaimUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Claim}
	 */
	unserialize: function( serialization ) {
		var mainSnak = ( new MODULE.SnakUnserializer ).unserialize( serialization.mainsnak ),
			qualifiers = null,
			guid = serialization.id || null;

		if( serialization.qualifiers !== undefined ) {
			qualifiers = ( new MODULE.SnakListUnserializer() ).unserialize(
				serialization.qualifiers,
				serialization['qualifiers-order']
			);
		}

		return new wb.datamodel.Claim( mainSnak, qualifiers, guid );
	}
} );

}( wikibase, util ) );
