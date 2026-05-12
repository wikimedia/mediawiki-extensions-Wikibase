( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		TermMapDeserializer = require( './TermMapDeserializer.js' ),
		MultiTermMapDeserializer = require( './MultiTermMapDeserializer.js' );

	/**
	 * @class FingerprintDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbFingerprintDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.Fingerprint}
		 */
		deserialize: function( serialization ) {
			var termMapDeserializer = new TermMapDeserializer(),
				multiTermMapDeserializer = new MultiTermMapDeserializer();

			return new datamodel.Fingerprint(
				termMapDeserializer.deserialize( serialization.labels ),
				termMapDeserializer.deserialize( serialization.descriptions ),
				multiTermMapDeserializer.deserialize( serialization.aliases )
			);
		}
	} );

}() );
