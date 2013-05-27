/**
 * Coordinate detection global routines
 * Original source: http://simia.net/valueparser/coordinate.js
 *
 * VERSION: 0.1
 *
 * @since 0.1
 * @file
 * @ingroup coordinate.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 * @author H. Snater < mediawiki@snater.com >
 */
var coordinate = ( function() {
	'use strict';

	return {
		/**
		 * Default settings/texts.
		 * @type {Object}
		 */
		settings: {
			north: 'N',
			east: 'E',
			south: 'S',
			west: 'W',
			dot: '.',
			latLongCombinator: ', ',
			degree: '°',
			minute: '\'',
			second: '"',
			precisionTextDegree: 'to a degree',
			precisionTextMinute: 'to an arcminute',
			precisionTextSecond: 'to an arcsecond',
			precisionTextDecisecond: 'to a tenth of an arcsecond',
			precisionTextCentisecond: 'to the hundredth of an arcsecond',
			precisionTextMillisecond: 'to the thousandth of an arcsecond',
			precisionTextMaximal: 'maximal'
		},

		/**
		 * Default precision levels.
		 * @type {number[]}
		 */
		precisionLevels: [
			10,
			1,
			0.1, 1/60,
			0.01, 1/3600,
			0.001, 1/36000,
			0.0001, 1/360000,
			0.00001, 1/3600000,
			0.000001
		],

		/**
		 * Returns a given precision as string.
		 *
		 * @param {number} precision
		 * @return {string}
		 */
		precisionText: function( precision ) {
			var text;

			if( Math.abs( precision - 1 ) < 0.0000001 ) {
				text = this.settings.precisionTextDegree;
			} else if( Math.abs(precision - 1 / 60 ) < 0.0000001 ) {
				text = this.settings.precisionTextMinute;
			} else if( Math.abs( precision - 1 / 3600 ) < 0.0000001 ) {
				text = this.settings.precisionTextSecond;
			} else if( Math.abs( precision - 1 / 36000 ) < 0.0000001 ) {
				text = this.settings.precisionTextDecisecond;
			} else if( Math.abs( precision - 1 / 360000 ) < 0.0000001 ) {
				text = this.settings.precisionTextCentisecond;
			} else if( Math.abs( precision - 1 / 3600000 ) < 0.0000001 ) {
				text = this.settings.precisionTextMillisecond;
			} else if( precision === 0 ) {
				text = this.settings.precisionTextMaximal;
			} else {
				if( precision < 9e-10 ) {
					precision = 1e-9;
				}
				text = '&plusmn;' + precision + this.settings.degree;
			}

			return text;
		},

		/**
		 * Returns a given precision as string with units commonly used on earth.
		 *
		 * @param {number} precision
		 * @return {string}
		 */
		precisionTextEarth: function( precision ) {
			var km = 40000 / 360 * precision;

			if( km > 100 ) {
				return Math.round( km / 100 ) * 100 + ' km';
			} else if( km > 10 ) {
				return Math.round( km / 10 ) * 10 + ' km';
			} else if( km > 1 ) {
				return Math.round( km ) + ' km';
			}

			var m = km * 1000;

			if( m > 100 ) {
				return Math.round( m / 100 ) * 100 + ' m';
			} else if( m > 10 ) {
				return Math.round( m / 10 ) * 10 + ' m';
			} else if( m > 1 ) {
				return Math.round( m ) + ' m';
			}

			var cm = m * 100;

			if( cm > 10 ) {
				return Math.round( cm / 10 ) * 10 + ' cm';
			} else if( cm > 1 ) {
				return Math.round( cm ) + ' cm';
			}

			var mm = cm * 10;

			if( mm > 1 ) {
				return Math.round( mm ) + ' mm';
			}

			return '1 mm';
		},

		/**
		 * Applies a precision to a decimal value.
		 *
		 * @param {number} value
		 * @param {number} precision
		 * @return {number}
		 */
		toDecimal: function( value, precision ) {
			var logPrecision = Math.max( -9, Math.floor( Math.log( precision ) / Math.LN10 ) ),
				factor = Math.pow( 10, -1 * logPrecision );

			return Math.round( value * factor ) / factor;
		},

		/**
		 * Returns a given coordinate as a string according to the decimal system.
		 *
		 * @param {number} latitude
		 * @param {number} longitude
		 * @param {number} precision
		 * @return {string}
		 */
		decimalText: function( latitude, longitude, precision ) {
			return ''
				+ Math.abs( this.toDecimal( latitude, precision ) )
				+ this.settings.degree
				+ ' '
				+ ( ( latitude < 0 ) ? this.settings.south : this.settings.north )
				+ this.settings.latLongCombinator
				+ Math.abs( this.toDecimal( longitude, precision ) )
				+ this.settings.degree
				+ ' '
				+ ( ( longitude < 0 ) ? this.settings.west : this.settings.east );
		},

		/**
		 * Returns a given value converted to degree.
		 *
		 * @param {number} value
		 * @param {number} precision
		 * @return {Object}
		 */
		toDegree: function( value, precision ) {
			var result = {};

			result.degree = Math.floor( value + 0.00000001 );

			if( precision > 0.9999999999 ) {
				result.minute = undefined;
			} else {
				result.minute = Math.abs( Math.floor( ( value - result.degree + 0.000001 ) * 60 ) );
			}

			if( precision > ( 0.9999999999 / 60 ) ) {
				result.second = undefined;
			} else {
				result.second = ( value - result.degree - result.minute / 60 ) * 3600;

				if( precision > ( 0.9999999999 / 3600 ) ) {
					result.second = Math.abs( Math.round( result.second ) );
				} else if( precision > ( 0.9999999999 / 36000 ) ) {
					result.second = Math.abs( Math.round( result.second * 10 ) / 10 );
				} else if( precision > ( 0.9999999999 / 360000 ) ) {
					result.second = Math.abs( Math.round( result.second * 100 ) / 100 );
				} else {
					result.second = Math.abs( Math.round( result.second * 1000 ) / 1000 );
				}

			}

			return result;
		},

		/**
		 * Returns a given coordinate as a string using degree.
		 *
		 * @param {number} latitude
		 * @param {number} longitude
		 * @param {number} precision
		 * @return {string}
		 */
		degreeText: function( latitude, longitude, precision ) {
			var text = function( number, sign ) {
				if( number === undefined ) {
					return '';
				}
				return number + sign;
			};

			var latDeg = this.toDegree( latitude, precision ),
				longDeg = this.toDegree( longitude, precision );

			return ''
				+ text( Math.abs( latDeg.degree ), this.settings.degree )
				+ text( latDeg.minute, this.settings.minute )
				+ text( latDeg.second, this.settings.second )
				+ ( ( latitude < 0 ) ? this.settings.south : this.settings.north )
				+ this.settings.latLongCombinator
				+ text( Math.abs( longDeg.degree ), this.settings.degree )
				+ text( longDeg.minute, this.settings.minute )
				+ text( longDeg.second, this.settings.second )
				+ ( ( longitude < 0 ) ? this.settings.west : this.settings.east );
		},

		/**
		 * Returns the given precision increased by one step.
		 *
		 * @param {number} precision
		 * @return {number}
		 */
		increasePrecision: function( precision ) {
			var index = this.precisionLevels.indexOf( precision );

			if( ( index === this.precisionLevels.length - 1 ) || ( index < 0 ) ) {
				var newPrecision = precision / 10;
				return ( newPrecision < 1e-9 ) ? 0 : newPrecision;
			}
			return this.precisionLevels[index + 1];
		},

		/**
		 * Returns the given precision decreased by one step.
		 *
		 * @param {number} precision
		 * @return {number}
		 */
		decreasePrecision: function( precision ) {
			if( precision === 0) {
				return 1e-9;
			}

			var index = this.precisionLevels.indexOf( precision );

			if( index === 0 ) {
				return 180;
			} else if( index < 0 ) {
				return Math.min( precision * 10, 180 );
			}

			return this.precisionLevels[index-1];
		}

	};

} )();