/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, util, GlobeCoordinate ) {
	'use strict';

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
		 * @inheritdoc
		 */
		getConstructor: function() {
			return dv.GlobeCoordinateValue;
		},

		/**
		 * @inheritdoc
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

}( dataValues, util, globeCoordinate.GlobeCoordinate ) );
