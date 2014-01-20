/**
 * Globe coordinate detection global routines
 * Original source: http://simia.net/valueparser/coordinate.js
 *
 * VERSION: 0.1
 *
 * @since 0.1
 * @file
 * @ingroup globeCoordinate.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 * @author H. Snater < mediawiki@snater.com >
 */
this.globeCoordinate = ( function() {
	'use strict';

	return {
		/**
		 * Return a given decimal value applying a precision.
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
		 * Returns a given decimal value converted to degree taking a precision into account.
		 *
		 * @param {number} value
		 * @param {number} precision
		 * @return {Object} Returned object has the following structure:
		 *         {
		 *           degree: {number},
		 *           minute: {number|undefined},
		 *           second: {number|undefined}
		 *         }
		 *         "minute" and/or "second" are undefined if not covered by the precision.
		 */
		toDegree: function( value, precision ) {
			var result = {};

			value = Math.abs( value );

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

			// TODO: precision might be a floating point number and might cause minutes/seconds
			// to be "generated".
			if( precision > 1 ) {
				result.degree = Math.round( result.degree / precision ) * precision;

				// JavaScript may cause some disturbance regarding rounding and precision. The
				// result should not have a higher floating point number precision than the
				// applied precision.
				var degreeFloat = ( '' + result.degree ).split( '.' ),
					precisionFloat = ( '' + precision ).split( '.' );

				if(
					degreeFloat[1] && precisionFloat[1]
					&& degreeFloat[1].length > precisionFloat[1].length
				) {
					var trimmedPrecision = degreeFloat[1].substr( 0, precisionFloat[1].length );
					result.degree = parseFloat( degreeFloat[0] + '.' + trimmedPrecision );
				}
			}

			return result;
		},

		/**
		 * Returns a coordinate's ISO 6709 string representation.
		 *
		 * @param {Object} decimalCoordinateDefinition
		 *        Object with the following structure:
		 *        {
		 *          latitude: {number},
		 *          longitude: {number},
		 *          precision: {number}
		 *        }
		 * @return {string}
		 */
		iso6709: function( decimalCoordinateDefinition ) {
			var latitude = decimalCoordinateDefinition.latitude,
				longitude = decimalCoordinateDefinition.longitude,
				precision = decimalCoordinateDefinition.precision,
				lat = globeCoordinate.toDegree( latitude, precision ),
				lon = globeCoordinate.toDegree( longitude, precision ),
				latISO,
				lonISO;

			/**
			 * Strips a number's sign and fills the number's integer part with zeroes according to a
			 * given string length.
			 *
			 * @param {number} number
			 * @param {string} length
			 */
			function pad( number, length ) {
				var absolute = Math.abs( number || 0 ),
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

			latISO = ''
				+ ( ( ( latitude < 0 ) ? '-' : '+' ) + pad( lat.degree, 2 ) )
				+ ( ( precision < 1 ) ? pad( lat.minute, 2 ) : '' )
				+ ( ( precision < 1 / 60 ) ? pad( lat.second, 2 ) : '' );

			lonISO = ''
				+ ( ( ( longitude < 0 ) ? '-' : '+' ) + pad( lon.degree, 3 ) )
				+ ( ( precision < 1 ) ? pad( lon.minute, 2 ) : '' )
				+ ( ( precision < 1 / 60 ) ? pad( lon.second, 2 ) : '' );

			// Synchronize precision (longitude degree needs to be 1 digit longer):
			if( lonISO.indexOf( '.' ) !== -1 && latISO.indexOf( '.' ) === -1 ) {
				latISO += '.';
			}
			while( latISO.length < lonISO.length - 1 ) {
				latISO += '0';
			}
			if( latISO.indexOf( '.' ) !== -1 && lonISO.indexOf( '.' ) === -1 ) {
				lonISO += '.';
			}
			while( lonISO.length < latISO.length + 1 ) {
				lonISO += '0';
			}

			return latISO + lonISO + '/';
		}

	};

} )();