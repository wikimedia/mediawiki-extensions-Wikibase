/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
define( [
	'time/time',
	'tests/lib/time/time.validTimeDefinitions',
	'jquery',
	'qunit',
	'time/time.Parser'
], function( time, validTimeDefinitions, $, QUnit ) {
	'use strict';

	QUnit.module( 'time.js: time.Parser' );

	var times = $.extend( {}, validTimeDefinitions, {
		'foo': null, // TODO: in error case, the parser should throw an error, not just return null!
		'42 abc': null
	} );

	QUnit.test( 'random parsing', function( assert ) {
		// TODO: injecdt this setting into test parser instance rather than changing global settings
		var dbmStateBefore = time.settings.daybeforemonth,
			parser = new time.Parser();
		time.settings.daybeforemonth = true;

		$.each( times, function( timeInput, expectedTimeDefinition ) {
			var parsedTime,
				timeObject;

			parsedTime = parser.parse( timeInput );
			assert.deepEqual(
				parsedTime,
				expectedTimeDefinition,
				'"' + timeInput + '" has been parsed successfully'
			);

			// test integration with time.Time:
			if( parsedTime !== null ) {
				timeObject = new time.Time( parsedTime );

				assert.ok( timeObject instanceof time.Time, '"' + timeInput + '" parser result '
					+ 'can be used to create new time.Time instance' );
			}
		} );

		time.settings.daybeforemonth = dbmStateBefore; // reset state of evil global setting
	} );

} );
