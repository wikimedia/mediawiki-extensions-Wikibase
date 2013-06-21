/**
 * Object holding example time definitions indexed by a string describing it. The string describing
 * it is basically a string that could be fed to the time parser, the parsed result should be a
 * time definition equal to the ones given here. This is particularly useful for testing purposes.
 * The definition assumes that the settings "daybeforemonth" is set to true.
 *
 * @since 0.1
 * @file
 * @ingroup Time.js
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
time.validTimeDefinitions = ( function( time ) {
	'use strict';

	var Time = time.Time,
		PRECISION = Time.PRECISION,
		G = Time.CALENDAR.GREGORIAN,
		J = Time.CALENDAR.JULIAN;

	return {
		'45 BC': {
			calendarname: G,
			year: -44,
			precision: PRECISION.YEAR
		},
		'12 October 1492': {
			calendarname: J,
			year: 1492,
			month: 10,
			day: 12,
			precision: PRECISION.DAY
		},
		'5. September 1981': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'5 9 1981': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'5-9-1981': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'5,9,1981': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'1981,9,5': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'September 5 1981': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'1981, September 5': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'5. 9. 1981': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'001981-September-00005': {
			calendarname: G,
			year: 1981,
			month: 9,
			day: 5,
			precision: PRECISION.DAY
		},
		'March 45 BC': {
			calendarname: G,
			month: 3,
			year: -44,
			precision: PRECISION.MONTH
		},
		'April 23, 1616 Old Style': {
			calendarname: J,
			year: 1616,
			month: 4,
			day: 23,
			precision: PRECISION.DAY
		},
		'22.4.1616 Gregorian': {
			calendarname: G,
			year: 1616,
			month: 4,
			day: 22,
			precision: PRECISION.DAY
		},
		'2001-01-02': {
			calendarname: G,
			year: 2001,
			month: 1,
			day: 2,
			precision: PRECISION.DAY
		},
		'November 20, 1989': {
			calendarname: G,
			year: 1989,
			month: 11,
			day: 20,
			precision: PRECISION.DAY
		},
		'1.2.3': {
			calendarname: J,
			year: 3,
			month: 2,
			day: 1,
			precision: PRECISION.DAY
		},
		'15.2.3': {
			calendarname: J,
			year: 3,
			month: 2,
			day: 15,
			precision: PRECISION.DAY
		},
		'111-10': {
			calendarname: G,
			year: 111,
			month: 10,
			precision: PRECISION.MONTH
		},
		'-2003-10': {
			calendarname: G,
			year: -2003,
			month: 10,
			precision: PRECISION.MONTH
		},
		'10.-2003': {
			calendarname: G,
			year: -2003,
			month: 10,
			precision: PRECISION.MONTH
		},
		'20.10.-2003': {
			calendarname: J,
			year: -2003,
			month: 10,
			day: 20,
			precision: PRECISION.DAY
		},
		'-1000-11-12': {
			calendarname: J,
			year: -1000,
			month: 11,
			day: 12,
			precision: PRECISION.DAY
		},
		'-1000': {
			calendarname: G,
			year: -1000,
			precision: PRECISION.YEAR
		},
		'1980s': {
			calendarname: G,
			year: 1980,
			precision: PRECISION.YEAR10
		},
		'in 300,000 years': {
			calendarname: G,
			year: 300000,
			precision: PRECISION.KY100
		},
		'2 billion years ago': {
			calendarname: G,
			year: -2000000000,
			precision: PRECISION.GY
		},
		'1. century BCE': {
			calendarname: G,
			year: -100,
			precision: PRECISION.YEAR100
		}
	};

}( time ) );
