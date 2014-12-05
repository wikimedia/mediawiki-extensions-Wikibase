/**
 * time.js by Denny Vrandečić
 * Source: http://simia.net/valueparser/time.js
 * VERSION: 0.1
 * @class time
 * @singleton
 * @licence GNU GPL v2+
 * @author Denny Vrandečić
 */
this.time = new ( function time() { // 'this' is global scope, e.g. 'window' in the browser and 'global' on the server
	'use strict';

	// TODO: get rid of global settings, inject them where required
	/**
	 * @property {Object}
	 */
	this.settings = {
		bce: ['BCE', 'BC', 'B.C.', 'before Common Era', 'before Christ'],
		ace: ['CE', 'AD', 'A.D.', 'Anno Domini', 'Common Era'],
		pasttext: '% ago',
		futuretext: 'in %',
		calendarnames: {
			'Gregorian': [ 'Gregorian', 'G', 'GD', 'GC', 'NS', 'N.S.', 'New Style', 'Gregorian calendar', 'Gregorian date' ],
			'Julian': [ 'Julian', 'J', 'JD', 'JC', 'OS', 'O.S.', 'Old Style', 'Julian calendar', 'Julian date' ]
		},
		daybeforemonth: true,
		monthnames: [
			[ 'January', 'Jan' ],
			[ 'February', 'Feb' ],
			[ 'March', 'Mar' ],
			[ 'April', 'Apr' ],
			[ 'May' ],
			[ 'June', 'Jun' ],
			[ 'July', 'Jul' ],
			[ 'August', 'Aug' ],
			[ 'September', 'Sep' ],
			[ 'October', 'Oct' ],
			[ 'November', 'Nov' ],
			[ 'December', 'Dec' ]
		],
		precisiontexts: [
			'billion years',
			'hundred million years',
			'ten million years',
			'million years',
			'100,000 years',
			'10,000 years',
			'millenium',
			'century',
			'decade',
			'year',
			'month',
			'day',
			'hour',
			'minute',
			'second'
		],
		outputprecision: [
			'% billion years',
			'%00 million years',
			'%0 million years',
			'% million years',
			'%00,000 years',
			'%0,000 years',
			'%. millenium',
			'%. century',
			'%0s'
		],
		// Factors correlating to settings.outputprecision. These are use when detecting and
		// re-converting input specified in one of the formats specified in outputprecisions.
		outputprecisionFactors: [
			1000000000,
			100000000,
			10000000,
			1000000,
			100000,
			10000,
			1000,
			100,
			10
		]
	};

	/**
	 * Returns the highest precision available.
	 *
	 * @return {number}
	 */
	this.maxPrecision = function() {
		return 14;
	};

	/**
	 * Returns the continuous count of days since the beginning of the Julian period for a Julian
	 * date.
	 *
	 * @param {number} year
	 * @param {number} month
	 * @param {number} day
	 * @return {number}
	 */
	this.julianToJulianDay = function( year, month, day ) {
		// based on en.wikipedia.org/wiki/Julian_day_number
		var a = Math.floor( (14 - month) / 12 ),
			y = year + 4800 - a,
			m = month + 12 * a - 3;

		return day + Math.floor( (153 * m + 2) / 5 ) + 365 * y + Math.floor( y / 4 ) - 32083;
	};

	/**
	 * Returns the continuous count of days since the beginning of the Julian period for a Gregorian
	 * date.
	 *
	 * @param {number} year
	 * @param {number} month
	 * @param {number} day
	 * @return {number}
	 */
	this.gregorianToJulianDay = function( year, month, day ) {
		// based on en.wikipedia.org/wiki/Julian_day_number
		var a = Math.floor( (14 - month) / 12 ),
			y = year + 4800 - a,
			m = month + 12 * a - 3;

		return day + Math.floor( (153 * m + 2) / 5 ) + 365 * y + Math.floor( y / 4 )
			- Math.floor( y / 100 ) + Math.floor( y / 400 ) - 32045;
	};

	/**
	 * Returns the Julian date for a Julian day number.
	 *
	 * @param {number} jdn
	 * @return {Object}
	 */
	this.julianDayToJulian = function( jdn ) {
		// based on http://www.tondering.dk/claus/cal/julperiod.php
		var result = {},
			b = 0,
			c = jdn + 32082,
			d = Math.floor( (4 * c + 3) / 1461 ),
			e = c - Math.floor( (1461 * d) / 4 ),
			m = Math.floor( (5 * e + 2) / 153 );

		result.year = 100 * b + d - 4800 + Math.floor( m / 10 );
		result.month = m + 3 - 12 * Math.floor( m / 10 );
		result.day = e - Math.floor( (153 * m + 2) / 5 ) + 1;
		return result;
	};

	/**
	 * Returns the Gregorian date for a Julian day number.
	 *
	 * @param {number} jdn
	 * @return {Object}
	 */
	this.julianDayToGregorian = function( jdn ) {
		// based on http://www.tondering.dk/claus/cal/julperiod.php
		var result = {},
			a = jdn + 32044,
			b = Math.floor( (4 * a + 3) / 146097 ),
			c = a - Math.floor( (146097 * b) / 4 ),
			d = Math.floor( (4 * c + 3) / 1461 ),
			e = c - Math.floor( (1461 * d) / 4 ),
			m = Math.floor( (5 * e + 2) / 153 );

		result.year = 100 * b + d - 4800 + Math.floor( m / 10 );
		result.month = m + 3 - 12 * Math.floor( m / 10 );
		result.day = e - Math.floor( (153 * m + 2) / 5 ) + 1;
		return result;
	};

	/**
	 * Converts a Julian date to Gregorian.
	 *
	 * @param {number} year
	 * @param {number} month
	 * @param {number} day
	 * @return {Object}
	 */
	this.julianToGregorian = function( year, month, day ) {
		var julianDay = this.julianToJulianDay( year, month, day );
		return this.julianDayToGregorian( julianDay );
	};

	/**
	 * Converts a Gregorian date to Julian.
	 *
	 * @param {number} year
	 * @param {number} month
	 * @param {number} day
	 * @return {Object}
	 */
	this.gregorianToJulian = function( year, month, day ) {
		var julianDay = this.gregorianToJulianDay( year, month, day );
		return this.julianDayToJulian( julianDay );
	};

	/**
	 * Returns a string denoting the year precision.
	 *
	 * @param {number} year
	 * @param {number} precision
	 * @return {string}
	 */
	this.writeApproximateYear = function( year, precision ) {
		var significant = 0,
			text = '';

		if( precision === 8 ) {
			significant = Math.floor( ( Math.abs( year ) ) / Math.pow( 10, 9 - precision ) );
		} else {
			significant = Math.floor( ( Math.abs( year ) - 1) / Math.pow( 10, 9 - precision ) ) + 1;
		}

		text = this.settings.outputprecision[precision].replace( '%', significant );

		if( precision < 6 ) {
			if( year < 0 ) {
				text = this.settings.pasttext.replace( '%', text );
			} else {
				text = this.settings.futuretext.replace( '%', text );
			}
		} else {
			if( year < 1 ) {
				text += ' ' + this.settings.bce[0];
			}
		}

		return text;
	};

	/**
	 *  Returns a year as string, adding BCE if applicable.
	 *
	 * @param {number} year
	 * @return {string}
	 */
	this.writeYear = function( year ) {
		if( year < 0 ) {
			return -1 * (year - 1) + ' ' + this.settings.bce[0];
		}
		if( year === 0 ) {
			return '1 ' + this.settings.bce[0];
		}
		return String( year );
	};

	/**
	 * Returns a month name.
	 *
	 * @param {number} month
	 * @return {string}
	 */
	this.writeMonth = function( month ) {
		return this.settings.monthnames[month - 1][0];
	};

	/**
	 * Returns a day as string.
	 *
	 * @param {number} day
	 * @return {string}
	 */
	this.writeDay = function( day ) {
		return String( day );
	};

	/**
	 * Returns the string representation of a precision.
	 *
	 * @param {number} acc
	 * @return {string}
	 */
	this.precisionText = function( acc ) {
		if( (acc > this.maxPrecision()) || (acc < 0) ) {
			return undefined;
		}
		return this.settings.precisiontexts[acc];
	};

	return this;
} )();
