/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( vf, dv ) {
	'use strict';

	var PARENT = vf.tests.ValueFormatterTest;

	/**
	 * Constructor for creating a test object containing tests for the StringFormatter.
	 *
	 * @constructor
	 * @extends valueFormatters.tests.ValueFormatterTest
	 * @since 0.1
	 */
	vf.tests.StringFormatterTest = vf.util.inherit( PARENT, {

		/**
		 * @see valueFormatters.tests.ValueFormatterTest.getObject
		 */
		getConstructor: function() {
			return vf.StringFormatter;
		},

		/**
		 * @see valueFormatters.tests.ValueFormatterTest.getFormatArguments
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

}( valueFormatters, dataValues ) );
