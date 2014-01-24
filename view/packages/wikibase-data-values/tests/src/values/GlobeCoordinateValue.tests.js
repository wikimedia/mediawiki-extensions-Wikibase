/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( define ) {
'use strict';

var DEPS = [
	'dataValues',
	'util.inherit',
	'globeCoordinate.GlobeCoordinate',
	'dataValues.DataValue.tests',
	'dataValues.GlobeCoordinateValue'
];

define( DEPS, function( dv, util, GlobeCoordinate ) {

	var PARENT = dv.tests.DataValueTest;

	/**
	 * Constructor for creating a test object for the globe coordinate DataValue.
	 *
	 * @constructor
	 * @extends dv.tests.DataValueTest
	 * @since 0.1
	 */
	dv.tests.GlobeCoordinateValueTest = util.inherit( PARENT, {

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
				[ new GlobeCoordinate( { latitude: 1.5, longitude: 1.25, precision: 0.01 } ) ],
				[ new GlobeCoordinate( { latitude: -50, longitude: -20, precision: 1 } ) ]
			];
		}

	} );

	var test = new dv.tests.GlobeCoordinateValueTest();

	test.runTests( 'dataValues.GlobeCoordinateValue' );

} );

}( define ) );
