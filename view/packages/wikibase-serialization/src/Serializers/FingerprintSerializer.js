( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.FingerprintSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.FingerprintSerializer = util.inherit( 'WbFingerprintSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.Fingerprint} fingerprint
	 * @return {Object}
	 *
	 * @throws {Error} if fingerprint is not a Fingerprint instance.
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
