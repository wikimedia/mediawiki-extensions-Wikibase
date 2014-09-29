/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * Deserializer for SnakList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.SnakListDeserializer = util.inherit( 'WbSnakListDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @param {Object} serialization
	 * @param {string[]} [order]
	 * @return {wikibase.datamodel.SnakList}
	 */
	deserialize: function( serialization, order ) {
		var snakList = new wb.datamodel.SnakList();

		if( !order ) {
			// No order specified: Just loop through the json object:
			$.each( serialization, function( propertyId, snaksPerProperty ) {
				addSerializedSnaksToSnakList( snaksPerProperty, snakList );
			} );

		} else {
			// Check whether all property ids that are featured by snaks are specified in the order
			// list:
			$.each( serialization, function( propertyId ) {
				if( $.inArray( propertyId, order ) === -1 ) {
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

				addSerializedSnaksToSnakList( serialization[propertyId], snakList );
			}
		}

		return snakList;
	}
} );

/**
 * @param {Object[]} serializedSnaks
 * @param {wikibase.datamodel.SnakList} snakList
 * @return {wikibase.datamodel.SnakList}
 */
function addSerializedSnaksToSnakList( serializedSnaks, snakList ) {
	var snakDeserializer = new MODULE.SnakDeserializer();

	for( var i = 0; i < serializedSnaks.length; i++ ) {
		snakList.addItem( snakDeserializer.deserialize( serializedSnaks[i] ) );
	}

	return snakList;
}

}( wikibase, util, jQuery ) );
