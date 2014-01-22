/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( define ) {
'use strict';

var DEPS = [
	'dataValues',
	'util.inherit',
	'dataValues.DataValue.tests',
	'dataValues.BoolValue'
];

define( DEPS, function( dv, util ) {

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
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.BoolValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
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

}( define ) );
