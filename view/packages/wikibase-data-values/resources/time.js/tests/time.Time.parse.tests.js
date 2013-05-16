/**
 * @since 0.1
 * @file
 * @ingroup Time.js
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( QUnit, $, Time, validTimeDefinitions ) {
	'use strict';

	var PRECISION = Time.PRECISION,
		G = Time.CALENDAR.GREGORIAN,
		J = Time.CALENDAR.JULIAN;

	QUnit.module( 'time.js: time.Time.parse()' );

	var times = $.extend( {}, validTimeDefinitions, {
		'foo': null, // TODO: in error case, the parser should throw an error, not just return null!
		'42 abc': null
	} );

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

}( QUnit, jQuery, time.Time, time.validTimeDefinitions ) );
