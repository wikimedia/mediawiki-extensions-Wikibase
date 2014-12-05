/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
define( [
	'time/time.Time',
	'jquery',
	'qunit',
	'tests/lib/time/time.validTimeDefinitions'
], function( Time, $, QUnit ) {
	'use strict';

	var PRECISION = Time.PRECISION,
		G = Time.CALENDAR.GREGORIAN,
		J = Time.CALENDAR.JULIAN;

	QUnit.module( 'time.js: time.Time.validate()' );

	QUnit.test( 'validating valid time definitions', function( assert ) {
		$.each( time.validTimeDefinitions, function( name, timeDefinition ) {
			var valid = true;
			try {
				Time.validate( timeDefinition ); // throws an error if failure
			} catch( e ) {
				valid = false;
			}

			assert.ok(
				valid,
				'valid definition (with key "' + name + '") has been accepted by validate()'
			);
		} );
	} );

	var validDefinition = {
		calendarname: J,
		year: 1492,
		month: 10,
		day: 12,
		precision: PRECISION.DAY
	};

	function newInvalidTestDefinition( reason, change ) {
		return {
			reason: reason,
			definition: $.extend( {}, validDefinition, change )
		};
	}

	var invalidDefinitions = [
		{
			reason: 'no object given',
			definition: null
		},
		newInvalidTestDefinition(
			'invalid precision (string)',
			{ precision: 'foo' }
		),
		newInvalidTestDefinition(
			'invalid numeric precision',
			{ precision: Time.maxPrecision() + 1 }
		),
		newInvalidTestDefinition(
			'invalid year (string)',
			{ year: 'foo' }
		),
		newInvalidTestDefinition(
			'invalid year NaN',
			{ year: Number.NaN }
		),
		newInvalidTestDefinition(
			'month above 12',
			{ month: 13 }
		),
		newInvalidTestDefinition(
			'month below 1',
			{ month: -1 }
		),
		newInvalidTestDefinition(
			'month below 1',
			{ month: 0 }
		),
		newInvalidTestDefinition(
			'day below 1',
			{ month: 0 }
		),
		newInvalidTestDefinition(
			'unknown calendar name',
			{ calendarname: 'foo' }
		),
		newInvalidTestDefinition(
			'precision higher day not yet supported',
			{ precision: Time.PRECISION.DAY + 1 }
		), {
			reason: 'precision is "DAY" but field "day" not given',
			definition: {
				calendarname: J,
				year: 1492,
				month: 10,
				precision: PRECISION.DAY
			}
		}, {
			reason: 'precision is "DAY" but field "month" not given',
			definition: {
				calendarname: G,
				year: 1492,
				day: 1,
				precision: PRECISION.DAY
			}
		}, {
			reason: 'precision is "DAY" but field "year" not given',
			definition: {
				calendarname: J,
				month: 1,
				day: 1,
				precision: PRECISION.DAY
			}
		}, {
			reason: 'precision is "MONTH" but field "month" not given',
			definition: {
				calendarname: J,
				year: 1234,
				precision: PRECISION.MONTH
			}
		}, {
			reason: 'precision is "MONTH" but field "year" not given',
			definition: {
				calendarname: J,
				month: 12,
				precision: PRECISION.MONTH
			}
		}, {
			reason: 'precision is "YEAR" but field "year" not given',
			definition: {
				calendarname: J,
				month: 12,
				precision: PRECISION.YEAR
			}
		}
	];

	QUnit.test( 'validating invalid time definitions', function( assert ) {
		Time.validate( newInvalidTestDefinition( '', { year: 1234 } ).definition );
		assert.ok(
			true,
			'Checked for test helper. Valid definition used as base is actually valid.'
		);

		$.each( invalidDefinitions, function( i, timeTestDefinition ) {
			assert.throws(
				function() {
					Time.validate( timeTestDefinition.definition );
				},
				'Validation of time object ' + i + ' failed because of ' + timeTestDefinition.reason
			);
		} );
	} );

} );
