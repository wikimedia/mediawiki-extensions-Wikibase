( function() {
	'use strict';

	var PARENT = require( './Serializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class SnakSerializer
	 * @extends Serializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbSnakSerializer', PARENT, {
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

}() );
