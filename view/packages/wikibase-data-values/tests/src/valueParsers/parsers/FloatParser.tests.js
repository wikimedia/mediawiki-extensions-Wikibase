/**
 * @license GPL-2.0+
 * @author Daniel Werner < danweetz@web.de >
 */
( function( vp, dv, util ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the FloatParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.FloatParserTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return vp.FloatParser;
		},

		/**
		 * @inheritdoc
		 */
		getParseArguments: function() {
			return [
				[ '0', new dv.NumberValue( 0 ) ],
				[ '-0', new dv.NumberValue( 0 ) ],
				[ '42', new dv.NumberValue( 42 ) ],
				[ '4.2', new dv.NumberValue( 4.2 ) ],
				[ '-42', new dv.NumberValue( -42 ) ],
				[ '-4.2', new dv.NumberValue( -4.2 ) ],
				[ '-9000.2', new dv.NumberValue( -9000.2 ) ]
			];
		}

	} );

	var test = new vp.tests.FloatParserTest();

	test.runTests( 'valueParsers.FloatParser' );

}( valueParsers, dataValues, util ) );
