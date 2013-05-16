/**
 * @since 0.1
 * @file
 * @ingroup Time.js
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( QUnit, $, Time ) {
	'use strict';

	var PRECISION = Time.PRECISION,
		G = Time.CALENDAR.GREGORIAN,
		J = Time.CALENDAR.JULIAN;

	QUnit.module( 'time.js: time.Time.parse()' );

	var times = {
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
		},
		'foo': null, // TODO: in error case, the parser should throw an error, not just return null!
		'42 abc': null
	};

	QUnit.test( 'random parsing', function( assert ) {
		$.each( times, function( timeInput, exptectedTimeDefinition ) {
			var parsedTime,
				timeObject;

			parsedTime = Time.parse( timeInput );
			assert.deepEqual(
				parsedTime,
				exptectedTimeDefinition,
				'"' + timeInput + '" has been parsed successfully'
			);

			// test integration with time.Time:
			if( parsedTime !== null ) {
				timeObject = new Time( parsedTime );
				assert.ok(
					timeObject.isValid(),
					'"' + timeInput + '" parser result can be used to create new valid time.Time instance'
				);
			}
		} );
	} );

}( QUnit, jQuery, time.Time ) );
