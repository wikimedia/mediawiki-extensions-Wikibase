( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		SnakDeserializer = require( './SnakDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object[]} serializedSnaks
	 * @param {datamodel.Snak[]} snaks
	 * @return {datamodel.SnakList}
	 */
	function addSerializedSnaksToSnakList( serializedSnaks, snaks ) {
		var snakDeserializer = new SnakDeserializer();

		for( var i = 0; i < serializedSnaks.length; i++ ) {
			snaks.push( snakDeserializer.deserialize( serializedSnaks[i] ) );
		}

		return snaks;
	}

	/**
	 * @class SnakListDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbSnakListDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @param {Object} serialization
		 * @param {string[]} [order]
		 * @return {datamodel.SnakList}
		 *
		 * @throws {Error} if the order parameter is provided but the property id of a snak
		 *         serialization is no represented in the order.
		 * @throws {Error} if the order parameter is provided but no snak exists for a property
		 *         represented in the order.
		 */
		deserialize: function( serialization, order ) {
			var snaks = [];

			if( !order ) {
			// No order specified: Just loop through the json object:
			// eslint-disable-next-line no-jquery/no-each-util
				$.each( serialization, function( propertyId, snaksPerProperty ) {
					addSerializedSnaksToSnakList( snaksPerProperty, snaks );
				} );

			} else {
			// Check whether all property ids that are featured by snaks are specified in the order
			// list:
			// eslint-disable-next-line no-jquery/no-each-util
				$.each( serialization, function( propertyId ) {
					if( order.indexOf( propertyId ) === -1 ) {
						throw new Error( 'Snak featuring the property id ' + propertyId + ' is not '
							+ 'present within list of property ids defined for ordering' );
					}
				} );

				// Add all snaks grouped by property according to the order specified via the "order"
				// parameter:
				for( var i = 0; i < order.length; i++ ) {
					var propertyId = order[i];

					if( !serialization[propertyId] ) {
						throw new Error( 'Trying to oder by property ' + propertyId + ' without any '
							+ 'snak featuring this property being present' );
					}

					addSerializedSnaksToSnakList( serialization[propertyId], snaks );
				}
			}

			return new datamodel.SnakList( snaks );
		}
	} );

}() );
