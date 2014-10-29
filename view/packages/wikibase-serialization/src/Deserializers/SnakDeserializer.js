/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, dv, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 */
MODULE.SnakDeserializer = util.inherit( 'WbSnakDeserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.Snak}
	 */
	deserialize: function( serialization ) {
		if( serialization.snaktype === 'novalue' ) {
			return new wb.datamodel.PropertyNoValueSnak( serialization.property );
		} else if( serialization.snaktype === 'somevalue' ) {
			return new wb.datamodel.PropertySomeValueSnak( serialization.property );
		} else if( serialization.snaktype === 'value' ) {
			var dataValue = null,
				type = serialization.datavalue.type,
				value = serialization.datavalue.value;

			try {
				dataValue = dv.newDataValue( type, value );
			} catch( error ) {
				dataValue = new dv.UnUnserializableValue( type, value, error );
			}

			return new wb.datamodel.PropertyValueSnak( serialization.property, dataValue );
		}

		throw new Error( 'Incompatible snak type' );
	}
} );

}( wikibase, util, dataValues, jQuery ) );
