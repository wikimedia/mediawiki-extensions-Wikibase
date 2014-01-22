/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( define ) {
'use strict';

var DEPS = [
	'time.Time',
	'jquery',
	'qunit',
	'time.Time.validate',
	'time.validTimeDefinitions'
];

define( DEPS, function( Time, $, QUnit ) {

	QUnit.module( 'time.js: time.Time.newFromIso8601()' );

	/**
	 * Executes an assertion to test whether a given iso8601String can be turned into a time.Time
	 * instance.
	 *
	 * @param {QUnit.assert} assert
	 * @param {string} iso8601String
	 * @return {time.Time|null}
	 */
	function testNewFromIso8601( assert, iso8601String ) {
		var time;
		try {
			time = Time.newFromIso8601( iso8601String ); // throws an error if failure
		} catch( e ) {
			time = null;
		}

		assert.ok(
			time,
			'newFromIso8601() built new Time from "' + iso8601String + '"'
		);
		return time;
	}

	QUnit.test( 'newFromIso8601 by random valid iso8601 strings', function( assert ) {
		var isoStrings = [];

		$.each( time.validTimeDefinitions, function( name, definition ) {
			var time = new Time( definition );
			isoStrings.push( time.iso8601() );
		} );

		$.each( isoStrings, function( i, iso8601String ) {
			testNewFromIso8601( assert, iso8601String );
		} );
	} );

	QUnit.test( 'newFromIso8601 with checks for correct date', function( assert ) {
		var isoStringsAndEquivalents = {
			'+00000001616-05-03T00:00:00Z': { year: 1616, month: 5, day: 3 },
			'+00000000000-01-01T00:00:00Z': { year: 0, month: 1, day: 1 },
			'-00000000010-09-08T00:00:00Z': { year: -10, month: 9, day: 8 },
			'10-9-8T00:00:00Z': { year: 10, month: 9, day: 8 },
			'0-9-8T00:00:00Z': { year: 0, month: 9, day: 8 },
			'0003-4-01T00:00:00Z': { year: 3, month: 4, day: 1 },
			'-0-10-30T00:00:00Z': { year: 0, month: 10, day: 30 },
			'-10-12-1T00:00:00Z': { year: -10, month: 12, day: 1 },
			'120300001616-02-15T00:00:00Z': { year: 120300001616, month: 2, day: 15 },
			'1-3-5T': { year: 1, month: 3, day: 5 }
		};

		$.each( isoStringsAndEquivalents, function( iso8601String, equivalentDMY ) {
			var time = testNewFromIso8601( assert, iso8601String );
			if( ! time ) {
				return; // next assertion depends on success of test above
			}
			assert.deepEqual(
				{
					year: time.year(),
					month: time.month(),
					day: time.day()
				},
				equivalentDMY,
				'time\'s day, month and year equal time built from definition with key "' + iso8601String + '"'
			);
		} );
	} );

	QUnit.test( 'invalid iso8601 strings', function( assert ) {
		var UNSUPPORTED_VARIATION = 'unsupported variation of iso8601';
		var invalidIsoStrings = {
			'foo': 'nonsense string',
			'1000-10-10': UNSUPPORTED_VARIATION,
			'1000-10': UNSUPPORTED_VARIATION,
			'1000': UNSUPPORTED_VARIATION,
			'1200-13-23': 'month out of range'
		};

		$.each( invalidIsoStrings, function( iso8601String, title ) {
			assert.throws(
				function() {
					Time.newFromIso8601( iso8601String );
				},
				'time.Time.newFromIso8601( "' + iso8601String + '" ) will result in an error because ' + title
			);
		} );
	} );

	QUnit.test( 'newFromIso8601 with overwritten precision', function( assert ) {
		function test( precisionName ) {
			var precision = Time.PRECISION[ precisionName ],
				time = Time.newFromIso8601( '123456789012-12-31T00:00:00', precision );

			assert.strictEqual(
				time.precision(),
				precision,
				'Precision ' + precisionName + ' has been passed through'
			);

			assert.ok(
				time.year() === 123456789012
					&& time.month() === 12
					&& time.day() === 31,
				'Created time is accurate'
			);
		}

		$.each( Time.PRECISION, function( precisionName, precision ) {
			if( precision > Time.PRECISION.DAY ) {
				return; // not supported yet so we can't test for it
			}
			test( precisionName );
		} );

	} );

} );

}( define ) );
