( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.SnakSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.SnakSerializer = util.inherit( 'WbSnakSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {datamodel.Snak} snak
	 * @return {Object}
	 *
	 * @throws {Error} if snak is not a Snak instance.
	 */
	serialize: function( snak ) {
		if( !( snak instanceof datamodel.Snak ) ) {
			throw new Error( 'Not an instance of datamodel.Snak' );
		}

		var serialization = {
			snaktype: snak.getType(),
			property: snak.getPropertyId()
		};

		if( snak.getHash() !== null ) {
			serialization.hash = snak.getHash();
		}

		if( snak instanceof datamodel.PropertyValueSnak ) {
			var dataValue = snak.getValue();

			serialization.datavalue = {
				type: dataValue.getType(),
				value: dataValue.toJSON()
			};
		}

		return serialization;
	}
} );

module.exports = MODULE.SnakSerializer;
}( wikibase, util ) );
