/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( vf, dv, util ) {
	'use strict';

	var PARENT = vf.tests.ValueFormatterTest;

	/**
	 * Constructor for creating a test object containing tests for the NullFormatter.
	 *
	 * @constructor
	 * @extends valueFormatters.tests.ValueFormatterTest
	 * @since 0.1
	 */
	vf.tests.NullParserTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return vf.NullFormatter;
		},

		/**
		 * @inheritdoc
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

}( valueFormatters, dataValues, util ) );
