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
		var termListUnserializer = new MODULE.TermListUnserializer(),
			termGroupListUnserializer = new MODULE.TermGroupListUnserializer();

		return new wb.datamodel.Fingerprint(
			termListUnserializer.unserialize( serialization.labels ),
			termListUnserializer.unserialize( serialization.descriptions ),
			termGroupListUnserializer.unserialize( serialization.aliases )
		);
	}
} );

}( wikibase, util ) );
