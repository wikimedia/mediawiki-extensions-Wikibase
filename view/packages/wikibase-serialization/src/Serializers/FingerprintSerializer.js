/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for Fingerprint objects.
 *
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

		var termListSerializer = new MODULE.TermListSerializer(),
			termGroupListSerializer = new MODULE.TermGroupListSerializer();

		return {
			labels: termListSerializer.serialize( fingerprint.getLabels() ),
			descriptions: termListSerializer.serialize( fingerprint.getDescriptions() ),
			aliases: termGroupListSerializer.serialize( fingerprint.getAliasGroups() )
		};
	}
} );

}( wikibase, util ) );
