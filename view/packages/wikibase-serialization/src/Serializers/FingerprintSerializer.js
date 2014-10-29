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

		var termMapSerializer = new MODULE.TermMapSerializer(),
			multiTermMapSerializer = new MODULE.MultiTermMapSerializer();

		return {
			labels: termMapSerializer.serialize( fingerprint.getLabels() ),
			descriptions: termMapSerializer.serialize( fingerprint.getDescriptions() ),
			aliases: multiTermMapSerializer.serialize( fingerprint.getAliases() )
		};
	}
} );

}( wikibase, util ) );
