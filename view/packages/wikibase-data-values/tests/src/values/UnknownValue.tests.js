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
	'dataValues.UnknownValue'
];

define( DEPS, function( dv, util ) {

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
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.UnknownValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
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

}( define ) );
