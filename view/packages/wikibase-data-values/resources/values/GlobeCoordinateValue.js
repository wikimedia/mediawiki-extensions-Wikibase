/**
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, $, GlobeCoordinate ) {
	'use strict';

	var PARENT = dv.DataValue,
		constructor = function( value ) {
			if( !( value instanceof GlobeCoordinate ) ) {
				throw new Error( 'The given value has to be a globeCoordinate.GlobeCoordinate '
					+ 'object' );
			}
			if( !value.isValid() ) {
				throw new Error( 'The given GlobeCoordinate object value has to represent a '
					+ 'valid globe coordinate' );
			}

			this._value = value;
		};

	/**
	 * Constructor for creating a data value representing a globe coordinate.
	 *
	 * @constructor
	 * @extends dv.DataValue
	 * @since 0.1
	 *
	 * @param {globeCoordinate.GlobeCoordinate} value
	 */
	var SELF = dv.GlobeCoordinateValue = dv.util.inherit( 'DvGlobeCoordinateValue', PARENT, constructor, {
		/**
		 * @see dv.DataValue.getSortKey
		 *
		 * @since 0.1
		 *
		 * @return {string}
		 */
		getSortKey: function() {
			return this.getValue().iso6709();
		},

		/**
		 * @see dv.DataValue.getValue
		 *
		 * @since 0.1
		 *
		 * @return {globeCoordinate.GlobeCoordinate}
		 */
		getValue: function() {
			return this._value;
		},

		/**
		 * @see dv.DataValue.equals
		 *
		 * @since 0.1
		 */
		equals: function( value ) {
			if ( !( value instanceof SELF ) ) {
				return false;
			}
			return this.getValue().equals( value.getValue() );
		},

		/**
		 * @see dv.DataValue.toJSON
		 *
		 * @since 0.1
		 */
		toJSON: function() {
			var globeCoordinate = this.getValue();

			// TODO: Backend should interact with a proper JSON structure and have precision implemented.
			return globeCoordinate.getLatitude() + '|' + globeCoordinate.getLongitude();
		}

	} );

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	SELF.newFromJSON = function( json ) {
		var data = json.split( '|' );

		var c = new GlobeCoordinate( {
			latitude: parseFloat( data[0] ),
			longitude: parseFloat( data[1] ),
			altitude: ( data[2] ) ? parseFloat( data[2] ) : null,
			globe: ( data[3] ) ? data[3] : null
		} );

		return new SELF( c );
	};

	/**
	 * @see dv.DataValue.TYPE
	 */
	SELF.TYPE = 'geocoordinate';

	dv.registerDataValue( SELF );

}( dataValues, jQuery, globeCoordinate.GlobeCoordinate ) );
