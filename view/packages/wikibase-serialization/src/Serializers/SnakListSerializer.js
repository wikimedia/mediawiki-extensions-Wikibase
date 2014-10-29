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
MODULE.SnakListSerializer = util.inherit( 'WbSnakListSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.SnakList} snakList
	 * @return {Object}
	 */
	serialize: function( snakList ) {
		if( !( snakList instanceof wb.datamodel.SnakList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.SnakList' );
		}

		var serialization = {},
			snakSerializer = new MODULE.SnakSerializer();

		snakList.each( function( i, snak ) {
			var propertyId = snak.getPropertyId();

			if( !serialization[propertyId] ) {
				serialization[propertyId] = [];
			}

			serialization[propertyId].push( snakSerializer.serialize( snak ) );
		} );

		return serialization;
	}
} );

}( wikibase, util ) );
