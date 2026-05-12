/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( vp, dv, util ) {
	'use strict';

	var PARENT = vp.tests.ValueParserTest;

	/**
	 * Constructor for creating a test object holding tests for the BoolParser.
	 *
	 * @constructor
	 * @extends dv.tests.ValueParserTest
	 * @since 0.1
	 */
	vp.tests.BoolParserTest = util.inherit( PARENT, {

		/**
		 * @inheritdoc
		 */
		getConstructor: function() {
			return vp.BoolParser;
		},

		/**
		 * @inheritdoc
		 */
		getParseArguments: function() {
			var validValues = {
				'yes': true,
				'on': true,
				'1': true,
				'true': true,
				'no': false,
				'off': false,
				'0': false,
				'false': false
			};

			var argLists = [];

			// build a list with arrays as entries, [0] is parser input, [1] expected output:
			for ( var rawValue in validValues ) {
				if ( validValues.hasOwnProperty( rawValue ) ) {
					argLists.push( [ rawValue, new dv.BoolValue( validValues[rawValue] ) ] );
				}
			}

			return argLists;
		}

	} );

	var test = new vp.tests.BoolParserTest();

	test.runTests( 'valueParsers.BoolParser' );

}( valueParsers, dataValues, util ) );
