/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/dataValues.DataValue.tests',
	'values/BoolValue'
], function( dv, util ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the boolean DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.BoolValueTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.BoolValue;
		},

		/**
		 * @inheritdoc
		 */
		getConstructorArguments: function() {
			return [
				[ true ],
				[ false ]
			];
		}

	} );

	var test = new dv.tests.BoolValueTest();

	test.runTests( 'dataValues.BoolValue' );

} );
