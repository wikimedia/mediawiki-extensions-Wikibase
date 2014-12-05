/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'time/time.Time',
	'tests/src/dataValues.DataValue.tests',
	'values/TimeValue'
], function( dv, util, Time ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the time DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
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
				[ new Time( 'April 1, 1942' ) ],
				[ new Time( '123456 BC' ) ],
				[ new Time( '-42' ) ]
			];
		}

	} );

	var test = new dv.tests.TimeValueTest();

	test.runTests( 'dataValues.TimeValue' );

} );
