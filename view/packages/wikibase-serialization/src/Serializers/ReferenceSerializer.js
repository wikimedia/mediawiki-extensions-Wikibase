( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.ReferenceSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ReferenceSerializer = util.inherit( 'WbReferenceSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {datamodel.Reference} reference
	 * @return {Object}
	 *
	 * @throws {Error} if reference is not a Reference instance.
	 */
	serialize: function( reference ) {
		if( !( reference instanceof datamodel.Reference ) ) {
			throw new Error( 'Not an instance of datamodel.Reference' );
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

module.exports = MODULE.ReferenceSerializer;
}( wikibase, util ) );
