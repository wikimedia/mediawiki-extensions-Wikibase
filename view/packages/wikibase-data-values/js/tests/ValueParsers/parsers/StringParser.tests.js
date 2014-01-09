/**
 * @since 0.1
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
( function( vp, dv, $, QUnit, undefined ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the StringParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.StringParserTest = vp.util.inherit( PARENT, {

		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return vp.StringParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			return [
				[ '42', new dv.StringValue( '42' ) ],
				[ ' foo ', new dv.StringValue( ' foo ' ) ],
				[ ' Baa', new dv.StringValue( ' Baa' ) ],
				[ 'xXx ', new dv.StringValue( 'xXx ' ) ]
			];
		}

	} );

	var test = new vp.tests.StringParserTest();

	test.runTests( 'valueParsers.StringParser' );

}( valueParsers, dataValues, jQuery, QUnit ) );
