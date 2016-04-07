/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
define( [
	'valueFormatters/valueFormatters',
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/valueFormatters/valueFormatters.tests',
	'formatters/StringFormatter',
	'values/StringValue'
], function( vf, dv, util ) {
	'use strict';

	var PARENT = vf.tests.ValueFormatterTest;

	/**
	 * Constructor for creating a test object containing tests for the StringFormatter.
	 *
	 * @constructor
	 * @extends valueFormatters.tests.ValueFormatterTest
	 * @since 0.1
	 */
	vf.tests.StringFormatterTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return vf.StringFormatter;
		},

		/**
		 * @inheritdoc
		 */
		getFormatArguments: function() {
			return [
				[ new dv.StringValue( 'some string' ), 'some string' ],
				[ new dv.StringValue( ' foo ' ), ' foo ' ],
				[ new dv.StringValue( ' xXx' ), ' xXx' ],
				[ new dv.StringValue( 'xXx ' ), 'xXx ' ]
			];
		}

	} );

	var test = new vf.tests.StringFormatterTest();

	test.runTests( 'valueFormatters.StringFormatter' );

} );
