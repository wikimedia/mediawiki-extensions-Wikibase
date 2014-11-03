/**
 * time.js's Time constructor for parsing and representing a time.
 *
 * @dependency jQuery
 * TODO: get rid of heavy jQuery dependency
 *
 * @since 0.1
 * @file
 * @ingroup Time.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
time.Time = ( function( time, $ ) {
	'use strict';

	/**
	 * Constructor for object representing a point in time with a certain precision.
	 *
	 * @param {string|Object} timeDefinition Text to be interpreted as time. Or a plain object
	 *        describing the time in the form of a time parser output.
	 * @param {Object} options
	 *        {number} precision: Precision which will overrule the automatically detected
	 *        precision.
	 *        {string} calendarname: Default calendar name overruling the automatically detected
	 *        calendar.
	 *
	 * @throws {Error} If given time Definition is an Object not representing a valid time.
	 */
	var Time = function Time( timeDefinition, options ) {
		var result;

		options = options || {};

		if( typeof timeDefinition === 'string' ) {
			// TODO: this should also throw errors or we should just take it out.
			// TODO: if this stays, the options should be merged with the parser result and the
			//  resulting object should be validated.
			var parser = new time.Parser();
			result = parser.parse( timeDefinition );
		} else {
			result = $.extend( {}, timeDefinition, options ); // copy object
			Time.validate( result );
		}

		var year = result.year || 0,
			month = result.month || 0,
			day = result.day || 0,
			hour = result.hour || 0,
			minute = result.minute || 0,
			second = result.second || 0,
			utcoffset = '+00:00',
			calendarname = Time.CALENDAR.GREGORIAN;

		if( options.calendarname ) {
			calendarname = options.calendarname;
		} else if ( result.calendarname !== undefined ) {
			calendarname = result.calendarname;
		}

		this.year = function() {
			return year;
		};

		this.month = function() {
			return month;
		};

		this.day = function() {
			return day;
		};

		this.utcoffset = function() {
			return utcoffset;
		};

		var precision = ( options.precision !== undefined ) ? options.precision : result.precision;
		this.precision = function() {
			return precision;
		};

		this.precisionText = function() {
			return time.precisionText( precision );
		};

		var before = 0,
			after = 0;

		this.before = function() {
			return before;
		};

		this.after = function() {
			return after;
		};

		this.gregorian = function() {
			if( calendarname === Time.CALENDAR.GREGORIAN ) {
				return {
					'year': year,
					'month': month,
					'day': day
				};
			} else if( calendarname === Time.CALENDAR.JULIAN ) {
				return time.julianToGregorian( year, month, day );
			}
		};

		this.julian = function() {
			if( calendarname === Time.CALENDAR.JULIAN ) {
				return {
					'year': year,
					'month': month,
					'day': day
				};
			} else if( calendarname === Time.CALENDAR.GREGORIAN ) {
				if( year !== null ) {
					return time.gregorianToJulian( year, month, day );
				}
			}
			return null;
		};

		this.jdn = function() {
			if( year === null ) {
				return null;
			}
			if( calendarname === Time.CALENDAR.GREGORIAN ) {
				return time.gregorianToJulianDay( year, month, day );
			} else {
				return time.julianToJulianDay( year, month, day );
			}
		};

		this.calendar = function() {
			return calendarname;
		};

		this.calendarURI = function() {
			if( calendarname === Time.CALENDAR.GREGORIAN ) {
				return 'http://www.wikidata.org/entity/Q1985727';
			} else if( calendarname === Time.CALENDAR.JULIAN ) {
				return 'http://www.wikidata.org/entity/Q1985786';
			}
		};

		this.iso8601 = function() {
			var g = this.gregorian();
			return ( ( g.year < 0 ) ? '-' : '+' ) + pad( g.year, 11 ) + '-' + pad( g.month, 2 )
				+ '-' + pad( g.day, 2 ) + 'T' + pad( hour, 2 ) + ':' + pad( minute, 2 )
				+ ':' + pad( second, 2 ) + 'Z';
		};

		this.text = function( options ) {
			options = options || {};

			if( year === null ) {
				return '';
			}

			if( options.format && options.format === 'ISO 8601' ) {
				return this.iso8601();
			}

			var defaultFormat = 'mdy';

			if( !options.format || options.format === 'default' ) {
				options.format = defaultFormat;
			}

			options.format = options.format.replace( /[^ymd]/g, '' );

			if( !/^[ymd]{3}$/.test( options.format ) ) {
				options.format = defaultFormat;
			}

			if( precision < 9 ) {
				return time.writeApproximateYear( year, precision );
			}

			if( precision === 9 ) {
				return time.writeYear( year );
			}

			var result = '';

			if( precision >= 10 ) {
				var template = options.format;

				if( precision < 11 ) {
					template = template.replace( /d/, '' );
				}

				template = template.split( '' ).join( ' ' );

				for( var i = 0; i < template.length; i++ ) {
					switch( template[i] ) {
						case 'y': result += time.writeYear( year ); break;
						case 'm': result += time.writeMonth( month ); break;
						case 'd': result += time.writeDay( day ); break;
						default: result += template[i];
					}
				}
			}

			if( precision > 11 ) {
				result += ' (time not implemented yet)';
			}

			return result;
		};

	};

	/**
	 * Returns whether some given time is equal to this one.
	 *
	 * @param {*|time.Time} otherTime
	 * @return boolean
	 */
	Time.prototype.equals = function( otherTime ) {
		if( !( otherTime instanceof Time ) ) {
			return false;
		}

		return this.precision() === otherTime.precision()
			&& this.calendar() === otherTime.calendar()
			&& this.after() === otherTime.after()
			&& this.before() === otherTime.before()
			&& this.utcoffset() === otherTime.utcoffset()
			&& this.iso8601() === otherTime.iso8601();
	};

	function pad( number, digits ) {
		return ( 1e12 + Math.abs( number ) + '' ).slice( -digits );
	}

	/**
	 * Creates a new Time object by a given iso8601 string like "+00000002000-12-31T23:59:59Z".
	 *
	 * TODO: this function shouldn't really be required since the parser should simply be able to
	 *       take such a string and create a new Time object from it. It could be kept for
	 *       performance reasons though.
	 *
	 * @param {string} iso8601String
	 * @param {number} [precision] If not given, precision will be as high as possible.
	 * @return time.Time
	 * @throws {Error} If the input string is invalid.
	 */
	Time.newFromIso8601 = function( iso8601String, precision ) {
		var year, month, day, timeObj;

		try {
			var matches = /^([+-]?\d+)-(\d+)-(\d+)(?=T)/.exec( iso8601String );
			year = parseInt( matches[1] );
			month = parseInt( matches[2] );
			day = parseInt( matches[3] );
		} catch( e ) {
			throw new Error( 'Unprocessable iso8601 string given' );
		}

		timeObj = {
			year: year,
			precision: precision !== undefined ? precision : Time.PRECISION.DAY,
			calendarname: Time.CALENDAR.GREGORIAN
		};

		if( month !== 0 ) {
			timeObj.month = month;
		}

		if( day !== 0 ) {
			timeObj.day = day;
		}

		return new Time( timeObj );
	};

	/**
	 * All possible precisions of Time.
	 * @type {Object} holding fields of type number
	 */
	Time.PRECISION = {
		GY: 0, // Gigayear
		MY100: 1, // 100 Megayears
		MY10: 2, // 10 Megayears
		MY: 3, // Megayear
		KY100: 4, // 100 Kiloyears
		KY10: 5, // 10 Kiloyears
		KY: 6, // Kiloyear
		YEAR100: 7, // 100 years
		YEAR10: 8, // 10 years
		YEAR: 9,
		MONTH: 10,
		DAY: 11,
		HOUR: 12,
		MINUTES: 13,
		SECOND: 14
	};

	/**
	 * Returns whether a given number can be interpreted as a Time's precision.
	 *
	 * @param {number} precision
	 * @return {boolean}
	 */
	Time.knowsPrecision = function( precision ) {
		var precisionKey;
		for( precisionKey in Time.PRECISION ) {
			if( Time.PRECISION[ precisionKey ] === precision ) {
				return true;
			}
		}
		return false;
	};

	/**
	 * Returns the lowest possible precision from the time.Time.PRECISION enum.
	 *
	 * @return {Number}
	 */
	Time.minPrecision = function() {
		return Time.PRECISION.GY;
	};

	/**
	 * Returns the highest possible precision from the time.Time.PRECISION enum.
	 *
	 * @return {Number}
	 */
	Time.maxPrecision = function() {
		return Time.PRECISION.SECOND;
	};

	/**
	 * All supported calendar models
	 * @type {Object}
	 */
	Time.CALENDAR = {
		GREGORIAN: 'Gregorian',
		JULIAN: 'Julian'
	};

	return Time; // expose time.Time

}( time, jQuery ) );
