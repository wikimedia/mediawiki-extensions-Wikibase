/**
 * Globe coordinate object
 *
 * @since 0.1
 * @file
 * @ingroup globeCoordinate.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 * @author H. Snater < mediawiki@snater.com >
 *
 * @dependency globeCoordinate
 * @dependency globeCoordinate.parser
 */
globeCoordinate.GlobeCoordinate = ( function( globeCoordinate, globeCoordinateParser ) {
	'use strict';

	/**
	 * Constructor for an object representing a globe coordinate with a certain precision.
	 *
	 * @param {string|Object} globeCoordinateDefinition
	 * @param {Object} [options]
	 *        {number} precision: Precision which will overrule the automatically detected
	 *        precision.
	 *
	 * @throws {Error} If input text could not be parsed.
	 *
	 * @constructor
	 */
	function GlobeCoordinate( globeCoordinateDefinition, options ) {
		var parsed;

		options = options || {};

		if( !globeCoordinateDefinition ) {
			throw new Error( 'No input given' );
		}

		if( typeof globeCoordinateDefinition === 'string' ) {
			try {
				parsed = globeCoordinateParser.parse( globeCoordinateDefinition );
			} catch( e ) {
				throw new Error( 'Could not parse input: ' + e.toString() );
			}

			this._rawInput = globeCoordinateDefinition;
			this._latitude = parsed[0];
			this._longitude = parsed[1];
			this._precision = ( options.precision !== undefined ) ? options.precision : parsed[2];
		} else {
			this._latitude = globeCoordinateDefinition.latitude;
			this._longitude = globeCoordinateDefinition.longitude;
			this._precision = globeCoordinateDefinition.precision;
		}
		// TODO: Capture altitude and globe

		// TODO: The following checks are earth specific. When implementing additional globes,
		// either loosen the restrictions or implement globe specific restrictions.
		if( Math.abs( this._latitude ) > 90 ) {
			throw new Error( 'Latitude (' + this._latitude + ') is out of bounds' );
		}
		if( Math.abs( this._longitude ) > 180 ) {
			throw new Error( 'Longitude (' + this._longitude + ') is out of bounds' );
		}

		// Keep precision boundaries:
		// TODO: get definition of precisions out of global settings and put them into
		//  a globeCoordinate.GlobeCoordinate.PRECISION constant.
		var precisions = globeCoordinate.settings.precisions,
			minPrecision = precisions[0].level,
			maxPrecision = precisions[precisions.length - 1].level;

		if( this._precision > minPrecision ) {
			this._precision = minPrecision;
		} else if( this._precision < maxPrecision ) {
			this._precision = maxPrecision;
		}

		this._globe = 'http://www.wikidata.org/entity/Q2'; // TODO: Support other globes
	}

	GlobeCoordinate.prototype = {
		// Don't forget about "constructor" since we are overwriting the whole prototype here:
		constructor: GlobeCoordinate,

		/**
		 * Globe URI
		 * @type {string}
		 */
		_globe: null,

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
		 * Returns the coordinate's globe URI.
		 *
		 * @return {string}
		 */
		getGlobe: function() {
			return this._globe;
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
		 * Returns the precision text.
		 *
		 * @return {string}
		 */
		getPrecisionText: function() { return globeCoordinate.precisionText( this._precision ); },

		/**
		 * Returns the precision text in a common unit.
		 *
		 * @return {string}
		 */
		getPrecisionTextEarth: function() {
			return globeCoordinate.precisionTextEarth( this._precision );
		},

		/**
		 * Returns the decimal latitude.
		 *
		 * @return {Object}
		 */
		latitudeDecimal: function() {
			return globeCoordinate.toDecimal( this._latitude, this._precision );
		},

		/**
		 * Returns the decimal longitude.
		 *
		 * @return {Object}
		 */
		longitudeDecimal: function() {
			return globeCoordinate.toDecimal( this._longitude, this._precision );
		},

		/**
		 * Returns the latitude in degree.
		 *
		 * @return {Object}
		 */
		latitudeDegree: function() {
			return globeCoordinate.toDegree( this._latitude, this._precision );
		},

		/**
		 * Returns the longitude in degree.
		 *
		 * @return {Object}
		 */
		longitudeDegree: function() {
			return globeCoordinate.toDegree( this._longitude, this._precision );
		},

		/**
		 * Returns the decimal coordinate as text.
		 *
		 * @return {string}
		 */
		decimalText: function() {
			return globeCoordinate.decimalText( this._latitude, this._longitude, this._precision );
		},

		/**
		 * Returns the coordinate as text in degree.
		 *
		 * @return {string}
		 */
		degreeText: function() {
			return globeCoordinate.degreeText( this._latitude, this._longitude, this._precision );
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
		 * Compares the object to another GlobeCoordinate object and returns whether both represent
		 * the same information.
		 *
		 * @param {globeCoordinate.GlobeCoordinate} otherGlobeCoordinate
		 * @return {boolean}
		 */
		equals: function( otherGlobeCoordinate ) {
			if( !( otherGlobeCoordinate instanceof globeCoordinate.GlobeCoordinate ) ) {
				return false;
			}

			return this.getPrecision() === otherGlobeCoordinate.getPrecision()
				&& this.iso6709() === otherGlobeCoordinate.iso6709();
		}

	};

	return GlobeCoordinate;

}( globeCoordinate, globeCoordinate.parser ) );
