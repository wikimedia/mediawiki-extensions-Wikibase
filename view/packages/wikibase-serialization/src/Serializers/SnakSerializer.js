/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for Snak objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.SnakSerializer = util.inherit( 'WbSnakSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Snak} snak
	 * @return {Object}
	 */
	serialize: function( snak ) {
		if( !( snak instanceof wb.datamodel.Snak ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Snak' );
		}

		var serialization = {
			snaktype: snak.getType(),
			property: snak.getPropertyId()
		};

		if( snak instanceof wikibase.datamodel.PropertyValueSnak ) {
			var dataValue = snak.getValue();

			serialization.datavalue = {
				type: dataValue.getType(),
				value: dataValue.toJSON()
			};
		}

		return serialization;
	}
} );

}( wikibase, util ) );
