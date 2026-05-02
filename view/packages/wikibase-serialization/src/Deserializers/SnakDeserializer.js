( function( dv ) {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @class SnakDeserializer
	 * @extends Deserializer
	 * @since 2.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbSnakDeserializer', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.Snak}
		 *
		 * @throws {Error} if no constructor for the snak type detected exists.
		 */
		deserialize: function( serialization ) {
			if( serialization.snaktype === 'novalue' ) {
				return new datamodel.PropertyNoValueSnak( serialization.property, serialization.hash );
			} else if( serialization.snaktype === 'somevalue' ) {
				return new datamodel.PropertySomeValueSnak( serialization.property, serialization.hash );
			} else if( serialization.snaktype === 'value' ) {
				var dataValue = null,
					type = serialization.datavalue.type,
					value = serialization.datavalue.value;

				try {
					dataValue = dv.newDataValue( type, value );
				} catch( error ) {
					dataValue = new dv.UnDeserializableValue( value, type, error.message );
				}

				return new datamodel.PropertyValueSnak( serialization.property, dataValue, serialization.hash );
			}

			throw new Error( 'Incompatible snak type' );
		}
	} );

}( dataValues ) );
