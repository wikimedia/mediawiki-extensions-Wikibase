/**
 * @since 0.1
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( vp, dv, $, QUnit, undefined ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest,
		constructor = function() {
		};

	/**
	 * Constructor for creating a test object holding tests for the IntParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.IntParserTest = vp.util.inherit( PARENT, constructor, {

		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return vp.IntParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			return [
				// TODO: replace test stub
				[ '4', new dv.NumberValue( 4 ) ],
				[ '42', new dv.NumberValue( 42 ) ],
				[ '0', new dv.NumberValue( 0 ) ],
				[ '9001', new dv.NumberValue( 9001 ) ]
			];
		}

	} );

	var test = new vp.tests.IntParserTest();

	test.runTests( 'valueParsers.IntParser' );

}( valueParsers, dataValues, jQuery, QUnit ) );
