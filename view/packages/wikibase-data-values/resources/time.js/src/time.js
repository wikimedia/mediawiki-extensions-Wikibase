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
( function( global ) {

	var time = {},
		_oldTime = global.time;

	global.time = time;

	time.noConflict = function() {
		global.time = _oldTime;
		return time;
	};

	// TODO: get rid of global settings, inject them where required
	var settings = {};

	settings.bce = ['BCE', 'BC', 'B.C.', 'before Common Era', 'before Christ'];
	settings.ace = ['CE', 'AD', 'A.D.', 'Anno Domini', 'Common Era'];
	settings.pasttext = '% ago';
	settings.futuretext = 'in %';
	settings.calendarnames = [];
	settings.calendarnames[0] = [
		'Gregorian', 'G', 'GD', 'GC', 'NS', 'N.S.', 'New Style', 'Gregorian calendar', 'Gregorian date'
	];
	settings.calendarnames[1] = [
		'Julian', 'J', 'JD', 'JC', 'OS', 'O.S.', 'Old Style', 'Julian calendar', 'Julian date'
	];
	settings.daybeforemonth = true;

	settings.monthnames = [];
	settings.monthnames[0] = ['January', 'Jan'];
	settings.monthnames[1] = ['February', 'Feb'];
	settings.monthnames[2] = ['March', 'Mar'];
	settings.monthnames[3] = ['April', 'Apr'];
	settings.monthnames[4] = ['May'];
	settings.monthnames[5] = ['June', 'Jun'];
	settings.monthnames[6] = ['July', 'Jul'];
	settings.monthnames[7] = ['August', 'Aug'];
	settings.monthnames[8] = ['September', 'Sep'];
	settings.monthnames[9] = ['October', 'Oct'];
	settings.monthnames[10] = ['November', 'Nov'];
	settings.monthnames[11] = ['December', 'Dec'];

	settings.precisiontexts = [];
	settings.precisiontexts[0] = 'billion years';
	settings.precisiontexts[1] = 'hundred million years';
	settings.precisiontexts[2] = 'ten million years';
	settings.precisiontexts[3] = 'million years';
	settings.precisiontexts[4] = '100,000 years';
	settings.precisiontexts[5] = '10,000 years';
	settings.precisiontexts[6] = 'millenium';
	settings.precisiontexts[7] = 'century';
	settings.precisiontexts[8] = 'decade';
	settings.precisiontexts[9] = 'year';
	settings.precisiontexts[10] = 'month';
	settings.precisiontexts[11] = 'day';
	settings.precisiontexts[12] = 'hour';
	settings.precisiontexts[13] = 'minute';
	settings.precisiontexts[14] = 'second';

	settings.outputprecision = [];
	settings.outputprecision[0] = '% billion years';
	settings.outputprecision[1] = '%00 million years';
	settings.outputprecision[2] = '%0 million years';
	settings.outputprecision[3] = '% million years';
	settings.outputprecision[4] = '%00,000 years';
	settings.outputprecision[5] = '%0,000 years';
	settings.outputprecision[6] = '%. millenium';
	settings.outputprecision[7] = '%. century';
	settings.outputprecision[8] = '%0s';

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
		var julianday = julianToJulianDay( year, month, day );
		return julianDayToGregorian( julianday );
	};

	var gregorianToJulian = function( year, month, day ) {
		var julianday = gregorianToJulianDay( year, month, day );
		return julianDayToJulian( julianday );
	};

	var writeApproximateYear = function( year, precision ) {
		var significant = 0,
			text = '';

		if( precision == 8 ) {
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
		return year;
	};

	var writeMonth = function( year, month ) {
		return settings.monthnames[month - 1][0] + ' ' + writeYear( year );
	};

	var writeDay = function( year, month, day ) {
		return settings.monthnames[ month - 1 ][0] + ' ' + day + ', ' + writeYear( year );
	};

	var getTextFromDate = function( precision, year, month, day ) {
		var retval = '';
		if( year === null ) {
			return '';
		}
		if( precision < 9 ) {
			return writeApproximateYear( year, precision );
		}
		switch( precision ) {
			case 9:
				return writeYear( year );
			case 10:
				return writeMonth( year, month );
			case 11:
				return writeDay( year, month, day );
			default:
				return writeDay( year, month, day ) + ' (time not implemented yet)';
		}
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
	time.getTextFromDate = getTextFromDate;
	time.precisionText = precisionText;
	time.maxPrecision = maxPrecision;

	time.settings = settings;

}( this ) ); // 'this' is global scope, i.e. 'window' in the browser and 'global' on the server
