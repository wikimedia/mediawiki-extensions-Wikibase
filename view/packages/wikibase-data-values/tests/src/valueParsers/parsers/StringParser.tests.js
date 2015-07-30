/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
define( [
	'valueParsers/valueParsers',
	'dataValues/dataValues',
	'util/util.inherit',
	'parsers/StringParser',
	'tests/src/valueParsers/valueParsers.tests',
	'values/StringValue'
], function( vp, dv, util ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the StringParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.StringParserTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return vp.StringParser;
		},

		/**
		 * @inheritdoc
		 */
		getParseArguments: function() {
			return [
				[ '42', new dv.StringValue( '42' ) ],
				[ ' foo ', new dv.StringValue( ' foo ' ) ],
				[ ' Baa', new dv.StringValue( ' Baa' ) ],
				[ 'xXx ', new dv.StringValue( 'xXx ' ) ],
				[ '', null ]
			];
		}

	} );

	var test = new vp.tests.StringParserTest();

	test.runTests( 'valueParsers.StringParser' );

} );
