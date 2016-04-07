( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.SnakListSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.SnakListSerializer = util.inherit( 'WbSnakListSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.SnakList} snakList
	 * @return {Object}
	 *
	 * @throws {Error} if snakList is not a SnakList instance.
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
