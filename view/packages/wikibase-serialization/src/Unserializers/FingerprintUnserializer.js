/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for Fingerprint objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.FingerprintUnserializer = util.inherit( 'WbFingerprintUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Fingerprint}
	 */
	unserialize: function( serialization ) {
		var termSetUnserializer = new MODULE.TermSetUnserializer(),
			multiTermSetUnserializer = new MODULE.MultiTermSetUnserializer();

		return new wb.datamodel.Fingerprint(
			termSetUnserializer.unserialize( serialization.labels ),
			termSetUnserializer.unserialize( serialization.descriptions ),
			multiTermSetUnserializer.unserialize( serialization.aliases )
		);
	}
} );

}( wikibase, util ) );
