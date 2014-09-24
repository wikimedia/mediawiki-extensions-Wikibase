/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for Reference objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.ReferenceSerializer = util.inherit( 'WbReferenceSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Reference} reference
	 * @return {Object}
	 */
	serialize: function( reference ) {
		if( !( reference instanceof wb.datamodel.Reference ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Reference' );
		}

		var snakListSerializer = new MODULE.SnakListSerializer(),
			snakList = reference.getSnaks(),
			hash = reference.getHash();

		var serialization = {
			snaks: snakListSerializer.serialize( snakList ),
			'snaks-order': snakList.getPropertyOrder()
		};

		if( hash ) {
			serialization.hash = hash;
		}

		return serialization;
	}
} );

}( wikibase, util ) );
