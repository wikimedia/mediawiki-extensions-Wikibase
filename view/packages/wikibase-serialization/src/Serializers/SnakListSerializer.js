( function() {
	'use strict';

	var PARENT = require( './Serializer.js' ),
		SnakSerializer = require( './SnakSerializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class SnakListSerializer
	 * @extends Serializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbSnakListSerializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {datamodel.SnakList} snakList
		 * @return {Object}
		 *
		 * @throws {Error} if snakList is not a SnakList instance.
		 */
		serialize: function( snakList ) {
			if( !( snakList instanceof datamodel.SnakList ) ) {
				throw new Error( 'Not an instance of datamodel.SnakList' );
			}

			var serialization = {},
				snakSerializer = new SnakSerializer();

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

}() );
