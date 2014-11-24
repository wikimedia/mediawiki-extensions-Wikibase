( function( dv, util, GlobeCoordinate ) {
	'use strict';

	var PARENT = dv.DataValue,
		constructor = function( value ) {
			if( !( value instanceof GlobeCoordinate ) ) {
				throw new Error( 'The given value has to be a globeCoordinate.GlobeCoordinate '
					+ 'object' );
			}

			this._value = value;
		};

	/**
	 * Constructor for creating a data value representing a globe coordinate.
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 * @extends dv.DataValue
	 * @since 0.1
	 *
	 * @param {globeCoordinate.GlobeCoordinate} value
	 */
	var SELF = dv.GlobeCoordinateValue = util.inherit( 'DvGlobeCoordinateValue', PARENT, constructor, {
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

			return {
				latitude: globeCoordinate.getLatitude(),
				longitude: globeCoordinate.getLongitude(),
				globe: globeCoordinate.getGlobe(),
				precision: globeCoordinate.getPrecision()
				// altitude: ...
			};
		}
	} );

	/**
	 * @see dv.DataValue.newFromJSON
	 */
	SELF.newFromJSON = function( json ) {
		var gc = new GlobeCoordinate( {
			latitude: json.latitude,
			longitude: json.longitude,
			globe: json.globe,
			precision: json.precision
			// altitude: json.altitude, // TODO: make globeCoordinate.js support altitude
		} );

		return new SELF( gc );
	};

	/**
	 * @see dv.DataValue.TYPE
	 */
	SELF.TYPE = 'globecoordinate';

	dv.registerDataValue( SELF );

}( dataValues, util, globeCoordinate.GlobeCoordinate ) );
