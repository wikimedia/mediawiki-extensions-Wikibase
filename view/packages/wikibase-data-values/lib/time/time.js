/**
 * time.js by Denny Vrandečić
 * Source: http://simia.net/valueparser/time.js
 *
 * VERSION: 0.1
 *
 * @since 0.1
 * @file
 * @ingroup Time.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 */
this.time = ( function() { // 'this' is global scope, e.g. 'window' in the browser and 'global' on the server
	'use strict';

	var time = {};

	// TODO: get rid of global settings, inject them where required
	var settings = {};

	settings.bce = ['BCE', 'BC', 'B.C.', 'before Common Era', 'before Christ'];
	settings.ace = ['CE', 'AD', 'A.D.', 'Anno Domini', 'Common Era'];
	settings.pasttext = '% ago';
	settings.futuretext = 'in %';
	settings.calendarnames = {
		'Gregorian': [ 'Gregorian', 'G', 'GD', 'GC', 'NS', 'N.S.', 'New Style', 'Gregorian calendar', 'Gregorian date' ],
		'Julian': [ 'Julian', 'J', 'JD', 'JC', 'OS', 'O.S.', 'Old Style', 'Julian calendar', 'Julian date' ]
	};
	settings.daybeforemonth = true;

	settings.monthnames = [
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
	];

	settings.precisiontexts = [
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
	];

	settings.outputprecision = [
		'% billion years',
		'%00 million years',
		'%0 million years',
		'% million years',
		'%00,000 years',
		'%0,000 years',
		'%. millenium',
		'%. century',
		'%0s'
	];

	/**
	 * Factors correlating to settings.outputprecision. These are use when detecting and
	 * re-converting input specified in one of the formats specified in outputprecisions.
	 * @type {number[]}
	 */
	settings.outputprecisionFactors = [
		1000000000,
		100000000,
		10000000,
		1000000,
		100000,
		10000,
		1000,
		100,
		10
	];

	var maxPrecision = function() {
		return 14;
	};

	var julianToJulianDay = function( year, month, day ) {
		// based on en.wikipedia.org/wiki/Julian_day_number
		var a = Math.floor( (14 - month) / 12 ),
			y = year + 4800 - a,
			m = month + 12 * a - 3;

		return day + Math.floor( (153 * m + 2) / 5 ) + 365 * y + Math.floor( y / 4 ) - 32083;
	};

	var gregorianToJulianDay = function( year, month, day ) {
		// based on en.wikipedia.org/wiki/Julian_day_number
		var a = Math.floor( (14 - month) / 12 ),
			y = year + 4800 - a,
			m = month + 12 * a - 3;

		return day + Math.floor( (153 * m + 2) / 5 ) + 365 * y + Math.floor( y / 4 )
			- Math.floor( y / 100 ) + Math.floor( y / 400 ) - 32045;
	};

	var julianDayToJulian = function( jdn ) {
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

	var julianDayToGregorian = function( jdn ) {
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

	var julianToGregorian = function( year, month, day ) {
		var julianDay = julianToJulianDay( year, month, day );
		return julianDayToGregorian( julianDay );
	};

	var gregorianToJulian = function( year, month, day ) {
		var julianDay = gregorianToJulianDay( year, month, day );
		return julianDayToJulian( julianDay );
	};

	var writeApproximateYear = function( year, precision ) {
		var significant = 0,
			text = '';

		if( precision === 8 ) {
			significant = Math.floor( ( Math.abs( year ) ) / Math.pow( 10, 9 - precision ) );
		} else {
			significant = Math.floor( ( Math.abs( year ) - 1) / Math.pow( 10, 9 - precision ) ) + 1;
		}

		text = settings.outputprecision[precision].replace( '%', significant );

		if( precision < 6 ) {
			if( year < 0 ) {
				text = settings.pasttext.replace( '%', text );
			} else {
				text = settings.futuretext.replace( '%', text );
			}
		} else {
			if( year < 1 ) {
				text += ' ' + settings.bce[0];
			}
		}

		return text;
	};

	var writeYear = function( year ) {
		if( year < 0 ) {
			return -1 * (year - 1) + ' ' + settings.bce[0];
		}
		if( year === 0 ) {
			return '1 ' + settings.bce[0];
		}
		return String( year );
	};

	var writeMonth = function( month ) {
		return settings.monthnames[month - 1][0];
	};

	var writeDay = function( day ) {
		return String( day );
	};

	var precisionText = function( acc ) {
		if( (acc > maxPrecision()) || (acc < 0) ) {
			return undefined;
		}
		return settings.precisiontexts[acc];
	};

	time.julianToGregorian = julianToGregorian;
	time.gregorianToJulian = gregorianToJulian;
	time.julianToJulianDay = julianToJulianDay;
	time.gregorianToJulianDay = gregorianToJulianDay;
	time.julianDayToGregorian = julianDayToGregorian;
	time.julianDayToJulian = julianDayToJulian;

	time.writeApproximateYear = writeApproximateYear;
	time.writeYear = writeYear;
	time.writeMonth = writeMonth;
	time.writeDay = writeDay;
	time.precisionText = precisionText;
	time.maxPrecision = maxPrecision;

	time.settings = settings;

	return time; // export
}() );
