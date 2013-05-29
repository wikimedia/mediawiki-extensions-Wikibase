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
		 * @param {number} precision
		 */
		setPrecision: function( precision ) { this._precision = precision; },

		/**
		 * Increases the precision by one step.
		 */
		increasePrecision: function() {
			this._precision = coordinate.increasePrecision( this._precision );
		},

		/**
		 * Decreases the precision by one step.
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
		}

	};

	return Coordinate;

}( coordinate, coordinate.parser ) );
