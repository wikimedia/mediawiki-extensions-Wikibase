( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		SnakDeserializer = require( './SnakDeserializer.js' ),
		SnakListDeserializer = require( './SnakListDeserializer.js' );

	/**
	 * @class ClaimDeserializer
	 * @extends Deserializer
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
			var mainSnak = ( new SnakDeserializer ).deserialize( serialization.mainsnak ),
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

}() );
