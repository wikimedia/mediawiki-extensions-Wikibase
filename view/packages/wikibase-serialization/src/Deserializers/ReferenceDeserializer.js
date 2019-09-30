( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	datamodel = require( 'wikibase.datamodel' ),
	SnakListDeserializer = require( './SnakListDeserializer.js' );

/**
 * @class wikibase.serialization.ReferenceDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbReferenceDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {datamodel.Reference}
	 */
	deserialize: function( serialization ) {
		return new datamodel.Reference(
			( new SnakListDeserializer() ).deserialize(
				serialization.snaks,
				serialization['snaks-order']
			),
			serialization.hash
		);
	}
} );

}( wikibase, util ) );
