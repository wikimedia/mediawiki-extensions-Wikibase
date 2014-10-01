/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.FingerprintSerializer = util.inherit( 'WbFingerprintSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Fingerprint} fingerprint
	 * @return {Object}
	 */
	serialize: function( fingerprint ) {
		if( !( fingerprint instanceof wb.datamodel.Fingerprint ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Fingerprint' );
		}

		var termSetSerializer = new MODULE.TermSetSerializer(),
			multiTermSetSerializer = new MODULE.MultiTermSetSerializer();

		return {
			labels: termSetSerializer.serialize( fingerprint.getLabels() ),
			descriptions: termSetSerializer.serialize( fingerprint.getDescriptions() ),
			aliases: multiTermSetSerializer.serialize( fingerprint.getAliases() )
		};
	}
} );

}( wikibase, util ) );
