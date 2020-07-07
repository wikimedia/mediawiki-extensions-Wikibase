( function( globeCoordinate ) {
	'use strict';

	/**
	 * Globe coordinate object.
	 * @class globeCoordinate.GlobeCoordinate
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} gcDef Needs the following attributes:
	 *        - {number} latitude
	 *        - {number} longitude
	 *        - {number|null} [precision]
	 *        - {string|null} [globe] Defaults to http://www.wikidata.org/entity/Q2.
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
		this._precision = gcDef.precision || null;

		// TODO: Implement globe specific restrictions. The restrictions below
		// allow coordinates for Mars and other globes.
		if( Math.abs( this._latitude ) > 360 ) {
			throw new Error( 'Latitude (' + this._latitude + ') is out of bounds' );
		}
		if( Math.abs( this._longitude ) > 360 ) {
			throw new Error( 'Longitude (' + this._longitude + ') is out of bounds' );
		}

		this._globe = gcDef.globe || 'http://www.wikidata.org/entity/Q2';
	};

	/**
	 * @class globeCoordinate.GlobeCoordinate
	 */
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
		 * @property {number|null}
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
		 * @return {number|null}
		 */
		getPrecision: function() { return this._precision; },

		/**
		 * Compares the object to another GlobeCoordinate object and returns whether both represent
		 * the same information.
		 *
		 * @param {globeCoordinate.GlobeCoordinate} otherGlobeCoordinate
		 * @return {boolean}
		 */
		equals: function( otherGlobeCoordinate ) {
			if ( !( otherGlobeCoordinate instanceof SELF )
				|| otherGlobeCoordinate._globe !== this._globe
			) {
				return false;
			}

			// 0.00000001° corresponds to approx. 1 mm on Earth and can always be considered equal.
			var oneMillimeter = 0.00000001,
				epsilon = Math.max(
					// A change worth 1/2 precision might already become a visible change
					Math.min( this._precision, otherGlobeCoordinate._precision ) / 2,
					oneMillimeter
				);

			return Math.abs( otherGlobeCoordinate._precision - this._precision ) < oneMillimeter
				&& Math.abs( otherGlobeCoordinate._latitude - this._latitude ) < epsilon
				&& Math.abs( otherGlobeCoordinate._longitude - this._longitude ) < epsilon;
		}
	};

}( globeCoordinate ) );
