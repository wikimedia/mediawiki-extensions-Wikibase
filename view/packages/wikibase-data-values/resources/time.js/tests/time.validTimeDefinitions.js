/**
 * Object holding example time definitions indexed by a string describing it. The string describing
 * it is basically a string that could be fed to the time parser, the parsed result should be a
 * time definition equal to the ones given here. This is particularly useful for testing purposes.
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
		'2001-01-01': {
			calendarname: G,
			year: 2001,
			month: 1,
			day: 1,
			precision: PRECISION.DAY
		},
		'November 20, 1989': {
			calendarname: G,
			year: 1989,
			month: 11,
			day: 20,
			precision: PRECISION.DAY
		}
	};

}( time ) );
