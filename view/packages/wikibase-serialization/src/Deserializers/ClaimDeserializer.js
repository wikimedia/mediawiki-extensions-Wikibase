( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.ClaimDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @constructor
 */
MODULE.ClaimDeserializer = util.inherit( 'WbClaimDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.Claim}
	 */
	deserialize: function( serialization ) {
		var mainSnak = ( new MODULE.SnakDeserializer ).deserialize( serialization.mainsnak ),
			qualifiers = null,
			guid = serialization.id || null;

		if( serialization.qualifiers !== undefined ) {
			qualifiers = ( new MODULE.SnakListDeserializer() ).deserialize(
				serialization.qualifiers,
				serialization['qualifiers-order']
			);
		}

		return new wb.datamodel.Claim( mainSnak, qualifiers, guid );
	}
} );

}( wikibase, util ) );
