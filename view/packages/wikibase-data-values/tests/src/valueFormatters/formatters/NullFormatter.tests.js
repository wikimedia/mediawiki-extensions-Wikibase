/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
define( [
	'valueFormatters/valueFormatters',
	'dataValues/dataValues',
	'util/util.inherit',
	'tests/src/valueFormatters/valueFormatters.tests',
	'formatters/NullFormatter',
	'values/UnknownValue'
], function( vf, dv, util ) {
	'use strict';

	var PARENT = vf.tests.ValueFormatterTest,
		constructor = function() {
	};

	/**
	 * Constructor for creating a test object containing tests for the NullFormatter.
	 *
	 * @constructor
	 * @extends valueFormatters.tests.ValueFormatterTest
	 * @since 0.1
	 */
	vf.tests.NullParserTest = util.inherit( PARENT, constructor, {

		/**
		 * @see valueFormatters.tests.ValueFormatterTest.getConstructor
		 */
		getConstructor: function() {
			return vf.NullFormatter;
		},

		/**
		 * @see valueFormatters.tests.ValueFormatterTest.getFormatArguments
		 */
		getFormatArguments: function() {
			var date = new Date(),
				list = [ true, false, null ];

			return [
				[ new dv.UnknownValue( 'foo' ), 'foo' ],
				[ null, null ],
				[ 'plain string', [ 'plain string', new dv.UnknownValue( 'plain string' ) ] ],
				[ -99.9, [ '-99.9', new dv.UnknownValue( -99.9 ) ] ],
				[ date, [ String( date ), new dv.UnknownValue( date ) ] ],
				[ list, [ String( list ), new dv.UnknownValue( list ) ] ]
			];
		}

	} );

	var test = new vf.tests.NullParserTest();

	test.runTests( 'valueFormatters.NullFormatter' );

} );
