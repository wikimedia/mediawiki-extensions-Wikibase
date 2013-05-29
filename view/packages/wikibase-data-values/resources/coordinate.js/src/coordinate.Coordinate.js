/**
 * Coordinate object
 *
 * @since 0.1
 * @file
 * @ingroup coordinate.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 * @author H. Snater < mediawiki@snater.com >
 *
 * @dependency coordinate
 * @dependency coordinate.parser
 */
coordinate.Coordinate = ( function( coordinate, coordinateParser ) {
	'use strict';

	/**
	 * Constructor for an object representing a coordinate with a certain precision.
	 *
	 * @param {string} rawInput
	 * @param {Object} options
	 *        {number} precision: Precision which will overrule the automatically detected
	 *        precision.
	 *
	 * @throws {Error} If input text could not be parsed.
	 *
	 * @constructor
	 */
	var Coordinate = function Coordinate( rawInput, options ) {
		var parsed;

		options = options || {};

		if( !rawInput ) {
			throw new Error( 'No input given' );
		}

		try {
			parsed = coordinateParser.parse( rawInput );
		} catch( e ) {
			throw new Error( 'Could not parse input: ' + e.toString() );
		}

		this._rawInput = rawInput;
		this._latitude = parsed[0];
		this._longitude = parsed[1];
		this._precision = ( options.precision !== undefined ) ? options.precision : parsed[2];
	};

	Coordinate.prototype = {
		/**
		 * Raw input
		 * @type {string}
		 */
		_rawInput: null,

		/**
		 * Latitude (decimal)
		 * @type {number}
		 */
		_latitude: null,

		/**
		 * Longitude (decimal)
		 * @type {number}
		 */
		_longitude: null,

		/**
		 * Precision
		 * @type {number}
		 */
		_precision: null,

		/**
		 * Returns whether the object is representing a valid coordinate.
		 *
		 * TODO: throw an error in constructor if invalid Coordinate, don't support invalid data
		 *  objects and deprecate this function then.
		 *
		 * @return {boolean}
		 */
		isValid: function() {
			return ( Math.abs( this._latitude ) <= 90 && Math.abs( this._longitude ) <= 180 );
		},

		/**
		 * Returns the original (raw) input.
		 *
		 * @return {string}
		 */
		getRawInput: function() { return this._rawInput; },

		/**
		 * Returns the decimal latitude.
		 *
		 * @return {number}
		 */
		getLatitude: function() { return this._latitude; },

		/**
		 * Returns the decimal longitude.
		 *
		 * @return {number}
		 */
		getLongitude: function() { return this._longitude; },

		/**
		 * Returns the precision.
		 *
		 * @return {number}
		 */
		getPrecision: function() { return this._precision; },

		/**
		 * Sets the precision.
		 *
		 * TODO: Make this an immutable object, deprecate this function.
		 *
		 * @param {number} precision
		 */
		setPrecision: function( precision ) { this._precision = precision; },

		/**
		 * Increases the precision by one step.
		 *
		 * TODO: Make this an immutable object, deprecate this function.
		 */
		increasePrecision: function() {
			this._precision = coordinate.increasePrecision( this._precision );
		},

		/**
		 * Decreases the precision by one step.
		 *
		 * TODO: Make this an immutable object, deprecate this function.
		 */
		decreasePrecision: function() {
			this._precision = coordinate.decreasePrecision( this._precision );
		},

		/**
		 * Returns the precision text.
		 *
		 * @return {string}
		 */
		getPrecisionText: function() { return coordinate.precisionText( this._precision ); },

		/**
		 * Returns the precision text in a common unit.
		 *
		 * @return {string}
		 */
		getPrecisionTextEarth: function() {
			return coordinate.precisionTextEarth( this._precision );
		},

		/**
		 * Returns the decimal latitude.
		 *
		 * @return {Object}
		 */
		latitudeDecimal: function() {
			return coordinate.toDecimal( this._latitude, this._precision );
		},

		/**
		 * Returns the decimal longitude.
		 *
		 * @return {Object}
		 */
		longitudeDecimal: function() {
			return coordinate.toDecimal( this._longitude, this._precision );
		},

		/**
		 * Returns the latitude in degree.
		 *
		 * @return {Object}
		 */
		latitudeDegree: function() {
			return coordinate.toDegree( this._latitude, this._precision );
		},

		/**
		 * Returns the longitude in degree.
		 *
		 * @return {Object}
		 */
		longitudeDegree: function() {
			return coordinate.toDegree( this._longitude, this._precision );
		},

		/**
		 * Returns the decimal coordinate as text.
		 *
		 * @return {string}
		 */
		decimalText: function() {
			return coordinate.decimalText( this._latitude, this._longitude, this._precision );
		},

		/**
		 * Returns the coordinate as text in degree.
		 *
		 * @return {string}
		 */
		degreeText: function() {
			return coordinate.degreeText( this._latitude, this._longitude, this._precision );
		},

		/**
		 * Returns the coordinate's ISO 6709 string representation.
		 *
		 * @return {string}
		 */
		iso6709: function() {
			var lat = this.latitudeDegree(),
				lon = this.longitudeDegree();

			/**
			 * Strips a number's sign and fills the number's integer part with zeroes according to a
			 * given string length.
			 *
			 * @param {number} number
			 * @param {number} length
			 */
			function pad( number, length ) {
				var absolute = Math.abs( number ),
					string = String( absolute ),
					exploded = string.split( '.' );

				if( exploded[0].length === length ) {
					return string;
				}

				return ''
					+ new Array( length - exploded[0].length + 1 ).join( '0' )
					+ exploded[0]
					+ ( ( exploded[1] ) ? '.' + exploded[1] : '' );
			}

			// There is no need to include minute in the result if minute and second have no value.
			// If second has no value, it can be dropped anyway.
			return ''
				+ ( ( ( lat.degree < 0 ) ? '-' : '+' ) + pad( lat.degree, 2 ) )
				+ ( ( lat.minute || lat.second ) ? pad( lat.minute, 2 ) : '' )
				+ ( ( lat.second ) ? pad( lat.second, 2 ) : '' )
				+ ( ( ( lon.degree < 0 ) ? '-' : '+' ) + pad( lon.degree, 3 ) )
				+ ( ( lon.minute || lon.second ) ? pad( lon.minute, 2 ) : '' )
				+ ( ( lon.second ) ? pad( lon.second, 2 ) : '' )
				+ '/';
		},

		/**
		 * Compares the object to another Coordinate object and returns whether both represent the
		 * same information.
		 *
		 * @param {coordinate.Coordinate} otherCoordinate
		 * @return {boolean}
		 */
		equals: function( otherCoordinate ) {
			if( !( otherCoordinate instanceof coordinate.Coordinate ) ) {
				return false;
			}

			return this.isValid() && otherCoordinate.isValid() // two invalid times are not equal
				&& this.getPrecision() === otherCoordinate.getPrecision()
				&& this.iso6709() === otherCoordinate.iso6709();
		}

	};

	return Coordinate;

}( coordinate, coordinate.parser ) );
