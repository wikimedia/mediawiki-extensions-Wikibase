/**
 * @since 0.1
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, $, QUnit, Coordinate ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the coordinate DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.CoordinateValueTest = dv.util.inherit( PARENT, {

		/**
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.CoordinateValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[ new Coordinate( '1.5 1.25' ) ],
				[ new Coordinate( '-50 -20' ) ]
			];
		}

	} );

	var test = new dv.tests.CoordinateValueTest();

	test.runTests( 'dataValues.CoordinateValue' );

}( dataValues, jQuery, QUnit, coordinate.Coordinate ) );
