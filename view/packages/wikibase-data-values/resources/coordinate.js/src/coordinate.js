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
			precisionTexts: [
				{ precision: 1, text: 'to a degree' },
				{ precision: 1 / 60, text: 'to an arcminute' },
				{ precision: 1 / 3600, text: 'to an arcsecond' },
				{ precision: 1 / 36000, text: 'to a tenth of an arcsecond' },
				{ precision: 1 / 360000, text: 'to the hundredth of an arcsecond' },
				{ precision: 1 / 3600000, text: 'to the thousandth of an arcsecond' }
			]
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
		 * Returns the given precision increased by one step.
		 *
		 * @param {number} precision
		 * @return {number}
		 */
		increasePrecision: function( precision ) {
			var index = this.precisionLevels.indexOf( precision );

			if( index === this.precisionLevels.length - 1 || index === -1 ) {
				var newPrecision = precision / 10;
				return ( newPrecision < 1e-9 ) ? 1e-9 : newPrecision;
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
			if( precision < 1e-9) {
				return 1e-9;
			}

			var index = this.precisionLevels.indexOf( precision );

			if( index === 0 ) {
				return 180;
			} else if( index < 0 ) {
				return Math.min( precision * 10, 180 );
			}

			return this.precisionLevels[index-1];
		},

		/**
		 * Returns a given precision as string.
		 *
		 * @param {number} precision
		 * @return {string}
		 */
		precisionText: function( precision ) {
			var precisionText;

			// Figure out if the precision is very close to a precision that can be expressed with a
			// string:
			for( var i in this.settings.precisionTexts ) {
				if(
					this.settings.precisionTexts.hasOwnProperty( i )
					&& Math.abs( precision - this.settings.precisionTexts[i].precision ) < 0.0000001
				) {
					precisionText = this.settings.precisionTexts[i].text;
				}
			}

			if( !precisionText ) {
				if( precision < 9e-10 ) {
					precision = 1e-9;
				}
				// 0x000B1 is the plus/minus sign
				precisionText = String.fromCharCode( 0x00B1 ) + precision + this.settings.degree;
			}

			return precisionText;
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
		}

	};

} )();