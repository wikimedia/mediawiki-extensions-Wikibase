( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		SnakListDeserializer = require( './SnakListDeserializer.js' );

	/**
	 * @class ReferenceDeserializer
	 * @extends Deserializer
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

}() );
