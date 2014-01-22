/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
( function( define ) {
'use strict';

var DEPS = [
	'valueParsers',
	'dataValues',
	'time.Time',
	'util.inherit',
	'valueParsers.TimeParser',
	'valueParsers.tests',
	'dataValues.TimeValue'
];

define( DEPS, function( vp, dv, Time, util ) {

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the TimeParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.TimeParserTest = util.inherit( PARENT, {

		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return vp.TimeParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			return [
				[ new Time( 'April, 2010' ), new dv.TimeValue( new Time( 'April, 2010' ) ) ],
				[ new Time( '123456 BC' ), new dv.TimeValue( new Time( '123456 BC' ) ) ]
			];
		}

	} );

	var test = new vp.tests.TimeParserTest();

	test.runTests( 'valueParsers.TimeParser' );

} );

}( define ) );
