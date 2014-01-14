/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vp, dv, GlobeCoordinate, util ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the GlobeCoordinateParser.
	 *
	 * @constructor
	 * @extends valueParsers.tests.ValueParserTest
	 * @since 0.1
	 */
	wb.tests.GlobeCoordinateParserTest = util.inherit( PARENT, {

		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return wb.GlobeCoordinateParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			return [
				[
					'1.5, 1.25',
					new dv.GlobeCoordinateValue( new GlobeCoordinate(
						{ latitude: 1.5, longitude: 1.25, precision: 0.01 }
					) )
				],
				[
					'-50, -20',
					new dv.GlobeCoordinateValue( new GlobeCoordinate(
						{ latitude: -50, longitude: -20, precision: 1 }
					) )
				]
			];
		}

	} );

	var test = new wb.tests.GlobeCoordinateParserTest();
	test.runTests( 'valueParsers.GlobeCoordinateParser' );

}( wikibase, valueParsers, dataValues, globeCoordinate.GlobeCoordinate, util ) );
