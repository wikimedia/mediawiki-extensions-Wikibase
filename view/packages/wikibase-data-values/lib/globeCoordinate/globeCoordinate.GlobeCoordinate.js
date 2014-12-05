( function( globeCoordinate ) {
	'use strict';

	/**
	 * Globe coordinate object.
	 * @class globeCoordinate.GlobeCoordinate
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} gcDef Needs the following attributes:
	 *        - {number} latitude
	 *        - {number} longitude
	 *        - {number} precision
	 *
	 * @throws {Error} when latitude is greater than 360.
	 * @throws {Error} when longitude is greater than 360.
	 */
	var SELF = globeCoordinate.GlobeCoordinate = function GlobeCoordinate( gcDef ) {
		if( !gcDef || typeof gcDef !== 'object'
			|| gcDef.latitude === undefined
			|| gcDef.longitude === undefined
		) {
			throw new Error( 'No proper globe coordinate definition given' );
		}

		this._latitude = gcDef.latitude;
		this._longitude = gcDef.longitude;
		this._precision = gcDef.precision;

		// TODO: Capture altitude and globe

		// TODO: Implement globe specific restrictions. The restrictions below
		// allow coordinates for Mars and other globes.
		if( Math.abs( this._latitude ) > 360 ) {
			throw new Error( 'Latitude (' + this._latitude + ') is out of bounds' );
		}
		if( Math.abs( this._longitude ) > 360 ) {
			throw new Error( 'Longitude (' + this._longitude + ') is out of bounds' );
		}

		this._globe = 'http://www.wikidata.org/entity/Q2'; // TODO: Support other globes
	};

	SELF.prototype = {
		// Don't forget about "constructor" since we are overwriting the whole prototype here:
		constructor: SELF,

		/**
		 * Globe URI
		 * @property {string}
		 * @private
		 */
		_globe: null,

		/**
		 * Latitude (decimal)
		 * @property {number}
		 * @private
		 */
		_latitude: null,

		/**
		 * Longitude (decimal)
		 * @property {number}
		 * @private
		 */
		_longitude: null,

		/**
		 * Precision
		 * @property {number}
		 * @private
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
		 * Returns an object with decimal latitude, longitude and precision.
		 *
		 * @return {Object}
		 */
		getDecimal: function() {
			return {
				latitude: this._latitude,
				longitude: this._longitude,
				precision: this._precision
			};
		},

		/**
		 * Returns a coordinate's ISO 6709 string representation.
		 * (see globeCoordinate.iso6709)
		 *
		 * @return {string}
		 */
		iso6709: function() {
			return globeCoordinate.iso6709( this.getDecimal() );
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

			var gc1Iso6709 = globeCoordinate.iso6709( this.getDecimal() ),
				gc2Iso6709 = globeCoordinate.iso6709( otherGlobeCoordinate.getDecimal() );

			return Math.abs( this.getPrecision() - otherGlobeCoordinate.getPrecision() ) < 0.00000001
				&& gc1Iso6709 === gc2Iso6709;
		}
	};

}( globeCoordinate ) );
