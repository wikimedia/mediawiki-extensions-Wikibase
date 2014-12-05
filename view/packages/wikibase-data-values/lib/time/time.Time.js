( function( time, $ ) {
	'use strict';

	// TODO: get rid of heavy jQuery dependency

	/**
	 * time.js's Time constructor for parsing and representing a time.
	 * @class time.Time
	 * @licence GNU GPL v2+
	 * @author Denny Vrandečić
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @constructor
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
	var SELF = time.Time = function Time( timeDefinition, options ) {
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
			SELF.validate( result );
		}

		if( result === null ) {
			throw new Error( 'time.Time object is invalid' );
		}

		this._year = result.year || 0;
		this._month = result.month || 0;
		this._day = result.day || 0;
		this._hour = result.hour || 0;
		this._minute = result.minute || 0;
		this._second = result.second || 0;
		this._utcoffset = '+00:00';
		this._calendarname = SELF.CALENDAR.GREGORIAN;

		if( options.calendarname ) {
			this._calendarname = options.calendarname;
		} else if ( result.calendarname !== undefined ) {
			this._calendarname = result.calendarname;
		}

		this._precision = options.precision !== undefined ? options.precision : result.precision;

		this._before = 0;
		this._after = 0;
	};

	SELF.prototype = {
		constructor: SELF,

		/**
		 * @property {number}
		 * @private
		 */
		_year: null,

		/**
		 * @property {number}
		 * @private
		 */
		_month: null,

		/**
		 * @property {number}
		 * @private
		 */
		_day: null,

		/**
		 * @property {number}
		 * @private
		 */
		_hour: null,

		/**
		 * @property {number}
		 * @private
		 */
		_minute: null,

		/**
		 * @property {number}
		 * @private
		 */
		_second: null,

		/**
		 * @property {string}
		 * @private
		 */
		_utcoffset: null,

		/**
		 * @property {string}
		 * @private
		 */
		_calendarname: null,

		/**
		 * @property {number}
		 * @private
		 */
		_precision: null,

		/**
		 * @property {number}
		 * @private
		 */
		_before: null,

		/**
		 * @property {number}
		 * @private
		 */
		_after: null,

		/**
		 * @return {number}
		 */
		year: function() {
			return this._year;
		},

		/**
		 * @return {number}
		 */
		month: function() {
			return this._month;
		},

		/**
		 * @return {number}
		 */
		day: function() {
			return this._day;
		},

		/**
		 * @return {string}
		 */
		utcoffset: function() {
			return this._utcoffset;
		},

		/**
		 * @return {number}
		 */
		precision: function() {
			return this._precision;
		},

		/**
		 * @return {string}
		 */
		precisionText: function() {
			return time.precisionText( this._precision );
		},

		/**
		 * @return {number}
		 */
		before: function() {
			return this._before;
		},

		/**
		 * @return {number}
		 */
		after: function() {
			return this._after;
		},

		/**
		 * Returns the Gregorian date.
		 *
		 * @return {Object}
		 */
		gregorian: function() {
			if( this._calendarname === SELF.CALENDAR.GREGORIAN ) {
				return {
					'year': this._year,
					'month': this._month,
					'day': this._day
				};
			} else if( this._calendarname === SELF.CALENDAR.JULIAN ) {
				return time.julianToGregorian( this._year, this._month, this._day );
			}
		},

		/**
		 * Returns the Julian date.
		 *
		 * @return {Object|null}
		 */
		julian: function() {
			if( this._calendarname === SELF.CALENDAR.JULIAN ) {
				return {
					year: this._year,
					month: this._month,
					day: this._day
				};
			} else if( this._calendarname === SELF.CALENDAR.GREGORIAN ) {
				if( this._year !== null ) {
					return time.gregorianToJulian( this._year, this._month, this._day );
				}
			}
			return null;
		},

		/**
		 * Returns the Julian day number.
		 *
		 * @return {number|null}
		 */
		jdn: function() {
			if( this._year === null ) {
				return null;
			}
			if( this._calendarname === SELF.CALENDAR.GREGORIAN ) {
				return time.gregorianToJulianDay( this._year, this._month, this._day );
			} else {
				return time.julianToJulianDay( this._year, this._month, this._day );
			}
		},

		/**
		 * Returns the calendar name.
		 *
		 * @return {string}
		 */
		calendar: function() {
			return this._calendarname;
		},

		/**
		 * Returns the calendar URI.
		 *
		 * @return {string}
		 */
		calendarURI: function() {
			if( this._calendarname === SELF.CALENDAR.GREGORIAN ) {
				return 'http://www.wikidata.org/entity/Q1985727';
			} else if( this._calendarname === SELF.CALENDAR.JULIAN ) {
				return 'http://www.wikidata.org/entity/Q1985786';
			}
		},

		/**
		 * Returns the ISO 8601 string.
		 *
		 * @return {string}
		 */
		iso8601: function() {
			var g = this.gregorian();
			return ( ( g.year < 0 ) ? '-' : '+' ) + pad( g.year, 11 ) + '-' + pad( g.month, 2 )
				+ '-' + pad( g.day, 2 ) + 'T' + pad( this._hour, 2 ) + ':' + pad( this._minute, 2 )
				+ ':' + pad( this._second, 2 ) + 'Z';
		},

		/**
		 * Returns the formatted date.
		 *
		 * @param [options={}]
		 * @return {string}
		 */
		text: function( options ) {
			options = options || {};

			if( this._year === null ) {
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

			if( this._precision < 9 ) {
				return time.writeApproximateYear( this._year, this._precision );
			}

			if( this._precision === 9 ) {
				return time.writeYear( this._year );
			}

			var result = '';

			if( this._precision >= 10 ) {
				var template = options.format;

				if( this._precision < 11 ) {
					template = template.replace( /d/, '' );
				}

				template = template.split( '' ).join( ' ' );

				for( var i = 0; i < template.length; i++ ) {
					switch( template[i] ) {
						case 'y': result += time.writeYear( this._year ); break;
						case 'm': result += time.writeMonth( this._month ); break;
						case 'd': result += time.writeDay( this._day ); break;
						default: result += template[i];
					}
				}
			}

			if( this._precision > 11 ) {
				result += ' (time not implemented yet)';
			}

			return result;
		},

		/**
		 * Returns whether some given time is equal to this one.
		 *
		 * @param {*} otherTime
		 * @return {boolean}
		 */
		equals: function( otherTime ) {
			if( !( otherTime instanceof SELF ) ) {
				return false;
			}

			return this.precision() === otherTime.precision()
				&& this.calendar() === otherTime.calendar()
				&& this.after() === otherTime.after()
				&& this.before() === otherTime.before()
				&& this.utcoffset() === otherTime.utcoffset()
				&& this.iso8601() === otherTime.iso8601();
		}
	};

	function pad( number, digits ) {
		return ( 1e12 + Math.abs( number ) + '' ).slice( -digits );
	}

	/**
	 * Creates a new Time object by a given iso8601 string like "+00000002000-12-31T23:59:59Z".
	 * TODO: this function shouldn't really be required since the parser should simply be able to
	 *       take such a string and create a new Time object from it. It could be kept for
	 *       performance reasons though.
	 * @property {Function}
	 * @static
	 *
	 * @param {string} iso8601String
	 * @param {number} [precision=time.Time.PRECISION.DAY] If not given, precision will be as high
	 *        as possible.
	 * @return time.Time
	 *
	 * @throws {Error} If the input string is invalid.
	 */
	SELF.newFromIso8601 = function( iso8601String, precision ) {
		var year, month, day, timeObj;

		try {
			var matches = /^([+-]?\d+)-(\d+)-(\d+)(?=T)/.exec( iso8601String );
			year = parseInt( matches[1], 10 );
			month = parseInt( matches[2], 10 );
			day = parseInt( matches[3], 10 );
		} catch( e ) {
			throw new Error( 'Unprocessable iso8601 string given' );
		}

		timeObj = {
			year: year,
			precision: precision !== undefined ? precision : SELF.PRECISION.DAY,
			calendarname: SELF.CALENDAR.GREGORIAN
		};

		if( month !== 0 ) {
			timeObj.month = month;
		}

		if( day !== 0 ) {
			timeObj.day = day;
		}

		return new SELF( timeObj );
	};

	/**
	 * Enum of all possible precisions of Time.
	 * @property {Object}
	 * @static
	 */
	SELF.PRECISION = {
		GY: 0,
		MY100: 1,
		MY10: 2,
		MY: 3,
		KY100: 4,
		KY10: 5,
		KY: 6,
		YEAR100: 7,
		YEAR10: 8,
		YEAR: 9,
		MONTH: 10,
		DAY: 11,
		HOUR: 12,
		MINUTES: 13,
		SECOND: 14
	};

	/**
	 * Returns whether a given number can be interpreted as a Time's precision.
	 * @property {Function}
	 * @static
	 *
	 * @param {number} precision
	 * @return {boolean}
	 */
	SELF.knowsPrecision = function( precision ) {
		var precisionKey;
		for( precisionKey in SELF.PRECISION ) {
			if( SELF.PRECISION[ precisionKey ] === precision ) {
				return true;
			}
		}
		return false;
	};

	/**
	 * Returns the lowest possible precision from the time.Time.PRECISION enum.
	 * @property {Function}
	 * @static
	 *
	 * @return {number}
	 */
	SELF.minPrecision = function() {
		return SELF.PRECISION.GY;
	};

	/**
	 * Returns the highest possible precision from the time.Time.PRECISION enum.
	 * @property {Function}
	 * @static
	 *
	 * @return {number}
	 */
	SELF.maxPrecision = function() {
		return SELF.PRECISION.SECOND;
	};

	/**
	 * Enum of all supported calendar models.
	 * @property {Object}
	 * @static
	 */
	SELF.CALENDAR = {
		GREGORIAN: 'Gregorian',
		JULIAN: 'Julian'
	};

	/**
	 * time.Time.validate for validating structures passed to the time.Time constructor.
	 * @property {Function}
	 * @static
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @param {Object} definition
	 *
	 * @throws {Error} if time definition is invalid.
	 */
	SELF.validate = function validateTimeDefinition( definition ) {
		validateFieldTypes( definition, {
			day: 'number',
			month: 'number',
			year: 'number',
			calendarname: 'string',
			precision: 'number'
		} );

		checkPrecisionRequirements( definition );

		if( definition.month < 0 || definition.month > 12 ) {
			throw new Error( 'Month out of [0,12] range.' );
		}

		if( definition.day < 0 || definition.day > 31 ) {
			throw new Error( 'Day out of [0,31] range.' );
		}
		// TODO: Add check for last day of the month once we have one validator per calendar model.

		// TODO: remove the following check once we have one validator per calendar model:
		if( definition.calendarname !== time.Time.CALENDAR.GREGORIAN
			&& definition.calendarname !== time.Time.CALENDAR.JULIAN
		) {
			throw new Error( '"calendarname" is "' + definition.calendarname + '" but has to be "'
				+ time.Time.CALENDAR.GREGORIAN + '" or "' + time.Time.CALENDAR.JULIAN + '"' );
		}
	};

	/**
	 * Makes sure a given structure has a proper precision set by validating the precision itself
	 * and checking if all fields required by that precision are set. E.g. if precision is "MONTH",
	 * then also the field "year" has to be given.
	 * @ignore
	 *
	 * @param {Object} definition
	 *
	 * @throws {Error}
	 */
	function checkPrecisionRequirements( definition ) {
		var precision = definition.precision,
			year = definition.year;

		if( !time.Time.knowsPrecision( precision ) ) {
			throw new Error( 'Unknown precision "' + definition.precision + '" given in "precision"' );
		}

		// make sure fields with time information required for given precision are set:
		if( precision > time.Time.PRECISION.DAY ) {
			throw new Error( 'Precision higher than "DAY" is not yet supported' );
		}
		if( precision >= time.Time.PRECISION.DAY && !definition.day ) {
			throw new Error( 'Field "day" required because precision is "DAY"' );
		}
		if( precision >= time.Time.PRECISION.MONTH && !definition.month ) {
			throw new Error( 'Field "month" required because precision is "MONTH"' );
		}

		// year is always required
		if( year === undefined || isNaN( year ) || !isFinite( year ) ) {
			throw new Error( '"year" has to be a finite number' );
		}
	}

	/**
	 * Checks a definition for certain fields. If the field is available, an error will be thrown
	 * in case the field is not of the specified type.
	 * @ignore
	 *
	 * @param {{key: string, type: string}} fieldTypes
	 * @param {Object} definition
	 */
	function validateFieldTypes( fieldTypes, definition ) {
		var field, value, requiredType;

		for( field in definition ) {
			value = fieldTypes[ field ];
			requiredType = definition[ field ];

			if( !requiredType ) {
				throw new Error( 'Unknown field "' + field + '" found in structure' );
			}
			if( value !== undefined && typeof value !== requiredType ) {
				throw new Error( 'Field "' + field + '" has to be of type ' + requiredType );
			}
		}
	}

}( time, jQuery ) );
