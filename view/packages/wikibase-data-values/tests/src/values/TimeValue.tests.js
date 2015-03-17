/**
 * @licence GNU GPL v2+
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
				['+0000000000001942-04-01T00:00:00Z'],
				['+0000000000001400-01-01T00:00:00Z', {
					calendarModel: 'http://www.wikidata.org/entity/Q1985786'
				} ],
				['-0000000000000042-00-00T00:00:00Z', {
					precision: 9
				}]
			];
		},

		/**
		 * Tests if the equals method is able to return false.
		 *
		 * @since 0.7
		 *
		 * @param {QUnit} assert
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
		 * Tests the effect of the private pad() function, relevant in getSortKey() and toJSON().
		 *
		 * @since 0.7
		 *
		 * @param {QUnit} assert
		 */
		testPad: function( assert ) {
			var testCases = {
				'-123456789012-00-00T00:00:00Z': '-123456789012-00-00T00:00:00Z',
				'-12345678901-00-00T00:00:00Z': '-12345678901-00-00T00:00:00Z',
				'-1-1-1T01:01:01Z': '-00000000001-01-01T01:01:01Z',
				'1-1-1T01:01:01Z': '+00000000001-01-01T01:01:01Z',
				'12-00-00T00:00:00Z': '+00000000012-00-00T00:00:00Z',
				'1234567890-00-00T00:00:00Z': '+01234567890-00-00T00:00:00Z',
				'12345678901-00-00T00:00:00Z': '+12345678901-00-00T00:00:00Z',
				'123456789012-00-00T00:00:00Z': '+123456789012-00-00T00:00:00Z',
				'1234567890123456-00-00T00:00:00Z': '+1234567890123456-00-00T00:00:00Z'
			};

			for( var iso8601 in testCases ) {
				var expected = testCases[iso8601],
					actual = new dv.TimeValue( iso8601 ).getSortKey();

				assert.ok(
					expected === actual,
					'Expected getSortKey() to return "' + expected + '", got "' + actual + '"'
				);

			}
		}

	} );

	var test = new dv.tests.TimeValueTest();

	test.runTests( 'dataValues.TimeValue' );

} );
