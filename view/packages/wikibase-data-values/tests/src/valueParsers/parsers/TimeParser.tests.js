/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
define( [
	'valueParsers/valueParsers',
	'dataValues/dataValues',
	'time/time.Time',
	'util/util.inherit',
	'parsers/TimeParser',
	'tests/src/valueParsers/valueParsers.tests',
	'values/TimeValue'
], function( vp, dv, Time, util ) {
	'use strict';

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
		 * @inheritdoc
		 */
		getConstructor: function() {
			return vp.TimeParser;
		},

		/**
		 * @inheritdoc
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
