/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
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
		var termMapDeserializer = new MODULE.TermMapDeserializer(),
			multiTermMapDeserializer = new MODULE.MultiTermMapDeserializer();

		return new wb.datamodel.Fingerprint(
			termMapDeserializer.deserialize( serialization.labels ),
			termMapDeserializer.deserialize( serialization.descriptions ),
			multiTermMapDeserializer.deserialize( serialization.aliases )
		);
	}
} );

}( wikibase, util ) );
