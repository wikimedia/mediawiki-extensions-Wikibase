/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
define( [
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/dataValues.DataValue.tests',
	'values/UnknownValue'
], function( dv, util ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the unknown DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.UnknownValueTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.UnknownValue;
		},

		/**
		 * @inheritdoc
		 */
		getConstructorArguments: function() {
			return [
				[ '' ],
				[ 'foo' ],
				[ ' foo bar baz foo bar baz. foo bar baz ' ],
				[ 0 ],
				[ 42 ],
				[ -4.2 ],
				[ { 'a': 'b' } ],
				[ [ 'foo', 9001, { 'bar': 'baz', 5: 5 } ] ],
				[ new Date() ],
				[ false ],
				[ true ],
				[ null ],
				[ [] ],
				[ {} ]
			];
		}

	} );

	var test = new dv.tests.UnknownValueTest();

	test.runTests( 'dataValues.UnknownValue' );

} );
