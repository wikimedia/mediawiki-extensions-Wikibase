( function( globeCoordinate ) {
	'use strict';

	var defaultOptions = {
		north: 'N',
		east: 'E',
		south: 'S',
		west: 'W',
		dot: '.',
		latLongCombinator: ', ',
		degree: '°',
		minute: '\'',
		second: '"',
		precisionTexts: [
			{ precision: 1 / 60, text: 'to an arcminute' },
			{ precision: 1 / 3600, text: 'to an arcsecond' },
			{ precision: 1 / 36000, text: 'to 1/10 of an arcsecond' },
			{ precision: 1 / 360000, text: 'to 1/100 of an arcsecond' },
			{ precision: 1 / 3600000, text: 'to 1/1000 of an arcsecond' }
		],
		format: 'decimal'
	};

	/**
	 * Globe coordinate formatter.
	 * @class globeCoordinate.Formatter
	 * @licence GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} [options]
	 */
	var SELF = globeCoordinate.Formatter = function Formatter( options ) {
		options = options || {};

		this._options = defaultOptions;

		for( var key in options ) {
			if( options.hasOwnProperty( key ) && this._options[key] ) {
				this._options[key] = options[key];
			}
		}
	};

	SELF.prototype = {
		// Don't forget about "constructor" since we are overwriting the whole prototype here:
		constructor: SELF,

		/**
		 * Options
		 * @property {Object}
		 * @private
		 */
		_options: null,

		/**
		 * Returns a text representation of a GlobeCoordinate object formatted according to the
		 * "format" option.
		 *
		 * @param {globeCoordinate.GlobeCoordinate} gc
		 * @return {string}
		 */
		format: function( gc ) {
			return this[this._options.format]( gc );
		},

		/**
		 * Returns the precision's string representation.
		 *
		 * @return {string}
		 */
		precisionText: function( precision ) {
			return SELF.PRECISIONTEXT( precision, this._options );
		},

		/**
		 * Returns the decimal coordinate as text.
		 *
		 * @param {globeCoordinate.GlobeCoordinate} gc
		 * @return {string}
		 */
		decimal: function( gc ) {
			var latitude = gc.getLatitude();
			var longitude = gc.getLongitude();
			var precision = gc.getPrecision();

			if( gc.getPrecision() ) {
				latitude = globeCoordinate.toDecimal( latitude, precision );
				longitude = globeCoordinate.toDecimal( longitude, precision );
			}

			return ''
				+ latitude
				+ this._options.latLongCombinator
				+ longitude;
		},

		/**
		 * Returns the coordinate as text in degree.
		 *
		 * @param {globeCoordinate.GlobeCoordinate} gc
		 * @return {string}
		 */
		degree: function( gc ) {
			var lat = gc.getLatitude(),
				lon = gc.getLongitude(),
				precision = gc.getPrecision();

			var text = function( number, sign ) {
				if( number === undefined ) {
					return '';
				}
				return number + sign;
			};

			var latDeg = globeCoordinate.toDegree( lat, precision ),
				longDeg = globeCoordinate.toDegree( lon, precision );

			return ''
				+ text( Math.abs( latDeg.degree ), this._options.degree )
				+ text( latDeg.minute, this._options.minute )
				+ text( latDeg.second, this._options.second )
				+ ( ( lat < 0 ) ? this._options.south : this._options.north )
				+ this._options.latLongCombinator
				+ text( Math.abs( longDeg.degree ), this._options.degree )
				+ text( longDeg.minute, this._options.minute )
				+ text( longDeg.second, this._options.second )
				+ ( ( lon < 0 ) ? this._options.west : this._options.east );
		}
	};

	/**
	 * Returns a precision's string representation.
	 * @property {Function}
	 * @static
	 *
	 * @param {number} precision
	 * @param {Object} [options]
	 * @return {string}
	 */
	SELF.PRECISIONTEXT = function( precision, options ) {
		var precisionText,
			combinedOptions = {};

		options = options || {};

		for( var key in defaultOptions ) {
			combinedOptions[key] = ( options[key] ) ? options[key] : defaultOptions[key];
		}

		// Figure out if the precision is very close to a precision that can be expressed with a
		// string:
		for( var i in combinedOptions.precisionTexts ) {
			if( Math.abs( precision - combinedOptions.precisionTexts[i].precision ) < 0.0000001 ) {
				precisionText = combinedOptions.precisionTexts[i].text;
			}
		}

		if( !precisionText ) {
			precisionText = '±' + precision + combinedOptions.degree;
		}

		return precisionText;
	};

}( globeCoordinate ) );
