/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for Fingerprint objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.FingerprintDeserializer = util.inherit( 'WbFingerprintDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.Fingerprint}
	 */
	deserialize: function( serialization ) {
		var termSetDeserializer = new MODULE.TermSetDeserializer(),
			multiTermSetDeserializer = new MODULE.MultiTermSetDeserializer();

		return new wb.datamodel.Fingerprint(
			termSetDeserializer.deserialize( serialization.labels ),
			termSetDeserializer.deserialize( serialization.descriptions ),
			multiTermSetDeserializer.deserialize( serialization.aliases )
		);
	}
} );

}( wikibase, util ) );
