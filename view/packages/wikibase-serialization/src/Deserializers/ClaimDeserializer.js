( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	datamodel = require( 'wikibase.datamodel' ),
	SnakListDeserializer = require( './SnakListDeserializer.js' );

/**
 * @class ClaimDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbClaimDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {datamodel.Claim}
	 */
	deserialize: function( serialization ) {
		var mainSnak = ( new MODULE.SnakDeserializer ).deserialize( serialization.mainsnak ),
			qualifiers = null,
			guid = serialization.id || null;

		if( serialization.qualifiers !== undefined ) {
			qualifiers = ( new SnakListDeserializer() ).deserialize(
				serialization.qualifiers,
				serialization['qualifiers-order']
			);
		}

		return new datamodel.Claim( mainSnak, qualifiers, guid );
	}
} );

}( wikibase, util ) );
