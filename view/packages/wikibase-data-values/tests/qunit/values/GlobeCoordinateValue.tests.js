/**
 * @since 0.1
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, $, QUnit, GlobeCoordinate ) {
	'use strict';

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the globe coordinate DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.GlobeCoordinateValueTest = dv.util.inherit( PARENT, {

		/**
		 * @see dv.tests.DataValueTest.getConstructor
		 */
		getConstructor: function() {
			return dv.GlobeCoordinateValue;
		},

		/**
		 * @see dv.tests.DataValueTest.getConstructorArguments
		 */
		getConstructorArguments: function() {
			return [
				[ new GlobeCoordinate( '1.5 1.25' ) ],
				[ new GlobeCoordinate( '-50 -20' ) ]
			];
		}

	} );

	var test = new dv.tests.GlobeCoordinateValueTest();

	test.runTests( 'dataValues.GlobeCoordinateValue' );

}( dataValues, jQuery, QUnit, globeCoordinate.GlobeCoordinate ) );
