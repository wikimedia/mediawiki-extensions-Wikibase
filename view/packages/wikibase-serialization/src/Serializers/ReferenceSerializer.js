( function() {
	'use strict';

	var PARENT = require( './Serializer.js' ),
		SnakListSerializer = require( './SnakListSerializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class ReferenceSerializer
	 * @extends Serializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbReferenceSerializer', PARENT, {
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

			var snakListSerializer = new SnakListSerializer(),
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

}() );
