/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
( function( define ) {
'use strict';

var DEPS = [
	'dataValues',
	'util.inherit',
	'dataValues.DataValue.tests',
	'dataValues.NumberValue'
];

define( DEPS, function( dv, util ) {

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the number DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.NumberValueTest = util.inherit( PARENT, {

		/**
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.NumberValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[ 0 ],
				[ 42 ],
				[ 4.2 ],
				[ -42 ],
				[ -4.2 ]
			];
		}

	} );

	var test = new dv.tests.NumberValueTest();

	test.runTests( 'dataValues.NumberValue' );

} );

}( define ) );
