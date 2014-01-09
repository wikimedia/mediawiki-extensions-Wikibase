/**
 * @since 0.1
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 */
( function( vp, dv, $, QUnit, GlobeCoordinate ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the GlobeCoordinateParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.GlobeCoordinateParserTest = vp.util.inherit( PARENT, {

		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return vp.GlobeCoordinateParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			return [
				[
					'1.5, 1.25',
					new dv.GlobeCoordinateValue( new GlobeCoordinate( { latitude: 1.5, longitude: 1.25, precision: 0.01 } ) )
				],
				[
					'-50, -20',
					new dv.GlobeCoordinateValue( new GlobeCoordinate( { latitude: -50, longitude: -20, precision: 1 } ) )
				]
			];
		}

	} );

	var test = new vp.tests.GlobeCoordinateParserTest();

	test.runTests( 'valueParsers.GlobeCoordinateParser' );

}( valueParsers, dataValues, jQuery, QUnit, globeCoordinate.GlobeCoordinate ) );
