/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'values/TimeValue',
	'tests/src/dataValues.DataValue.tests'
], function( dv, util, TimeValue ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the `Time` `DataValue`.
	 * @see dataValues.TimeValue
	 * @class dataValues.tests.TimeValueTest
	 * @extends dataValues.tests.DataValueTest
	 * @since 0.1
	 *
	 * @constructor
	 */
	dv.tests.TimeValueTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.TimeValue;
		},

		/**
		 * @inheritdoc
		 */
		getConstructorArguments: function() {
			return [
				[ '+0000000000001942-04-01T00:00:00Z' ],

				// Optional parts
				[ '+0000000000001942-04-01T00:00:00' ],
				[ '+0000000000001942-04-01T00:00' ],
				[ '+0000000000001942-04-01T' ],
				[ '0000000000001942-04-01T' ],
				[ '1942-04-01T' ],

				// Minimal and maximal length
				[ '+1-1-1T1:1:1Z' ],
				[ '+9999999999999999-12-31T23:59:59Z' ],

				// Options
				[ '+0000000000001400-01-01T00:00:00Z', {
					calendarModel: 'http://www.wikidata.org/entity/Q1985786'
				} ],
				[ '-0000000000000042-00-00T00:00:00Z', {
					precision: 9
				} ]
			];
		},

		/**
		 * Tests if the constructor fails as expected for invalid and unsupported timestamp values.
		 *
		 * @since 0.7
		 *
		 * @param {QUnit.assert} assert
		 */
		testConstructorThrowsException: function( assert ) {
			var invalidTimestamps = [
				// Non-strings
				undefined,
				null,
				1,
				0.1,

				// The "T" is required
				'',
				'1',
				'1942-04-01',
				'+0000000000002015-01-01 01:01:01Z',

				// Unsupported time zones
				'+0000000000002015-01-01T01:01:01A',
				'+0000000000002015-01-01T01:01:01+0000',
				'+0000000000002015-01-01T01:01:01+00:00'
			];
			var i, invalidTimestamp;

			for ( i = 0; i < invalidTimestamps.length; i++ ) {
				invalidTimestamp = invalidTimestamps[i];

				assert['throws'](
					function() {
						dv.TimeValue( invalidTimestamp );
					},
					'"' + invalidTimestamp + '" is not a valid TimeValue timestamp'
				);
			}
		},

		/**
		 * Tests if the equals method is able to return false.
		 *
		 * @since 0.7
		 *
		 * @param {QUnit.assert} assert
		 */
		testNotEquals: function( assert ) {
			var timeValue1 = new dv.TimeValue( '2015-12-30T00:00:00Z' ),
				timeValue2 = new dv.TimeValue( '2015-12-31T00:00:00Z' );

			assert.ok(
				!timeValue1.equals( timeValue2 ),
				'instances encapsulating different values are not equal'
			);
		},

		/**
		 * Tests the effect of the private pad() function, relevant in toJSON() and getSortKey().
		 *
		 * @since 0.7
		 *
		 * @param {QUnit.assert} assert
		 */
		testPad: function( assert ) {
			var testCases = {
				// Without leading zeros
				'-9000000000000000-12-31T23:59:59Z': [
					'-9000000000000000-12-31T23:59:59Z',
					'-9000000000000000-12-31T23:59:59Z'
				],
				'-123456789012-00-00T00:00:00Z': [
					'-123456789012-00-00T00:00:00Z',
					'-0000123456789012-00-00T00:00:00Z'
				],
				'-12345678901-00-00T00:00:00Z': [
					'-12345678901-00-00T00:00:00Z',
					'-0000012345678901-00-00T00:00:00Z'
				],
				'-1234-1-1T01:01:01Z': [
					'-1234-01-01T01:01:01Z',
					'-0000000000001234-01-01T01:01:01Z'
				],
				'-123-1-1T01:01:01Z': [
					'-0123-01-01T01:01:01Z',
					'-0000000000000123-01-01T01:01:01Z'
				],
				'-12-1-1T01:01:01Z': [
					'-0012-01-01T01:01:01Z',
					'-0000000000000012-01-01T01:01:01Z'
				],
				'-1-1-1T01:01:01Z': [
					'-0001-01-01T01:01:01Z',
					'-0000000000000001-01-01T01:01:01Z'
				],
				'0-1-1T01:01:01Z': [
					'+0000-01-01T01:01:01Z',
					'+0000000000000000-01-01T01:01:01Z'
				],
				'1-1-1T01:01:01Z': [
					'+0001-01-01T01:01:01Z',
					'+0000000000000001-01-01T01:01:01Z'
				],
				'12-00-00T00:00:00Z': [
					'+0012-00-00T00:00:00Z',
					'+0000000000000012-00-00T00:00:00Z'
				],
				'123-00-00T00:00:00Z': [
					'+0123-00-00T00:00:00Z',
					'+0000000000000123-00-00T00:00:00Z'
				],
				'1234-00-00T00:00:00Z': [
					'+1234-00-00T00:00:00Z',
					'+0000000000001234-00-00T00:00:00Z'
				],
				'1234567890-00-00T00:00:00Z': [
					'+1234567890-00-00T00:00:00Z',
					'+0000001234567890-00-00T00:00:00Z'
				],
				'12345678901-00-00T00:00:00Z': [
					'+12345678901-00-00T00:00:00Z',
					'+0000012345678901-00-00T00:00:00Z'
				],
				'123456789012-00-00T00:00:00Z': [
					'+123456789012-00-00T00:00:00Z',
					'+0000123456789012-00-00T00:00:00Z'
				],
				'1234567890123456-00-00T00:00:00Z': [
					'+1234567890123456-00-00T00:00:00Z',
					'+1234567890123456-00-00T00:00:00Z'
				],
				'9000000000000000-12-31T23:59:59Z': [
					'+9000000000000000-12-31T23:59:59Z',
					'+9000000000000000-12-31T23:59:59Z'
				],

				// With leading zeros
				'-0900000000000000-12-31T23:59:59Z': [
					'-900000000000000-12-31T23:59:59Z',
					'-0900000000000000-12-31T23:59:59Z'
				],
				'-0000000000000123-01-01T01:01:01Z': [
					'-0123-01-01T01:01:01Z',
					'-0000000000000123-01-01T01:01:01Z'
				],
				'+0000000000000000-01-01T01:01:01Z': [
					'+0000-01-01T01:01:01Z',
					'+0000000000000000-01-01T01:01:01Z'
				],
				'+0000000000000001-01-01T01:01:01Z': [
					'+0001-01-01T01:01:01Z',
					'+0000000000000001-01-01T01:01:01Z'
				],
				'+0900000000000000-12-31T23:59:59Z': [
					'+900000000000000-12-31T23:59:59Z',
					'+0900000000000000-12-31T23:59:59Z'
				],

				// Year would become 10000000000000000 when parsed as a number
				'-9999999999999999-12-31T23:59:59Z': [
					'-9999999999999999-12-31T23:59:59Z',
					'-9999999999999999-12-31T23:59:59Z'
				],
				'9999999999999999-12-31T23:59:59Z': [
					'+9999999999999999-12-31T23:59:59Z',
					'+9999999999999999-12-31T23:59:59Z'
				]
			};

			for( var timestamp in testCases ) {
				var timeValue = new dv.TimeValue( timestamp ),
					json = timeValue.toJSON().time,
					sortKey = timeValue.getSortKey(),
					expectedJSON = testCases[timestamp][0],
					expectedSortKey = testCases[timestamp][1];

				assert.ok(
					json === expectedJSON,
					'Expected toJSON().time to return "' + expectedJSON + '", got "' + json + '"'
				);
				assert.ok(
					sortKey === expectedSortKey,
					'Expected getSortKey() to return "' + expectedSortKey + '", got "' + sortKey + '"'
				);
			}
		}

	} );

	var test = new dv.tests.TimeValueTest();

	test.runTests( 'dataValues.TimeValue' );

} );
