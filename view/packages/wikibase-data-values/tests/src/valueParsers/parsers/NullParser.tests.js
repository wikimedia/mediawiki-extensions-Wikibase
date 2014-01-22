/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( define ) {
'use strict';

var DEPS = [
	'valueParsers',
	'dataValues',
	'util.inherit',
	'valueParsers.NullParser',
	'valueParsers.tests',
	'dataValues.UnknownValue'
];

define( DEPS, function( vp, dv, util ) {

	var PARENT = vp.tests.ValueParserTest,
		constructor = function() {
		};

	/**
	 * Constructor for creating a test object holding tests for the NullParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.NullParserTest = util.inherit( PARENT, constructor, {

		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return vp.NullParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			var date = new Date(),
				list = [ true, false, null ],
				dataValue = new dv.UnknownValue( 'foo' );

			return [
				[ dataValue, dataValue ],
				[ null, null ],
				[ '42', new dv.UnknownValue( '42' ) ],
				[ -4.2, new dv.UnknownValue( -4.2 ) ],
				[ date, new dv.UnknownValue( date ) ],
				[ list, new dv.UnknownValue( list ) ]
			];
		}

	} );

	var test = new vp.tests.NullParserTest();

	test.runTests( 'valueParsers.NullParser' );

} );

}( define ) );
