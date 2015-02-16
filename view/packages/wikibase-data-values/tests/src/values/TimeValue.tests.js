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
		}

	} );

	var test = new dv.tests.TimeValueTest();

	test.runTests( 'dataValues.TimeValue' );

} );
