/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/dataValues.DataValue.tests',
	'values/NumberValue'
], function( dv, util ) {
	'use strict';

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
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.NumberValue;
		},

		/**
		 * @inheritdoc
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
